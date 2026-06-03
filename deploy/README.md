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

## Deploy de cambios futuros

```bash
cd /opt/sigav
git pull
# si cambió composer.lock:
docker run --rm -v "$PWD":/app -w /app composer:2 install --no-dev --optimize-autoloader --ignore-platform-reqs --no-scripts
docker compose -f docker-compose.prod.yml run --rm --no-deps app php artisan package:discover
# si cambiaron assets:
docker run --rm -v "$PWD":/app -w /app node:14 sh -c "npm ci && npm run prod"
docker compose -f docker-compose.prod.yml restart app
```

> Correr `php artisan migrate --force` solo si se agregan migraciones nuevas; con el arranque por dump la tabla `migrations` está vacía (evaluar baseline antes).

## Restore

```bash
LAST=$(gcloud storage ls "$BACKUP_BUCKET/db/" | sort | tail -1)
gcloud storage cp "$LAST" /tmp/restore.sql.gz
set -a; . /opt/sigav/.env; set +a
gunzip -c /tmp/restore.sql.gz | docker exec -i sigav_db mysql -uroot -p"$DB_ROOT_PASSWORD" --default-character-set=latin1 "$DB_DATABASE"
```
También hay snapshots diarios del disco (restaurar creando un disco desde el snapshot).
