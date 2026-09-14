# Deploy de SIGAV v2 — Google Cloud (VM única)

Runbook operativo. El **plan detallado paso a paso** está en
[`docs/superpowers/plans/2026-06-02-deploy-gcp-vm.md`](../docs/superpowers/plans/2026-06-02-deploy-gcp-vm.md)
y el **diseño** en [`docs/superpowers/specs/2026-06-02-deploy-gcp-vm-design.md`](../docs/superpowers/specs/2026-06-02-deploy-gcp-vm-design.md).

## Arquitectura

Una VM Debian 12 (`e2-small`, `southamerica-east1`) corre `docker-compose.prod.yml`:

```
Internet → Caddy (443/80, HTTPS automático) → app (PHP 7.4 + Apache) → MySQL 5.7
```

- Código en `/opt/sigav` (clonado), **bind-mount** al contenedor → los archivos que la app
  escribe (AFIP, PDFs, imágenes, `storage/`) persisten en el disco.
- MySQL en volumen Docker, **sin puerto público**.
- DNS en Cloudflare en modo **DNS only** (Caddy saca Let's Encrypt por HTTP-01).

## Archivos de este directorio

| Archivo | Para qué |
|---|---|
| `Caddyfile` | Reverse proxy + TLS automático (usa `$APP_DOMAIN`). |
| `afip-protect.conf` | Apache: bloquea `public/AFIP/{key,cert}` y desactiva el listado de directorios. Se monta en `conf-enabled/`. |
| `.env.production.example` | Plantilla del `.env` de producción (copiar a `/opt/sigav/.env` y completar). |
| `backup.sh` | Backup nocturno: `mysqldump` + `tar` de archivos → bucket GCS. |

(El `docker-compose.prod.yml` está en la raíz del repo.)

## Variables de entorno del lado legacy (`public/*.php`)

`deploy/.env.production.example` ya trae, además de las variables Laravel/compose de siempre:

- `LEGACY_DB_HOST/USER/PASS/NAME` — conexión mysqli de `public/conection.php`. Sin las cuatro, el sitio legacy responde 500.
- `LEGACY_SEMILLA` — semilla de hashes legacy (cookies `rol`/`sucursal`, claves `sha1`). `public/conection.php` la lee directamente de esta variable (fuente única, ya no hay un literal separado que deba coincidir); ver el paso 3 de "Pasos manuales post-bootstrap" más abajo.
- `SMARTSUPP_KEY` — clave del chat en `public/header.php`. Vacía = script no se carga.
- `MAIL_HOST/PORT/USERNAME/PASSWORD/ENCRYPTION/FROM_ADDRESS/FROM_NAME` — SMTP saliente que usan tanto Laravel (`config/mail.php`) como `public/enviar_por_mail.php` / `enviar_por_mail_pedido.php` vía `legacy_mail_config()`.
- `CATALOGO_IMPORTAR_PERMITIDO` — guard de `catalogo:importar --force` (borra `ventas`, `factura`, `stock`, `stock_logs`, `descuentos_logs`, `productos_en_carrito`, `productos`). Dejar en `false` salvo en una instancia que arranca vacía.

**Nota:** el `PassEnv` de `deploy/afip-protect.conf` es la lista de variables que el código legacy puede leer bajo Apache/mod_php; toda variable nueva para `public/*.php` va ahí.

**Nota:** `consulta_stock.php` y `script_hora_reportes.php` leen `MAIL_FROM_ADDRESS` con `getenv()`; si se corren desde cron/CLI (no Apache) esa variable tiene que estar presente en el entorno del cron, o el script termina sin enviar el mail.

## Migrar la tabla `pedidos_legacy` en VMs bootstrapeadas por dump

Una VM que arrancó importando el dump (`dump/c2101314_ma.sql`) tiene la tabla
`migrations` vacía (ver "NO correr `php artisan migrate`" más arriba). Un
`php artisan migrate` a secas ahí intenta correr **todas** las migraciones
del repo contra un esquema que ya las tiene, y falla. Para aplicar solo la
migración de este branch (`2026_09_14_000000_archivar_pedidos_legacy`, que
renombra la tabla `pedidos` legacy a `pedidos_legacy` y crea la `pedidos` con
el esquema Laravel):

```bash
sudo docker exec sigav_app php artisan migrate --force --path=database/migrations/2026_09_14_000000_archivar_pedidos_legacy.php
```

Es idempotente: correrla una segunda vez imprime "Nothing to migrate.". Si
aborta con "pedidos_legacy ya existe", resolver a mano (decidir qué hacer con
la tabla `pedidos_legacy` existente) antes de reintentar.

## Flujo resumido

1. **Provisión GCP** (`gcloud`): IP estática, VM `e2-small` con tag `sigav`, firewall (80/443 público, SSH solo por IAP `35.235.240.0/20`), política de snapshots diaria, bucket Nearline con lifecycle 30d.
2. **Bootstrap en la VM** (SSH por IAP):
   - Instalar Docker (`curl -fsSL https://get.docker.com | sudo sh`).
   - `git clone` en `/opt/sigav` (ref que contenga estos artefactos — ver prerequisito de branches en el plan).
   - `cp deploy/.env.production.example .env` y completar (dominio, passwords, bucket).
   - Build one-shot: `composer install --no-dev --ignore-platform-reqs --no-scripts` (imagen `composer:2`) y `npm ci && npm run prod` (imagen `node:14`).
   - `docker compose -f docker-compose.prod.yml build app`, luego `key:generate` y `package:discover` con la imagen de la app.
   - Levantar `db`, **importar el dump** (`dump/c2101314_ma.sql`, latin1). **NO correr `php artisan migrate`** (el dump ya trae todas las tablas).
   - `docker compose -f docker-compose.prod.yml up -d --build`, `passport:install --force`, `storage:link`, permisos.
   - Subir certs de AFIP a `public/AFIP/{cert,key}`.
   - Crear el registro **A** en Cloudflare (DNS only) → IP de la VM; verificar HTTPS.
3. **Backups**: probar `deploy/backup.sh` y agendarlo por cron (`0 3 * * *`).

## Pasos manuales post-bootstrap (reproducibilidad)

Ajustes que se aplicaron a mano en la VM y **NO** están en el código. **Reaplicar en un redeploy desde cero** (todos los comandos desde `/opt/sigav`):

1. **Permisos de carpetas de subida** (Apache=`www-data` escribe imágenes ahí; sin esto da `Permission denied` al subir):
   ```bash
   sudo docker compose -f docker-compose.prod.yml exec -T app sh -c '
     for d in public/assets/sucursales public/assets/perfil public/productos; do
       mkdir -p "$d"; chown -R www-data:www-data "$d"; chmod -R 775 "$d";
     done'
   ```
   Si otra pantalla da `Permission denied` al subir/generar archivos, agregar esa carpeta a la lista.

2. **Columna `provider` en `oauth_clients`** (el dump trae el esquema viejo y arrancamos sin `migrate`; Passport la necesita):
   ```bash
   set -a; . ./.env; set +a
   sudo docker exec sigav_db mysql -uroot -p"$DB_ROOT_PASSWORD" "$DB_DATABASE" \
     -e "ALTER TABLE oauth_clients ADD COLUMN provider VARCHAR(255) NULL AFTER secret;"
   sudo docker compose -f docker-compose.prod.yml exec -T app php artisan passport:install --force
   ```

3. **`LEGACY_SEMILLA` en `.env`** — imprescindible (puente auth legacy→Laravel, middleware `LegacyCookieAuth`). Sin esto, las rutas Laravel (`/carga`, etc.) rebotan al login. La plantilla `deploy/.env.production.example` trae el nombre de la variable vacío a propósito; `public/conection.php` lee esta misma variable como `SEMILLA`, así que es una única fuente — completarla con el valor real desde el gestor de secretos (nunca commitear el valor).

> **Assets:** `npm ci` / `npm run prod` **no funcionan** (no hay `package-lock.json` ni `resources/js/app.js`·`resources/sass/app.scss`); los compilados ya vienen versionados en `public/css` y `public/js`. **Saltear el build de assets.**
>
> **Datos:** el dump trae esquema + `usuarios`/`roles`, pero `sucursales` y `productos` vienen **vacías**. Los usuarios referencian ~17 `sucursal_id` que no existen hasta cargar las sucursales reales (dump completo) o alinear ids a mano.

## Deploy de cambios futuros

```bash
cd /opt/sigav
git pull
# si cambió composer.lock:
docker run --rm -v "$PWD":/app -w /app composer:2 install --no-dev --optimize-autoloader --ignore-platform-reqs --no-scripts
docker compose -f docker-compose.prod.yml run --rm --no-deps app php artisan package:discover
# (los assets ya vienen compilados en public/; no se buildean con npm — ver nota arriba)
docker compose -f docker-compose.prod.yml restart app
```

> Correr `php artisan migrate --force` solo si se agregan migraciones nuevas; con el arranque por dump la tabla `migrations` está vacía (evaluar baseline antes — ver la sección "Migrar la tabla `pedidos_legacy` en VMs bootstrapeadas por dump" más arriba para el caso de la migración de este branch).

## Restore

```bash
LAST=$(gcloud storage ls "$BACKUP_BUCKET/db/" | sort | tail -1)
gcloud storage cp "$LAST" /tmp/restore.sql.gz
set -a; . /opt/sigav/.env; set +a
gunzip -c /tmp/restore.sql.gz | docker exec -i sigav_db mysql -uroot -p"$DB_ROOT_PASSWORD" --default-character-set=latin1 "$DB_DATABASE"
```
También hay snapshots diarios del disco (restaurar creando un disco desde el snapshot).

## Credenciales AFIP (post-deploy)

Las credenciales AFIP ya NO viven en el webroot. Se cargan desde la pantalla
Laravel `/afip/configuracion` (rol >= 2) y se guardan en `storage/app/afip/{homo,prod}/`.

Requisitos en el server:
- `storage/app/afip/` debe ser escribible por el usuario del web server
  (el SDK escribe ahí el cache de tokens `TA-*.xml`):
  `chown -R www-data:www-data storage/app/afip && chmod -R 750 storage/app/afip`
- Tras el primer deploy: `php artisan migrate && php artisan db:seed --class=AfipConfigSeeder` (en VMs bootstrapeadas por dump, ver "Migrar la tabla `pedidos_legacy` en VMs bootstrapeadas por dump" más arriba antes de correr un `migrate` a secas)
- Cargar credenciales de homologación y producción desde la pantalla.
- El switch de entorno activo (global) se cambia desde la misma pantalla.
