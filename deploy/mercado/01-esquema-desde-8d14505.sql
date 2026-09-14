-- =============================================================================
-- Mercado Artesanal: lleva la base `c2101314_ma` (esquema de master@8d14505,
-- feb-2024) al esquema que espera master hoy, y deja registradas en
-- `migrations` las migraciones equivalentes para que `php artisan migrate`
-- no las reintente.
--
-- IDEMPOTENTE: correrlo dos veces no cambia nada ni falla. Cada bloque
-- consulta information_schema antes de tocar; en MySQL 5.7 no existe
-- `ADD COLUMN IF NOT EXISTS`, por eso los ALTER van por PREPARE/EXECUTE
-- condicional.
--
-- NO borra datos. El único cambio sobre datos existentes es renombrar la
-- tabla `pedidos` legacy (esquema 2019, 3 filas) a `pedidos_legacy`.
--
-- Uso (VM, contra el MySQL compartido, base ya importada desde el dump):
--   sudo docker exec -i sigav_db mysql -uroot -p"$DB_ROOT_PASSWORD" mercado \
--        < /opt/mercado/deploy/mercado/01-esquema-desde-8d14505.sql
-- Verificación:
--   sudo docker exec mercado_app php artisan migrate --pretend   -> sin pendientes
-- =============================================================================

SET NAMES utf8mb4;
SET @db := DATABASE();

-- Lote único para todas las filas nuevas de `migrations`.
SELECT COALESCE(MAX(batch), 0) + 1 INTO @lote FROM migrations;

-- -----------------------------------------------------------------------------
-- 0. pedidos legacy -> pedidos_legacy (equivale a 2026_09_14_000000_archivar_pedidos_legacy)
--    Se renombra solo si `pedidos` existe y NO tiene la firma Laravel
--    (id_sucursal + monto, sin nro_pedido). Si `pedidos_legacy` ya existe y
--    `pedidos` sigue siendo legacy, se aborta: resolver a mano.
-- -----------------------------------------------------------------------------
SELECT COUNT(*) INTO @pedidos_existe FROM information_schema.tables
 WHERE table_schema = @db AND table_name = 'pedidos';
SELECT COUNT(*) INTO @pedidos_legacy_existe FROM information_schema.tables
 WHERE table_schema = @db AND table_name = 'pedidos_legacy';
SELECT COUNT(*) INTO @pedidos_es_laravel FROM information_schema.columns
 WHERE table_schema = @db AND table_name = 'pedidos' AND column_name IN ('id_sucursal', 'monto');
SELECT COUNT(*) INTO @pedidos_tiene_nro FROM information_schema.columns
 WHERE table_schema = @db AND table_name = 'pedidos' AND column_name = 'nro_pedido';
SET @pedidos_es_laravel := (@pedidos_es_laravel = 2 AND @pedidos_tiene_nro = 0);

-- Abort explícito: fuerza un error de SQL legible si hay que intervenir a mano.
SET @s := IF(@pedidos_existe = 1 AND NOT @pedidos_es_laravel AND @pedidos_legacy_existe = 1,
  'SIGNAL SQLSTATE ''45000'' SET MESSAGE_TEXT = ''pedidos_legacy ya existe y pedidos sigue siendo legacy: resolver a mano''',
  'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF(@pedidos_existe = 1 AND NOT @pedidos_es_laravel,
  'RENAME TABLE pedidos TO pedidos_legacy',
  'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 1. pedidos (esquema Laravel) — 2021_08_31_015258_create_pedidos_table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pedidos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_sucursal` int(11) NOT NULL,
  `fecha` datetime NOT NULL,
  `monto` double NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `estado` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. detalle_pedidos — 2021_08_31_015254_create_detalle_pedidos_table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `detalle_pedidos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_pedido` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio` double NOT NULL,
  `costo` double NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. sites_sucursales_opencart — 2021_10_21_015259_create_sites_sucursales_opencart_table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sites_sucursales_opencart` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_sucursal` int(11) NOT NULL,
  `url` varchar(255) NOT NULL,
  `user` varchar(255) NOT NULL,
  `password` longtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. oauth_clients.provider — 2021_08_16_010308_AddProviderColumnToOauthClientsTable
-- -----------------------------------------------------------------------------
SELECT COUNT(*) INTO @c FROM information_schema.columns
 WHERE table_schema = @db AND table_name = 'oauth_clients' AND column_name = 'provider';
SET @s := IF(@c = 0,
  'ALTER TABLE `oauth_clients` ADD COLUMN `provider` varchar(255) NULL AFTER `secret`',
  'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 5. afip_config — 2026_06_06_000000_create_afip_config_table
--    + filas del AfipConfigSeeder (homo activo, prod inactivo) si no existen.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `afip_config` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entorno` enum('homo','prod') NOT NULL,
  `cuit` varchar(255) NULL,
  `ptovta` varchar(255) NULL,
  `comprobante` varchar(255) NULL,
  `condicion_iva` varchar(255) NULL,
  `inicio_actividades` varchar(255) NULL,
  `ingresos_brutos` varchar(255) NULL,
  `emitir` tinyint(1) NOT NULL DEFAULT 0,
  `solicitar_datos` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `afip_config_entorno_unique` (`entorno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `afip_config` (`entorno`, `activo`, `created_at`, `updated_at`)
SELECT 'homo', 1, NOW(), NOW() FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM `afip_config` WHERE `entorno` = 'homo');
INSERT INTO `afip_config` (`entorno`, `activo`, `created_at`, `updated_at`)
SELECT 'prod', 0, NOW(), NOW() FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM `afip_config` WHERE `entorno` = 'prod');

-- -----------------------------------------------------------------------------
-- 6. stock_logs.tipo_operacion varchar(50) — 2026_06_07_100000_alter_stock_logs_tipo_operacion
-- -----------------------------------------------------------------------------
SELECT COALESCE(MAX(character_maximum_length), 0) INTO @len FROM information_schema.columns
 WHERE table_schema = @db AND table_name = 'stock_logs' AND column_name = 'tipo_operacion';
SET @s := IF(@len > 0 AND @len < 50,
  'ALTER TABLE `stock_logs` MODIFY `tipo_operacion` VARCHAR(50) NULL',
  'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 7. descuentos_logs — 2026_06_26_100000_create_descuentos_logs_table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `descuentos_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `usuario` varchar(200) NULL,
  `sucursal_id` int(11) NULL,
  `tipo_operacion` varchar(50) NOT NULL,
  `factura_id` int(11) NULL,
  `productos_id` int(11) NULL,
  `descuento_anterior` decimal(5,2) NULL,
  `descuento_nuevo` decimal(5,2) NOT NULL DEFAULT 0,
  `monto_descontado` decimal(12,2) NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 8. columnas de descuento — 2026_06_26_100100_add_descuento_columns
--    productos.descuento, productos_en_carrito.descuento, ventas.descuento,
--    factura.descuento_total (decimal(5,2) NOT NULL DEFAULT 0)
-- -----------------------------------------------------------------------------
SELECT COUNT(*) INTO @c FROM information_schema.columns
 WHERE table_schema = @db AND table_name = 'productos' AND column_name = 'descuento';
SET @s := IF(@c = 0, 'ALTER TABLE `productos` ADD COLUMN `descuento` decimal(5,2) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @c FROM information_schema.columns
 WHERE table_schema = @db AND table_name = 'productos_en_carrito' AND column_name = 'descuento';
SET @s := IF(@c = 0, 'ALTER TABLE `productos_en_carrito` ADD COLUMN `descuento` decimal(5,2) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @c FROM information_schema.columns
 WHERE table_schema = @db AND table_name = 'ventas' AND column_name = 'descuento';
SET @s := IF(@c = 0, 'ALTER TABLE `ventas` ADD COLUMN `descuento` decimal(5,2) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @c FROM information_schema.columns
 WHERE table_schema = @db AND table_name = 'factura' AND column_name = 'descuento_total';
SET @s := IF(@c = 0, 'ALTER TABLE `factura` ADD COLUMN `descuento_total` decimal(5,2) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 9. mercadopago_config — 2026_06_30_100000_create_mercadopago_config_table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mercadopago_config` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sucursal_id` int(10) unsigned NOT NULL,
  `access_token` text NULL,
  `public_key` varchar(255) NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mercadopago_config_sucursal_id_unique` (`sucursal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 10. mercadopago_pagos — 2026_06_30_100100_create_mercadopago_pagos_table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mercadopago_pagos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sucursal_id` int(10) unsigned NOT NULL,
  `mp_payment_id` varchar(255) NOT NULL,
  `fecha` datetime NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `monto_neto` decimal(12,2) NULL,
  `estado` varchar(255) NOT NULL,
  `medio_pago` varchar(255) NULL,
  `comprador` varchar(255) NULL,
  `payload_raw` json NULL,
  `estado_facturacion` varchar(255) NOT NULL DEFAULT 'pendiente',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mercadopago_pagos_sucursal_id_mp_payment_id_unique` (`sucursal_id`, `mp_payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 11. Registrar en `migrations` todo lo que el repo tiene y esta base no
--     (incluye 2021_04_21 logs_costos_precios, cuya tabla ya existe por SQL
--     directo). `migrations.migration` no es UNIQUE en Laravel: se usa
--     INSERT ... WHERE NOT EXISTS.
-- -----------------------------------------------------------------------------
INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.nombre, @lote FROM (
  SELECT '2021_04_21_010308_create_logs_costos_precios_table' AS nombre UNION ALL
  SELECT '2021_08_16_010308_AddProviderColumnToOauthClientsTable' UNION ALL
  SELECT '2021_08_31_015254_create_detalle_pedidos_table' UNION ALL
  SELECT '2021_08_31_015258_create_pedidos_table' UNION ALL
  SELECT '2021_10_21_015259_create_sites_sucursales_opencart_table' UNION ALL
  SELECT '2026_06_06_000000_create_afip_config_table' UNION ALL
  SELECT '2026_06_07_100000_alter_stock_logs_tipo_operacion' UNION ALL
  SELECT '2026_06_26_100000_create_descuentos_logs_table' UNION ALL
  SELECT '2026_06_26_100100_add_descuento_columns' UNION ALL
  SELECT '2026_06_30_100000_create_mercadopago_config_table' UNION ALL
  SELECT '2026_06_30_100100_create_mercadopago_pagos_table' UNION ALL
  SELECT '2026_09_14_000000_archivar_pedidos_legacy'
) AS m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` x WHERE x.migration = m.nombre);

-- Resumen para el operador.
SELECT 'pedidos_legacy' AS chequeo, COUNT(*) AS valor FROM information_schema.tables WHERE table_schema = @db AND table_name = 'pedidos_legacy'
UNION ALL SELECT 'pedidos_laravel', COUNT(*) FROM information_schema.columns WHERE table_schema = @db AND table_name = 'pedidos' AND column_name = 'monto'
UNION ALL SELECT 'oauth_clients.provider', COUNT(*) FROM information_schema.columns WHERE table_schema = @db AND table_name = 'oauth_clients' AND column_name = 'provider'
UNION ALL SELECT 'stock_logs.tipo_operacion.len', COALESCE(MAX(character_maximum_length), 0) FROM information_schema.columns WHERE table_schema = @db AND table_name = 'stock_logs' AND column_name = 'tipo_operacion'
UNION ALL SELECT 'afip_config.filas', COUNT(*) FROM afip_config
UNION ALL SELECT 'migrations.filas', COUNT(*) FROM migrations;
