-- =============================================================================
-- Mercado Artesanal: unifica la convención de bytes de las tablas utf8mb4.
--
-- Contexto (ver README): las tablas legacy son latin1 y guardan bytes UTF-8
-- "tal cual" (el POS conecta sin set_charset). Laravel en Ferozo creó otras
-- tablas en utf8mb4 (oauth_*, users, pedidos_optica*, logs_costos_precios, ...)
-- y ahí escribió UTF-8 real. Como la app ahora conecta en latin1 (passthrough),
-- esas filas se leen convertidas: 'PAÑO' llega como el byte 0xD1 y se ve 'PA?O'.
--
-- Este script re-escribe, en las columnas de texto de tablas utf8mb4 que tengan
-- caracteres no-ASCII, cada valor como CONVERT(BINARY(valor) USING latin1):
-- la fila queda guardada "doble" (como el resto de la base) y, leída por la
-- conexión latin1, devuelve exactamente los bytes UTF-8 originales.
--
-- SE APLICA UNA SOLA VEZ: si se corriera de nuevo re-codificaría lo ya
-- convertido. Por eso deja una marca en `charset_convencion_aplicada` y se
-- salta si la marca existe. Correr DESPUÉS de importar el dump fresco y de
-- 01-esquema-desde-8d14505.sql. Sin cambios de esquema.
-- =============================================================================

SET NAMES latin1;

CREATE TABLE IF NOT EXISTS `charset_convencion_aplicada` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `tabla` varchar(64) NOT NULL,
  `columna` varchar(64) NOT NULL,
  `filas` int NOT NULL,
  `aplicado_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP PROCEDURE IF EXISTS `mercado_unificar_convencion_utf8`;
DELIMITER $$
CREATE PROCEDURE `mercado_unificar_convencion_utf8`()
BEGIN
  DECLARE fin INT DEFAULT 0;
  DECLARE v_tabla VARCHAR(64);
  DECLARE v_col VARCHAR(64);
  DECLARE cur CURSOR FOR
    SELECT c.table_name, c.column_name
      FROM information_schema.columns c
      JOIN information_schema.tables t
        ON t.table_schema = c.table_schema AND t.table_name = c.table_name
     WHERE c.table_schema = DATABASE()
       AND t.table_collation LIKE 'utf8mb4%'
       AND c.character_set_name = 'utf8mb4'
       AND c.data_type IN ('varchar', 'char', 'text', 'tinytext', 'mediumtext', 'longtext')
       AND c.table_name <> 'charset_convencion_aplicada';
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET fin = 1;

  IF (SELECT COUNT(*) FROM `charset_convencion_aplicada`) > 0 THEN
    SELECT 'YA APLICADO: no se vuelve a convertir' AS aviso;
  ELSE
    OPEN cur;
    bucle: LOOP
      FETCH cur INTO v_tabla, v_col;
      IF fin = 1 THEN LEAVE bucle; END IF;

      SET @sql_upd := CONCAT(
        'UPDATE `', v_tabla, '` SET `', v_col, '` = CONVERT(BINARY(`', v_col, '`) USING latin1)',
        ' WHERE `', v_col, '` IS NOT NULL AND `', v_col, '` <> CONVERT(`', v_col, '` USING ascii)');
      PREPARE s FROM @sql_upd; EXECUTE s;
      SET @filas := ROW_COUNT();   -- antes del DEALLOCATE, que lo resetea
      DEALLOCATE PREPARE s;

      IF @filas > 0 THEN
        INSERT INTO `charset_convencion_aplicada` (`tabla`, `columna`, `filas`, `aplicado_at`)
        VALUES (v_tabla, v_col, @filas, NOW());
      END IF;
    END LOOP;
    CLOSE cur;
    -- Marca aunque no haya habido filas que convertir, para que no se re-corra.
    INSERT INTO `charset_convencion_aplicada` (`tabla`, `columna`, `filas`, `aplicado_at`)
    VALUES ('(marca)', '(marca)', 0, NOW());
  END IF;
END$$
DELIMITER ;

CALL `mercado_unificar_convencion_utf8`();
-- Se borra para que `mysqldump --routines` (backup.sh) no lo arrastre.
DROP PROCEDURE IF EXISTS `mercado_unificar_convencion_utf8`;

SELECT tabla, columna, filas, aplicado_at FROM `charset_convencion_aplicada` ORDER BY id;
