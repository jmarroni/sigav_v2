# Actualizar la instancia de Mercado Artesanal a la versión actual de SIGAV v2

Estado: **plan aprobado por el operator pendiente**. Revisado por el panel de IA (Codex `gpt-5.6-sol` en sandbox read-only + agente local `code-reviewer` con acceso completo). Ronda 1: ambos REVISE; esta versión incorpora todas las objeciones válidas. Informe del panel en el Apéndice A.

## 1. Contexto

- **SIGAV v2** (`sigav_v2`, rama `master`): inventario / POS / facturación AFIP multi-sucursal. Híbrido Laravel 7 + ~87 scripts PHP legacy en `public/` sobre la misma MySQL. Producción: VM GCP `sigav` (Docker Compose `caddy` + `app` PHP 7.4-Apache + `db` MySQL 5.7) para "Acantilado Sur" en `acantilado-sur.sigav.ar`, DB sin histórico.
- **Mercado Artesanal**: cliente original de este código. Instancia en hosting compartido Ferozo, `http://sistema.mercado-artesanal.com.ar` (Apache, solo HTTP: el certificado no cubre el subdominio). Copia local del hosting en `/home/juan/sitios/mercado/sistema/` (sin git) y dump de su DB `c2101314_ma` en `/home/juan/sitios/mercado/c2101314_ma.sql.zip` (feb-2026).
- **Objetivo:** que Mercado corra la misma versión que Acantilado, preservando su histórico.
- **Constraints:** Laravel 7 / PHP 7.2.5+; datos reales que hay que preservar; nada se prueba contra producción; certificados AFIP, dumps y credenciales no salen de la máquina.

## 2. Hallazgos verificados

### 2.1 Qué es `mercado/sistema/`

- Coincide con el commit **`8d14505` de master (2024-02-13, merge PR #24 `version_en_mercado_artesanal`)**: 197 de 198 archivos PHP relevantes byte-idénticos (verificado por hash de blobs); `ProductoController.php` difiere en 3 líneas en blanco.
- **Faltan 11 archivos** de ese commit: `app/Http/Controllers/Api/{ClienteController,PedidoController}.php`, `app/Models/{Pedido,DetallePedido}.php`, `app/Traits/{ProductosStock,Usuarios}.php`, `public/productos_por_sucursal_crud.php` y las migraciones `2021_08_16_010308_AddProviderColumnToOauthClientsTable`, `2021_08_31_015254_create_detalle_pedidos_table`, `2021_08_31_015258_create_pedidos_table`, `2021_10_21_015259_create_sites_sucursales_opencart_table`. El hosting se deployó parcialmente: la API de pedidos **nunca corrió** contra los datos de Mercado.
- 4 archivos fuera de git en `public/`: `netsuite_proxy.php` (proxy OAuth 1.0 a NetSuite para n8n; credenciales NetSuite hardcodeadas y `API_SECRET` placeholder `'tu_clave_secreta_aqui_123'`: endpoint público casi sin protección), `limpiar_cache.php` (bootstrapea Laravel y corre `config:clear`/`cache:clear` sin autenticación), `ventas_postbk.php`, `___index.php`.
- `SEMILLA` de `public/conection.php` es idéntica en ambos lados: cookies `rol`/`sucursal` y claves `sha1($clave.SEMILLA)` siguen válidas.
- La copia local **no tiene `storage/oauth-private.key` ni `storage/oauth-public.key`**. Los tokens Passport se firman con ese par, no con `APP_KEY`. Hay que rescatarlos del hosting; si no existen, los 382 `oauth_access_tokens` quedan inválidos y hay que coordinar re-login de los consumidores del API.
- `mercado/test/` corresponde a `origin/main` (`dd76be4`, 2026-03-01), historia sin ancestro común con master, `APP_URL=http://localhost`. Sin evidencia de deploy. Fuera de alcance; el CLAUDE.md de `mercado/` lo llama "primario" y confunde.

### 2.2 Delta de código `8d14505..master`

117 commits, 92 archivos (+4970 / −1698):

- **Seguridad** (PR #25 `74abf2d`): bcrypt lado Laravel; `API_SECRET_KEY` por env; verbos `DELETE`/`POST`; CORS en `config/cors.php`; `$guarded`. `public/login.php` acepta **bcrypt y sha1** (`password_verify` + `hash_equals`, consulta preparada).
- **UI**: menú superior unificado, rediseño `/ventas`, 10 reportes, paginación server-side `/carga`, `sigav-ui.css`.
- **AFIP**: tabla `afip_config` + credenciales en `storage/app/afip/{homo,prod}` fuera del webroot, pantalla `/afip/configuracion`, puente `public/afip_bridge.php` (los scripts legacy leen `afip_valor('cuit')` etc.), WSDL RG 5616. `AfipConfigSeeder` deja **homo activo** en la creación inicial y no pisa el activo en re-corridas.
- **Notas de crédito/débito** en Laravel (`NotasController`); `public/nota_credito.php` y `public/nota_debito.php` eliminados (`35279d3`) pero siguen en `sistema/`.
- **Descuentos**, **Mercado Pago** (config por sucursal con `access_token` cifrado con `Crypt`, movimientos, QR), tests PHPUnit, artefactos de deploy.
- **Importador CSV** `catalogo:importar`: `TABLAS_WIPE` = `ventas, productos_en_carrito, descuentos_logs, factura, stock_logs, stock, productos`. **Prohibido en Mercado** (borraría las 2.413 facturas).
- `public/conection.php:8-11` lee `LEGACY_DB_*` por `getenv()` con **fallback hardcodeado a las credenciales de Ferozo** (host, usuario/DB `c2101314_ma` y password). Secreto en repo público.
- **Env nuevas** que Mercado no tiene: `API_SECRET_KEY`, `LEGACY_DB_HOST/USER/PASS/NAME`, `LEGACY_SEMILLA` (= `SEMILLA`; la lee `config/app.php:73` para `LegacyCookieAuth`), `FRONTEND_URL`, `APP_DOMAIN`, `AFIP_*`. `APP_APEX` **no** la lee el código: es una variable del Caddyfile compartido de la VM para `sigav.ar`, Mercado no la necesita. `API_KEY_INTERNAL` y `LEGACY_HASH_SEED` del `.env` de `sistema/` no las usa ningún código de `sistema/`.
- **Guard `api`**: `config/auth.php` declara `driver => 'token'` tanto en master como en `sistema/`, mientras `routes/api.php` protege con `auth:api` y `Api\AuthController@login` emite tokens Passport. `users` no tiene `api_token`. Inconsistencia **preexistente e idéntica antes y después**: se verifica paridad, no se corrige en este proyecto.
- **Tabla `pedidos`: dos features distintas chocan por nombre de tabla.** El legacy `public/pedidos.php:48,259`, `pedidos_post.php:21,35,49`, `pedidos_optica.php:12` usan `(id, nro_pedido, productos_id, cantidad, precio, tipo_pago, fecha, usuario, sucursal_id, cliente_id, estado)`, el esquema que tiene Mercado (3 filas). El Laravel `Pedido` / `PedidoController` (ruta `/pedido`, enlazada desde `public/header.php:473`) y `Api\PedidoController::savePedido` esperan `(id, id_sucursal, fecha, monto, id_usuario, id_cliente, estado)` + `detalle_pedidos`, creados por `create_pedidos_table` con `Schema::create` incondicional. Contra la DB de Mercado la migración falla; si se salta, `/pedido` rompe por columnas inexistentes.

### 2.3 DB de producción de Mercado (`c2101314_ma`, dump feb-2026)

- Datos reales: `ventas` 3.411, `factura` 2.413, `clientes` 2.060, `productos` 1.065, `stock` 7.224, `stock_logs` 19.886, `transferencias` 501, `relacion_transferencias_productos` 4.854, `nota_de_credito` 153, `nota_de_debito` 36, `usuarios` 29, `sucursales` 9, `pedidos_optica` 5, `pedidos` 3, `oauth_access_tokens` 382, `users` 2.
- `migrations` con 17 filas; el resto del esquema nació por SQL directo.
- **Falta** respecto a master: tablas `detalle_pedidos`, `sites_sucursales_opencart`, `afip_config`, `descuentos_logs`, `mercadopago_config`, `mercadopago_pagos`; columna `oauth_clients.provider`; columnas de descuento (`2026_06_26_100100`); `stock_logs.tipo_operacion` es `varchar(20)` y el código escribe `DESCUENTO_PRODUCTO_CONFIG` (25 chars).
- Solo `AddProviderColumnToOauthClientsTable` y `add_descuento_columns` tienen guards (`hasColumn`); las 7 `Schema::create` restantes no tienen `hasTable`.
- Charset **latin1**. `docker-compose.prod.yml:28` ya fija `--character-set-server=latin1` para Acantilado: el patrón está probado.
- `clientes.condicion_iva`: 1.532 = `'4'` (CF), 519 = `'3'` (Exento), 2 = `'1'` (RI), **6 con un CUIT cargado en la columna** y 1 vacío. `public/afip_bridge.php:86-95` mapea 1..4 → AFIP y **cualquier otro valor cae a Consumidor Final (5)**: no rompe la emisión, pero los 7 registros hay que corregirlos.
- La tienda OpenCart (`mercado/tienda`) no comparte la DB (cero tablas `oc_*`).
- Único dato cifrado con `Crypt` en master: `mercadopago_config.access_token` (tabla que Mercado no tiene). Sesiones en archivos. Mantener `APP_KEY` es prudente pero **no** preserva Passport ni sesiones.

### 2.4 Datos de tenant hardcodeados en master (afectan a Acantilado hoy y a Mercado mañana)

Con hardcodeo real:
- `public/generar_facturar.php` (~45-61): logo, CUIT e Ing. Brutos de Mercado.
- `public/reimprimir.php:57`: `$cuitempresa` de Mercado.
- `public/facturar.php:124`, `public/search.php:26,43`, `public/ventas_post.php:73`: URL de imagen fallback a `sistema.mercado-artesanal.com.ar`.
- `public/enviar_por_mail.php:52-54` y `public/enviar_por_mail_pedido.php:46-59`: SMTP de Ferozo y `facturacion@mercado-artesanal.com.ar` como From/usuario.
- `public/header.php:558`: clave de Smartsupp.
- `app/Http/Controllers/Api/SucursalesController.php:60`, `ProductoController.php:500`: URLs a `www.mercado-artesanal.com.ar`.

Ya resueltos o muertos (no tocar por esto):
- `public/taxt_types.php:9` y `public/obtener_alicuotas.php:16` leen `afip_valor('cuit')`: dinámicos.
- `resources/views/login/login.php` es **código muerto**: `view('login/login')` resuelve `login.blade.php` (que no tiene URLs hardcodeadas) porque `FileViewFinder` prueba `.blade.php` antes que `.php`. Se borra.

### 2.5 AFIP, hosting actual y VM destino

- `sistema/public/AFIP/{cert,key}` en el webroot; ticket `TA-<CUIT>-wsfe.xml` en `Afip_res/`. Master espera cert/key cargados por `/afip/configuracion`; **homo activo por defecto**.
- Ferozo: Apache, solo HTTP, PHP de versión desconocida, sin Docker ni SSH garantizado. `netsuite_proxy.php` lo consume n8n.
- VM `sigav` e2-small (2 GB + swap 2 GB, disco 30 GB) ya corre 4 stacks y tuvo OOM en junio 2026.

## 3. Decisión: dónde corre Mercado actualizado

**Opción B (recomendada; el plan la desarrolla): stack independiente en la VM de GCP con DB propia.** No se comparte `sigav_db` (acopla backups/restores y anula el rollback independiente). Argumentos: HTTPS automático (hoy Mercado factura sin TLS), misma imagen PHP 7.4 + extensiones ya validada en Acantilado, charset latin1 ya resuelto en el compose, CLI real para seeders/`cache:clear` (la opción A obligaría a recrear justamente el `limpiar_cache.php` que hay que eliminar), backups a GCS. Costo: dimensionar la VM (RAM **y disco**) antes de anunciar la ventana, decidir `netsuite_proxy.php`, relay SMTP.

**Opción A (contingencia si la VM no se puede ampliar): in place en Ferozo.** Pasos marcados `[A]`.

## 4. Plan por fases

### Fase 0 — Saneamiento en `sigav_v2` master (bloqueante)

**Estado: implementada en la rama `feat/mercado-fase0-saneamiento` (plan `docs/superpowers/plans/2026-09-14-mercado-fase0-saneamiento.md`); rama pendiente de merge a `master`. Acciones pendientes del operator (no automatizables desde el repo): (1) rotar la password de la DB `c2101314_ma` en el panel de Ferozo y actualizar el `.env` del hosting; (2) regenerar la clave de Smartsupp (o dar de baja el chat) y cargarla como `SMARTSUPP_KEY` donde corresponda — ambos secretos estuvieron en el repo público; (3) en la VM de Acantilado, agregar a `/opt/sigav/.env` las variables nuevas que apliquen (`SMARTSUPP_KEY` vacío, `CATALOGO_IMPORTAR_PERMITIDO=false`, `MAIL_*` si se usa el mail) antes de desplegar este branch, porque `conection.php` ya no tiene fallback: si `LEGACY_DB_*`/`LEGACY_SEMILLA` faltaran, el sitio legacy daría 500.**

1. Sacar secretos del código: fallback hardcodeado en `public/conection.php:8-11` (solo `getenv()`, fallar explícito si falta); clave Smartsupp en `public/header.php:558`; SMTP/From en `public/enviar_por_mail.php` y `public/enviar_por_mail_pedido.php` (a config `mail` / tabla `perfil`). Rotar la password de la DB de Ferozo al terminar.
2. Desacoplar datos de tenant listados en §2.4 (los "con hardcodeo real"): leerlos de `afip_config` (CUIT, Ing. Brutos vía `afip_valor`), `perfil` (logo, mail) o `APP_URL`. Borrar `resources/views/login/login.php`.
3. **Decisión de producto sobre `pedidos`** (antes de la Fase 2). Opciones: (a) renombrar la tabla legacy a `pedidos_legacy` y apuntar `pedidos.php`/`pedidos_post.php`/`pedidos_optica.php` a ella, creando `pedidos`/`detalle_pedidos` con el esquema Laravel; (b) eliminar el flujo legacy si `/pedido` lo reemplaza y migrar las 3 filas a mano; (c) renombrar la tabla de la feature Laravel (`pedidos_app`) tocando `Pedido::$table`, `DetallePedido`, la migración y los reportes. El plan asume **(a)**; la elección es del operator.
4. `netsuite_proxy.php` y `limpiar_cache.php` no se copian. Si el proxy hace falta: reescribirlo dentro del repo con credenciales por env y autenticación real; confirmar con el flujo n8n quién lo llama.

### Fase 1 — Entorno destino

5. Dimensionar **antes de anunciar la ventana de corte**: `free -h`, `docker stats`, `df -h` con los 4 stacks corriendo. Un MySQL 5.7 más son ~400 MB RSS más el `app`. Si el margen libre real es menor a 1 GB o el disco libre menor a 10 GB, subir a e2-medium / ampliar disco.
6. Crear `deploy/mercado/`: `docker-compose.mercado.yml` con `mercado_app` (misma imagen `sigav-app:prod`), `mercado_db` (MySQL 5.7, `--character-set-server=latin1 --collation-server=latin1_swedish_ci`, volumen `mercado_db_data`), **red y nombres propios** sin colisión con `sigav_*`, `healthcheck` en ambos; `.env.mercado` desde `deploy/.env.production.example`: `APP_DOMAIN=sistema.mercado-artesanal.com.ar`, `LEGACY_DB_*` → `mercado_db`, `LEGACY_SEMILLA` = `SEMILLA`, `API_SECRET_KEY` nuevo, `APP_KEY` el de Ferozo. Sin `APP_APEX`.
7. Bloque Caddy nuevo en `deploy/Caddyfile` (mismo patrón que `sattor.sigav.ar`) con **dos hosts**: el definitivo y un alias de pre-producción `mercado-artesanal.sigav.ar` (A en Cloudflare DNS-only → VM) para smoke test con HTTPS real antes de tocar el DNS de Mercado. `PassEnv` de `deploy/afip-protect.conf` ya cubre `LEGACY_DB_*`/`API_SECRET_KEY`.
8. `[A]` En vez de 5-7: confirmar PHP ≥ 7.4 con `soap`, `gd`, `intl`, `bcmath`, `mbstring`, `pdo_mysql`, `mysqli`; conseguir SSH; certificado para el subdominio.

### Fase 2 — Ensayo de migración de datos en local (repetible hasta que salga sin manos)

9. MySQL 5.7 local con el dump `c2101314_ma.sql.zip` tal cual (latin1).
10. **Un único mecanismo, versionado: `deploy/mercado/01-esquema-desde-8d14505.sql`**, ejecutado con `mysql`:
    - Cada bloque con precondición explícita (`CREATE TABLE IF NOT EXISTS`, `ADD COLUMN` guardado por `information_schema`, `MODIFY` idempotente).
    - `RENAME TABLE pedidos TO pedidos_legacy` solo si `pedidos` no tiene la columna `id_sucursal` (según decisión del paso 3).
    - Tras cada bloque, `INSERT IGNORE INTO migrations (migration, batch)` con el nombre **exacto** del archivo y `batch = 2`, para las 10: `2021_08_16_010308_AddProviderColumnToOauthClientsTable`, `2021_08_31_015254_create_detalle_pedidos_table`, `2021_08_31_015258_create_pedidos_table`, `2021_10_21_015259_create_sites_sucursales_opencart_table`, `2026_06_06_000000_create_afip_config_table`, `2026_06_07_100000_alter_stock_logs_tipo_operacion`, `2026_06_26_100000_create_descuentos_logs_table`, `2026_06_26_100100_add_descuento_columns`, `2026_06_30_100000_create_mercadopago_config_table`, `2026_06_30_100100_create_mercadopago_pagos_table`.
    - Aceptación: dos corridas seguidas sin error y `php artisan migrate --pretend` sin pendientes. **No** se aplica con `artisan migrate`.
    - Después: `php artisan db:seed --class=AfipConfigSeeder` y corrección de los 7 `clientes.condicion_iva` anómalos (revisar a mano los 6 con CUIT por si son RI; el resto a `'4'`).
11. Checklist escrita con master montado sobre esa DB: login usuario existente (sha1) y nuevo (bcrypt); `/carga` con 1.065 productos; `/ventas` con histórico; 10 reportes; `/notas/credito` y `/notas/debito` muestran 153/36; reimpresión de factura vieja; `ñ` en clientes/productos en Laravel **y** en el POS legacy; `/pedido` funciona y `pedidos_legacy` conserva 3 filas; imágenes de productos (`storage/app/public/productos`) y facturas de proveedor (`storage/app/public/facturas/proveedores`) se ven; API: `POST /api/auth/login` + `POST /api/auth/productos` con token nuevo y con un token **pre-existente** (documentar si falla igual que en Ferozo: paridad, no regresión).
12. Documentar cada ajuste manual en `deploy/mercado/README.md`. Repetir 9-11 desde cero hasta que no haga falta intervención.

### Fase 3 — Deploy y cutover

13. Ventana anunciada. TTL del DNS de `sistema.mercado-artesanal.com.ar` a 300 s con 24 h de anticipación.
14. En Ferozo: página de mantenimiento; `mysqldump --default-character-set=latin1 --single-transaction c2101314_ma`; inventario de archivos con conteo y `sha256sum`: `storage/` entero (incluye `oauth-private.key`, `oauth-public.key`, `app/public/productos/`, `app/public/facturas/proveedores/`), `public/AFIP/{cert,key}`, `public/assets/perfil/`, `public/assets/sucursales/`, `public/productos/`, `public/facturas/`, `public/presupuesto/`, `public/clientes/`, `public/branchs/`, `public/notas_credito/`, `public/cobros/`, `upload_articles/` (fuentes: `deploy/backup.sh:27-29`, `deploy/README.md:53`, `ProductoController.php:341`, `ProveedorController.php:231`).
15. En la VM: checkout limpio de master en `/opt/mercado`; `docker compose -f docker-compose.mercado.yml up -d`; importar el dump; correr el SQL + seeder + fix de `condicion_iva`; restaurar archivos y verificar conteos/hashes; `chown www-data` de `storage/`, `public/assets/perfil`, `public/assets/sucursales`, `public/productos`; `php artisan storage:link`; `config:clear`/`route:clear`/`view:clear`.
16. Cargar credenciales AFIP de **producción** de Mercado por `/afip/configuracion` (vía el alias), "Probar conexión", activar Producción; dejar `emitir`/`solicitar_datos` como estaban.
17. **Smoke test completo por el alias antes del DNS:** checklist del paso 11 más mail saliente desde la VM y un token API pre-existente. AFIP prod: solo "Probar conexión", **sin emitir**.
18. **Cambio de DNS y punto de no retorno.** Verificar `https://sistema.mercado-artesanal.com.ar/login.php` 200 con certificado válido y 2 logins reales. Mientras no haya ventas ni comprobantes AFIP en la DB nueva, el rollback es volver el DNS y sacar el mantenimiento. La primera factura real de monto mínimo es el **punto de no retorno**: desde ahí se corrige hacia adelante y Ferozo queda con mantenimiento fijo (no "solo lectura": clientes con DNS cacheado no deben poder escribir ahí).
19. A los 7 días estables: borrar `netsuite_proxy.php` y `limpiar_cache.php` del hosting, rotar la password de la DB de Ferozo y las credenciales NetSuite.
20. Extender `deploy/backup.sh` a `mercado_db` + archivos de `/opt/mercado`, y **ensayar un restore** completo en local (Fase D pendiente también en Acantilado).

### Fase 4 — Post-cutover

21. Documentación operativa de Mercado (qué difiere de Acantilado: dominio, DB, AFIP prod activa, `pedidos_legacy`, usuarios).
22. Cerrar `mercado/test/` y `origin/main`: borrar o documentar como no productivo; corregir el CLAUDE.md de `mercado/`.

## 5. Riesgos

| Riesgo | Mitigación |
|---|---|
| Choque de features sobre `pedidos` | Decisión explícita en Fase 0 paso 3; rename guardado en el SQL |
| Tokens Passport inválidos por falta de `oauth-*.key` | Paso 14 los rescata; si no existen, aviso previo y re-login |
| Migración parcial deja tablas sin fila en `migrations` | Script único idempotente + "dos corridas + `migrate --pretend` vacío" |
| Mojibake latin1/utf8mb4 | `character-set-server=latin1` como en Acantilado; checklist de `ñ` |
| Facturar contra homologación | Paso 16 explícito + badge de entorno en `/ventas` |
| `condicion_iva` anómala (7 filas) | Default CF ya existe en `afip_bridge.php`; fix de datos en paso 10 |
| Pérdida/bifurcación de datos en rollback | Punto de no retorno definido (paso 18); Ferozo en mantenimiento fijo |
| OOM / disco en la VM | Paso 5 con umbrales, antes de anunciar la ventana |
| Archivos históricos faltantes | Inventario con conteos y hashes (paso 14) |
| Secretos y datos de tenant viajan a la instancia nueva | Fase 0 bloqueante |
| Cross-tenant: Acantilado imprime CUIT y logo de Mercado | Fase 0 paso 2 |
| `catalogo:importar` ejecutado por error | Prohibido en Mercado; considerar un guard por env |
| Guard `api` con driver `token` (preexistente) | Paridad documentada en paso 11; proyecto aparte |

## 6. Archivos que el plan asume o toca

- `public/conection.php`, `public/login.php`, `public/header.php`, `public/enviar_por_mail.php`, `public/enviar_por_mail_pedido.php`, `public/generar_facturar.php`, `public/reimprimir.php`, `public/facturar.php`, `public/search.php`, `public/ventas_post.php`, `public/afip_bridge.php`, `public/pedidos.php`, `public/pedidos_post.php`, `public/pedidos_optica.php`
- `app/Http/Controllers/{ProductoController,ProveedorController,PedidoController,NotasController,AfipConfigController}.php`, `app/Http/Controllers/Api/{AuthController,PedidoController,SucursalesController}.php`, `app/Http/Middleware/LegacyCookieAuth.php`, `app/Models/{AfipConfig,MercadoPagoConfig,Pedido,DetallePedido,Usuario,User}.php`, `app/Console/Commands/ImportarCatalogo.php`
- `database/migrations/*` (las 10 del paso 10), `database/seeds/AfipConfigSeeder.php`
- `resources/views/login/{login.php,login.blade.php}`, `resources/views/afip/configuracion.blade.php`
- `config/{app,auth,cors}.php`, `routes/{web,api}.php`
- `Dockerfile`, `docker-compose.prod.yml`, `deploy/{Caddyfile,afip-protect.conf,backup.sh,.env.production.example,README.md}`
- Nuevos: `deploy/mercado/{docker-compose.mercado.yml,.env.mercado.example,01-esquema-desde-8d14505.sql,README.md}`
- Fuera del repo: `/home/juan/sitios/mercado/sistema/`, `/home/juan/sitios/mercado/c2101314_ma.sql.zip`

---

## Apéndice A — Informe del panel de IA (ronda 1, 2026-09-13)

Gate `spec`. Artefacto: spec v1 + diff `8d14505..master` del lado Laravel/deploy (redactado) + lista de commits. Codex corrió con `-s read-only` sobre una **copia parcial y redactada** de sigav_v2 (sin `public/`, sin `.git`, sin `storage`, sin `vendor`; pasó `check-no-secrets` con 0 bloqueantes tras neutralizar falsos positivos). El agente local `code-reviewer` vio ambos directorios completos, sin redactar, en modo lectura.

### A.1 Coincidencias (ambos auditores)

- **`pedidos` es un choque de dos features por nombre de tabla**, no una migración a adaptar. Codex: `create_pedidos_table.php:16` es `Schema::create` incondicional. Local: columnas del dump vs migración no comparten ni nombres; `public/pedidos.php:259` y `pedidos_post.php:35` usan el esquema viejo **en master hoy**; `Api/PedidoController` nunca corrió en Mercado. Aplicado: Fase 0 paso 3 como decisión de producto.
- **Opción B** es la correcta, con DB propia. Local aporta que el compose ya resuelve latin1 y que la opción A se contradice con eliminar `limpiar_cache.php`. Aplicado en §3 y paso 5 (dimensionar antes de anunciar la ventana).

### A.2 Solo Codex (aceptadas)

1. Passport firma con `storage/oauth-{private,public}.key`, no con `APP_KEY`; la Fase 3 no las copiaba. Verificado: la copia local de Mercado **no las tiene**. Aplicado en §2.1 y paso 14.
2. Baseline de migraciones no determinista y 7 `Schema::create` sin `hasTable`. Verificado (solo 2 migraciones tienen guards). Aplicado: script único idempotente, paso 10.
3. Inventario de archivos persistentes incompleto (`storage/`, `public/productos`, `public/assets/sucursales`, `notas_credito`, `cobros`). Verificado contra `backup.sh:27-29`, `README.md:53`, `ProductoController.php:341`, `ProveedorController.php:231`. Aplicado en paso 14.
4. Rollback por DNS tras la primera factura AFIP bifurca datos. Aplicado: punto de no retorno, paso 18.
5. Sin smoke test antes del DNS. Aplicado: alias de pre-producción, pasos 7 y 17.
6. `APP_KEY` sobredimensionado; único cifrado es `mercadopago_config.access_token`. Verificado. Aplicado en §2.3.
7. Dimensionar también disco, health checks, restore ensayado. Aplicado en pasos 5, 6, 20.

### A.3 Solo revisor local (aceptadas)

1. `taxt_types.php:9` y `obtener_alicuotas.php:16` ya leen `afip_valor('cuit')`: sacados del inventario de hardcodeo.
2. Faltaban `facturar.php:124`, `search.php:26,43`, `ventas_post.php:73`, `enviar_por_mail_pedido.php:46-59`. Verificados y agregados en §2.4.
3. `resources/views/login/login.php` es código muerto (Blade gana en `FileViewFinder`). Verificado: `login.blade.php` no tiene las URLs. Aplicado: se borra.
4. `APP_APEX` no la lee el código; es del Caddyfile compartido. Verificado. Sacada de las env de Mercado.
5. `catalogo:importar` también borra `factura`, `descuentos_logs`, `stock_logs`, `productos_en_carrito`. Verificado en `ImportarCatalogo.php:25-33`. Corregido en §2.2.

### A.4 Disidencias (Codex vs mi lectura, con evidencia)

- **RG 5616 sin default (Codex #8).** Codex dijo que no hay evidencia de un default persistente para `condicion_iva`. Codex no tenía `public/`: `public/afip_bridge.php:86-95` (`afip_cond_iva_receptor`) mapea `1..4` y cae a Consumidor Final (5) para cualquier otro valor. El revisor local lo confirmó. La objeción se reduce a limpiar 7 registros anómalos (6 con un CUIT en la columna, 1 vacío), incorporado en paso 10. **Mi lectura: refutada como bloqueante; válida como tarea de datos.**
- **Guard `api` con driver `token` (Codex #2).** Codex lo trata como algo que "hay que corregir o explicar" antes de la migración. Verificado: `config/auth.php:45` dice `token` **también en `sistema/`** (Ferozo hoy), y `users` no tiene `api_token`. Es una inconsistencia real pero preexistente e idéntica antes y después; la actualización no la introduce ni la empeora. **Mi lectura: fuera del alcance de este proyecto; se verifica paridad en el paso 11 y se abre como trabajo aparte.** Si el operator prefiere aprovechar la migración para arreglarla, es una decisión suya, no un prerrequisito.

### A.5 Descartados

- Ninguno de los dos auditores reportó falsos positivos sobre el código; las neutralizaciones de la copia redactada (`$authHdr`, `<pw_key>`, líneas de `login.php` y `MercadoPagoConfig`) fueron avisadas en el prompt y Codex no las reportó.

### A.6 Participación

- **Codex** (`codex-cli 0.146.0`): participó, `VERDICT: REVISE`, 10 objeciones, ~4 min.
- **Agente local `code-reviewer`**: participó, `VERDICT: REVISE`, 6 objeciones, 69 lecturas, ~9 min.
- **Kimi**: no participó por pedido explícito del operator ("kimi por ahora no").
- Nivel 1 (scanners) no aplica al gate `spec`; sí corrió `check-no-secrets` sobre todo lo despachado.

### A.7 Overrides

Ninguno. No salió ningún valor real de la máquina: la copia para Codex excluyó `public/`, `.git`, `storage`, `vendor`, `acantiladosur/`, `docs/`, el CSV del catálogo y el test con fixture PEM, y pasó el gate con 0 bloqueantes. El sandbox se borró al terminar.

**Hallazgo del gate que sí exige acción (independiente del panel):** el repo público tiene secretos en `public/conection.php:10` (password de la DB de Ferozo como fallback) y `public/header.php:558` (clave Smartsupp). Están en la Fase 0 paso 1 y hay que rotarlos.
