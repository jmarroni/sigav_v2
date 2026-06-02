# Deploy de SIGAV v2 a Google Cloud (VM única) — Plan de implementación

> **Para ejecutores agénticos:** SUB-SKILL REQUERIDA: usar superpowers:subagent-driven-development (recomendado) o superpowers:executing-plans para implementar tarea por tarea. Los pasos usan checkboxes (`- [ ]`).
>
> **Importante:** las Fases B–D se ejecutan contra la cuenta de GCP del usuario y la VM (vía `gcloud`/SSH). El agente NO tiene credenciales de GCP; esos pasos son un runbook que ejecuta el usuario (o el agente guiándolo con su sesión autenticada). Las Fases A son artefactos de repo que el agente sí crea/commitea.

**Goal:** Poner SIGAV v2 en producción sobre una única VM de Compute Engine con Docker (Caddy + app + MySQL), HTTPS, backups y hardening básico.

**Architecture:** Una VM Debian 12 (`e2-small`, southamerica-east1) corre `docker-compose.prod.yml` con 3 servicios: Caddy (TLS automático, reverse proxy) → app (imagen PHP 7.4+Apache del `Dockerfile`, código bind-mount desde `/opt/sigav`) → MySQL 5.7 (volumen persistente, sin puerto público). Cloudflare en modo DNS-only. Backups por snapshots de disco + dump a un bucket de Cloud Storage.

**Tech Stack:** GCP Compute Engine, Cloud Storage, Docker + Compose, Caddy 2, MySQL 5.7, PHP 7.4/Laravel 7, Cloudflare DNS.

**Spec:** `docs/superpowers/specs/2026-06-02-deploy-gcp-vm-design.md`

**Convención de variables del runbook** (definir una vez en la shell antes de las Fases B–D):
```bash
export PROJECT_ID="tu-proyecto-gcp"
export REGION="southamerica-east1"
export ZONE="southamerica-east1-b"
export VM_NAME="sigav"
export DOMAIN="tu-dominio.com"          # el que administrás en Cloudflare
export BUCKET="gs://sigav-backups-${PROJECT_ID}"
```

---

## FASE A — Artefactos en el repo (branch `deploy/gcp-vm`)

> Todas las tareas A se hacen en la branch `deploy/gcp-vm` (ya creada). Cada una termina en commit.

### Task A1: Caddyfile (reverse proxy + TLS automático)

**Files:**
- Create: `deploy/Caddyfile`

- [ ] **Step 1: Crear el Caddyfile**

```
{$APP_DOMAIN} {
	encode gzip
	reverse_proxy app:80
	header {
		Strict-Transport-Security "max-age=31536000; includeSubDomains"
		X-Content-Type-Options "nosniff"
		Referrer-Policy "strict-origin-when-cross-origin"
		-Server
	}
}
```

- [ ] **Step 2: Verificar sintaxis (opcional, requiere docker)**

Run: `docker run --rm -v "$PWD/deploy/Caddyfile":/etc/caddy/Caddyfile:ro -e APP_DOMAIN=example.com caddy:2 caddy validate --config /etc/caddy/Caddyfile`
Expected: `Valid configuration`

- [ ] **Step 3: Commit**

```bash
git add deploy/Caddyfile
git commit -m "deploy: Caddyfile (reverse proxy + HTTPS automático)"
```

---

### Task A2: Protección de claves AFIP (Apache deny)

**Contexto:** `public/` es el docroot; `public/AFIP/key` y `public/AFIP/cert` contienen claves privadas que NO deben servirse por web. PHP las sigue leyendo del filesystem (esto solo bloquea el acceso HTTP).

**Files:**
- Create: `deploy/afip-protect.conf`

- [ ] **Step 1: Crear la conf de Apache**

```apache
# Bloquea el acceso web a las claves/certs de AFIP (siguen accesibles para PHP en disco).
<DirectoryMatch "^/var/www/html/public/AFIP/(key|cert)">
    Require all denied
</DirectoryMatch>

# Sin listado de directorios en el docroot.
<Directory /var/www/html/public>
    Options -Indexes
</Directory>
```

- [ ] **Step 2: Commit**

```bash
git add deploy/afip-protect.conf
git commit -m "deploy: bloquear acceso web a claves/certs AFIP"
```

---

### Task A3: docker-compose de producción

**Files:**
- Create: `docker-compose.prod.yml`

- [ ] **Step 1: Crear el compose de producción**

```yaml
# SIGAV v2 — producción (VM única). Variables desde el .env del directorio.
services:
  app:
    build: .
    image: sigav-app:prod
    container_name: sigav_app
    restart: always
    expose:
      - "80"
    volumes:
      - ./:/var/www/html
      - ./deploy/afip-protect.conf:/etc/apache2/conf-enabled/zz-afip-protect.conf:ro
    depends_on:
      db:
        condition: service_healthy
    environment:
      APACHE_DOCUMENT_ROOT: /var/www/html/public
      LEGACY_DB_HOST: db
      LEGACY_DB_USER: ${DB_USERNAME}
      LEGACY_DB_PASS: ${DB_PASSWORD}
      LEGACY_DB_NAME: ${DB_DATABASE}
    networks: [sigav]

  db:
    image: mysql:5.7
    container_name: sigav_db
    restart: always
    command: --character-set-server=latin1 --collation-server=latin1_swedish_ci
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_USER: ${DB_USERNAME}
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes:
      - sigav_db_data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "-p${DB_ROOT_PASSWORD}"]
      interval: 5s
      timeout: 5s
      retries: 30
    networks: [sigav]

  caddy:
    image: caddy:2
    container_name: sigav_caddy
    restart: always
    ports:
      - "80:80"
      - "443:443"
    environment:
      APP_DOMAIN: ${APP_DOMAIN}
    volumes:
      - ./deploy/Caddyfile:/etc/caddy/Caddyfile:ro
      - caddy_data:/data
      - caddy_config:/config
    depends_on:
      - app
    networks: [sigav]

networks:
  sigav:

volumes:
  sigav_db_data:
  caddy_data:
  caddy_config:
```

- [ ] **Step 2: Validar el compose (requiere docker)**

Run: `APP_DOMAIN=example.com DB_ROOT_PASSWORD=x DB_DATABASE=sigav DB_USERNAME=sigav DB_PASSWORD=y docker compose -f docker-compose.prod.yml config -q && echo OK`
Expected: `OK` (sin errores de sintaxis)

- [ ] **Step 3: Commit**

```bash
git add docker-compose.prod.yml
git commit -m "deploy: docker-compose de producción (caddy + app + mysql)"
```

---

### Task A4: Ejemplo de .env de producción

**Files:**
- Create: `deploy/.env.production.example`

- [ ] **Step 1: Crear el ejemplo de env**

```dotenv
# === .env de PRODUCCIÓN (copiar a /opt/sigav/.env y completar) ===
APP_NAME=SIGAV
APP_ENV=production
APP_KEY=                      # generar con: php artisan key:generate
APP_DEBUG=false
APP_URL=https://CAMBIAR-dominio
APP_DOMAIN=CAMBIAR-dominio    # usado por Caddy para el certificado
FRONTEND_URL=https://CAMBIAR-dominio

LOG_CHANNEL=stack

# --- Base de datos (Laravel + compose) ---
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=sigav
DB_USERNAME=sigav
DB_PASSWORD=CAMBIAR_password_fuerte_app
DB_ROOT_PASSWORD=CAMBIAR_password_fuerte_root

# --- Legacy (public/conection.php) — deben coincidir con los de arriba ---
LEGACY_DB_HOST=db
LEGACY_DB_USER=sigav
LEGACY_DB_PASS=CAMBIAR_password_fuerte_app
LEGACY_DB_NAME=sigav

# --- API legacy ---
API_SECRET_KEY=              # generar con: php -r "echo bin2hex(random_bytes(32));"

# --- Backups ---
BACKUP_BUCKET=gs://CAMBIAR-bucket
```

- [ ] **Step 2: Commit**

```bash
git add deploy/.env.production.example
git commit -m "deploy: ejemplo de .env de producción"
```

---

### Task A5: Confiar en el proxy (TrustProxies)

**Contexto:** detrás de Caddy, Laravel debe confiar en los headers `X-Forwarded-*` para generar URLs `https`. Hoy `protected $proxies;` está sin valor (no confía en nada).

**Files:**
- Modify: `app/Http/Middleware/TrustProxies.php`

- [ ] **Step 1: Setear `$proxies` a `*`**

Reemplazar:
```php
    protected $proxies;
```
por:
```php
    // Confía en el reverse proxy (Caddy) de la misma red Docker para honrar X-Forwarded-*.
    protected $proxies = '*';
```

- [ ] **Step 2: Verificar sintaxis PHP**

Run: `php -l app/Http/Middleware/TrustProxies.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add app/Http/Middleware/TrustProxies.php
git commit -m "fix: confiar en el reverse proxy para HTTPS detrás de Caddy"
```

---

### Task A6: Script de backup a Cloud Storage

**Files:**
- Create: `deploy/backup.sh`

- [ ] **Step 1: Crear el script**

```bash
#!/usr/bin/env bash
# Backup nocturno: dump de MySQL + tar de archivos -> bucket GCS.
# Uso: BACKUP_BUCKET=gs://... /opt/sigav/deploy/backup.sh
set -euo pipefail

APP_DIR="/opt/sigav"
cd "$APP_DIR"

# Toma BACKUP_BUCKET del entorno o del .env del proyecto.
if [ -z "${BACKUP_BUCKET:-}" ]; then
  BACKUP_BUCKET="$(grep -E '^BACKUP_BUCKET=' .env | cut -d= -f2-)"
fi
if [ -z "${BACKUP_BUCKET:-}" ]; then
  echo "ERROR: BACKUP_BUCKET no definido" >&2; exit 1
fi

TS="$(date +%Y%m%d-%H%M%S)"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

# 1) Dump lógico de la base (latin1, consistente para InnoDB)
docker exec sigav_db sh -c \
  'exec mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --routines --default-character-set=latin1 "$MYSQL_DATABASE"' \
  | gzip > "$TMP/db-$TS.sql.gz"

# 2) Archivos escribibles (los que la app genera en runtime)
tar -czf "$TMP/files-$TS.tar.gz" \
  public/AFIP public/presupuesto public/notas_credito public/branchs \
  public/clientes public/cobros storage 2>/dev/null || true

# 3) Subir al bucket
gcloud storage cp "$TMP/db-$TS.sql.gz"    "$BACKUP_BUCKET/db/"
gcloud storage cp "$TMP/files-$TS.tar.gz" "$BACKUP_BUCKET/files/"

echo "Backup OK: $TS -> $BACKUP_BUCKET"
```

- [ ] **Step 2: Validar sintaxis bash**

Run: `bash -n deploy/backup.sh && echo OK`
Expected: `OK`

- [ ] **Step 3: Commit**

```bash
git add deploy/backup.sh
git commit -m "deploy: script de backup (mysqldump + archivos) a GCS"
```

---

### Task A7: README del deploy (runbook)

**Files:**
- Create: `deploy/README.md`

- [ ] **Step 1: Crear el README** con el contenido de las Fases B, C y D de este plan (provisión, bootstrap, backups, verificación y restore), para que quede versionado junto a los artefactos.

- [ ] **Step 2: Commit y push de la branch**

```bash
git add deploy/README.md
git commit -m "deploy: runbook de provisión, bootstrap y restore"
git push -u origin deploy/gcp-vm
```

---

## FASE B — Provisión de GCP (runbook; ejecuta el usuario)

> Requiere `gcloud` autenticado (`gcloud auth login`) y `gcloud config set project $PROJECT_ID`.

### Task B1: Habilitar APIs y reservar IP estática

- [ ] **Step 1: Habilitar APIs**

```bash
gcloud services enable compute.googleapis.com storage.googleapis.com iap.googleapis.com
```

- [ ] **Step 2: Reservar IP estática**

```bash
gcloud compute addresses create sigav-ip --region="$REGION"
gcloud compute addresses describe sigav-ip --region="$REGION" --format='value(address)'
```
Expected: imprime la IP pública (anotarla → va al registro A de Cloudflare).

---

### Task B2: Crear la VM

- [ ] **Step 1: Crear instancia Debian 12 e2-small con tag `sigav`**

```bash
gcloud compute instances create "$VM_NAME" \
  --zone="$ZONE" \
  --machine-type=e2-small \
  --image-family=debian-12 --image-project=debian-cloud \
  --boot-disk-size=30GB --boot-disk-type=pd-balanced \
  --address=$(gcloud compute addresses describe sigav-ip --region="$REGION" --format='value(address)') \
  --tags=sigav
```

- [ ] **Step 2: Verificar que está RUNNING**

```bash
gcloud compute instances describe "$VM_NAME" --zone="$ZONE" --format='value(status)'
```
Expected: `RUNNING`

---

### Task B3: Firewall (80/443 público, SSH solo por IAP)

- [ ] **Step 1: Permitir HTTP/HTTPS al tag `sigav`**

```bash
gcloud compute firewall-rules create sigav-web \
  --direction=INGRESS --action=ALLOW --rules=tcp:80,tcp:443 \
  --source-ranges=0.0.0.0/0 --target-tags=sigav
```

- [ ] **Step 2: Permitir SSH solo desde el rango de IAP**

```bash
gcloud compute firewall-rules create sigav-ssh-iap \
  --direction=INGRESS --action=ALLOW --rules=tcp:22 \
  --source-ranges=35.235.240.0/20 --target-tags=sigav
```

- [ ] **Step 3: Verificar que NO haya regla de SSH abierta a 0.0.0.0/0**

```bash
gcloud compute firewall-rules list --format='table(name,sourceRanges.list(),allowed[].map().firewall_rule().list())'
```
Expected: ninguna regla con `0.0.0.0/0` sobre `tcp:22`.

---

### Task B4: Snapshots automáticos del disco

- [ ] **Step 1: Crear política de snapshot diaria (retención 14 días)**

```bash
gcloud compute resource-policies create snapshot-schedule sigav-daily \
  --region="$REGION" --max-retention-days=14 \
  --daily-schedule --start-time=07:00 \
  --on-source-disk-delete=keep-auto-snapshots
```

- [ ] **Step 2: Adjuntar la política al disco de la VM**

```bash
gcloud compute disks add-resource-policies "$VM_NAME" \
  --zone="$ZONE" --resource-policies=sigav-daily
```
Expected: `Updated [...]`.

---

### Task B5: Bucket de backups con lifecycle

- [ ] **Step 1: Crear bucket Nearline**

```bash
gcloud storage buckets create "$BUCKET" \
  --location="$REGION" --default-storage-class=NEARLINE \
  --uniform-bucket-level-access
```

- [ ] **Step 2: Aplicar lifecycle (borra objetos a los 30 días)**

```bash
cat > /tmp/lifecycle.json <<'JSON'
{ "rule": [ { "action": {"type":"Delete"}, "condition": {"age":30} } ] }
JSON
gcloud storage buckets update "$BUCKET" --lifecycle-file=/tmp/lifecycle.json
```

- [ ] **Step 3: Dar permiso de escritura a la service account de la VM**

```bash
SA=$(gcloud compute instances describe "$VM_NAME" --zone="$ZONE" --format='value(serviceAccounts[0].email)')
gcloud storage buckets add-iam-policy-binding "$BUCKET" \
  --member="serviceAccount:${SA}" --role=roles/storage.objectAdmin
```

---

## FASE C — Bootstrap de la app en la VM

> Conexión: `gcloud compute ssh "$VM_NAME" --zone="$ZONE" --tunnel-through-iap`
>
> **Prerequisito de branches:** los artefactos de la Fase A viven en `deploy/gcp-vm` y las mejoras de la grilla en `mejoras/grilla-productos-paginacion`. Antes de desplegar, **mergear ambas a `master`** (vía sus PRs) y desplegar `master`; o, como atajo, clonar directamente `deploy/gcp-vm`. El ref que se clone DEBE contener `docker-compose.prod.yml`, `deploy/` y el `dump/`.

### Task C1: Instalar Docker

- [ ] **Step 1: Instalar Docker + plugin compose**

```bash
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker "$USER"   # reloguear SSH después
```

- [ ] **Step 2: Verificar**

```bash
docker --version && docker compose version
```
Expected: imprime versiones de Docker y Compose v2.

---

### Task C2: Clonar el repo y preparar el .env

- [ ] **Step 1: Clonar en /opt/sigav**

```bash
sudo mkdir -p /opt/sigav && sudo chown "$USER" /opt/sigav
git clone https://github.com/jmarroni/sigav_v2.git /opt/sigav
cd /opt/sigav
git checkout master   # debe contener los artefactos de deploy (ver prerequisito de branches arriba)
```

- [ ] **Step 2: Crear el .env desde el ejemplo y completarlo**

```bash
cp deploy/.env.production.example .env
# Editar .env: APP_DOMAIN/APP_URL/FRONTEND_URL con el dominio real,
# DB_PASSWORD y DB_ROOT_PASSWORD con passwords fuertes (iguales en LEGACY_DB_PASS),
# BACKUP_BUCKET con el bucket creado.
nano .env
```

- [ ] **Step 3: Generar API_SECRET_KEY y pegarlo en .env**

```bash
docker run --rm php:7.4-cli php -r "echo bin2hex(random_bytes(32)).PHP_EOL;"
# copiar el valor a API_SECRET_KEY= en .env
```

---

### Task C3: Build de dependencias (contenedores one-shot)

> La imagen `composer:2` usa PHP 8; por eso se instala con `--no-scripts` (los scripts de Laravel 7 corren artisan y fallarían bajo PHP 8). Los comandos artisan se ejecutan luego con la **imagen de la app** (PHP 7.4 con mbstring/soap/etc.).

- [ ] **Step 1: Composer (sin dev, sin scripts)**

```bash
cd /opt/sigav
docker run --rm -v "$PWD":/app -w /app composer:2 \
  install --no-dev --optimize-autoloader --ignore-platform-reqs --no-scripts
```
Expected: crea `vendor/` sin errores.

- [ ] **Step 2: Assets front (Laravel Mix)**

```bash
docker run --rm -v "$PWD":/app -w /app node:14 sh -c "npm ci && npm run prod"
```
Expected: compila `public/css|js` sin errores.

- [ ] **Step 3: Construir la imagen de la app**

```bash
set -a; . ./.env; set +a
docker compose -f docker-compose.prod.yml build app
```
Expected: build OK de `sigav-app:prod`.

- [ ] **Step 4: Generar APP_KEY (con la imagen de la app, sin levantar DB)**

```bash
docker compose -f docker-compose.prod.yml run --rm --no-deps app php artisan key:generate
```
Expected: `Application key set successfully.` (escribe en `.env`).

- [ ] **Step 5: Regenerar el manifest de paquetes (lo omitido por --no-scripts)**

```bash
docker compose -f docker-compose.prod.yml run --rm --no-deps app php artisan package:discover
```
Expected: lista los paquetes descubiertos sin errores.

---

### Task C4: Levantar la base e importar el esquema

- [ ] **Step 1: Levantar solo la DB**

```bash
docker compose -f docker-compose.prod.yml up -d db
```

- [ ] **Step 2: Esperar a que esté healthy**

```bash
until [ "$(docker inspect -f '{{.State.Health.Status}}' sigav_db)" = healthy ]; do sleep 2; done; echo "db lista"
```
Expected: `db lista`.

- [ ] **Step 3: Importar el dump (esquema, latin1) en la base `sigav`**

```bash
set -a; . ./.env; set +a
docker exec -i sigav_db mysql -uroot -p"$DB_ROOT_PASSWORD" \
  --default-character-set=latin1 "$DB_DATABASE" < dump/c2101314_ma.sql
```

- [ ] **Step 4: Verificar que están las 53 tablas**

```bash
docker exec sigav_db mysql -uroot -p"$DB_ROOT_PASSWORD" "$DB_DATABASE" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_DATABASE';"
```
Expected: `53`.

> **IMPORTANTE — NO correr `php artisan migrate`.** El dump ya creó todas las tablas (incluidas `migrations`, `users`, `oauth_*`). Correr `migrate` fallaría con "table already exists".

---

### Task C5: Levantar la app, Passport y permisos

- [ ] **Step 1: Build + levantar todo el stack**

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

- [ ] **Step 2: Generar llaves de Passport (NO migra)**

```bash
docker compose -f docker-compose.prod.yml exec app php artisan passport:install --force
```
Expected: genera llaves y clientes; crea `storage/oauth-*.key`.

- [ ] **Step 3: storage:link y permisos de escritura**

```bash
docker compose -f docker-compose.prod.yml exec app php artisan storage:link || true
docker compose -f docker-compose.prod.yml exec app sh -c \
  'chown -R www-data:www-data storage bootstrap/cache public/AFIP public/presupuesto public/notas_credito public/branchs public/clientes public/cobros 2>/dev/null; chmod -R ug+rwX storage bootstrap/cache'
```

- [ ] **Step 4: Verificar que la app responde internamente**

```bash
docker compose -f docker-compose.prod.yml exec app sh -c 'apt-get install -y curl >/dev/null 2>&1; curl -s -o /dev/null -w "%{http_code}\n" -H "Host: $APP_DOMAIN" http://localhost/login.php'
```
Expected: `200`.

---

### Task C6: Subir certs de AFIP de producción

- [ ] **Step 1: Copiar certs a la VM (desde la máquina local del usuario)**

```bash
# En la máquina local:
gcloud compute scp ./AFIP-cert.crt ./AFIP-key.key "$VM_NAME":/tmp/ --zone="$ZONE" --tunnel-through-iap
```

- [ ] **Step 2: Ubicarlos en public/AFIP/{cert,key} con permisos restrictivos**

```bash
# En la VM:
cd /opt/sigav
mkdir -p public/AFIP/cert public/AFIP/key
mv /tmp/AFIP-cert.crt public/AFIP/cert/
mv /tmp/AFIP-key.key   public/AFIP/key/
docker compose -f docker-compose.prod.yml exec app sh -c 'chown -R www-data:www-data public/AFIP; chmod -R go-rwx public/AFIP/key'
```

- [ ] **Step 3: Verificar que NO se sirven por web (tras tener DNS+TLS, Task C7)**

Run (después de C7): `curl -s -o /dev/null -w "%{http_code}\n" https://$DOMAIN/AFIP/key/AFIP-key.key`
Expected: `403`.

---

### Task C7: DNS en Cloudflare + verificación HTTPS

- [ ] **Step 1: Crear registro A en Cloudflare (modo DNS only)**

En el panel de Cloudflare → DNS → Add record:
- Type: `A`, Name: el dominio/subdominio, IPv4: la IP estática de Task B1.
- **Proxy status: DNS only (nube gris).**

- [ ] **Step 2: Esperar propagación y verificar resolución**

```bash
dig +short $DOMAIN
```
Expected: la IP estática de la VM.

- [ ] **Step 3: Verificar HTTPS con certificado válido**

```bash
curl -sI https://$DOMAIN/login.php | head -1
curl -s -o /dev/null -w "redirect http→https: %{http_code}\n" http://$DOMAIN/login.php
```
Expected: primera línea `HTTP/2 200` (o 200 vía login); el http devuelve 308/301 a https. Caddy ya emitió el certificado Let's Encrypt.

---

## FASE D — Backups y verificación final

### Task D1: Programar el backup nocturno

- [ ] **Step 1: Probar el backup manualmente**

```bash
cd /opt/sigav && bash deploy/backup.sh
gcloud storage ls "$BUCKET/db/" "$BUCKET/files/"
```
Expected: aparecen `db-<ts>.sql.gz` y `files-<ts>.tar.gz`.

- [ ] **Step 2: Agendar cron diario (03:00)**

```bash
( crontab -l 2>/dev/null; echo "0 3 * * * cd /opt/sigav && /usr/bin/env bash deploy/backup.sh >> /var/log/sigav-backup.log 2>&1" ) | crontab -
crontab -l
```
Expected: la línea del cron aparece listada.

---

### Task D2: Prueba de restauración (dump)

- [ ] **Step 1: Bajar el último dump y restaurar a una base temporal**

```bash
cd /tmp
LAST=$(gcloud storage ls "$BUCKET/db/" | sort | tail -1)
gcloud storage cp "$LAST" /tmp/restore.sql.gz
set -a; . /opt/sigav/.env; set +a
docker exec -i sigav_db mysql -uroot -p"$DB_ROOT_PASSWORD" -e "CREATE DATABASE restore_test CHARACTER SET latin1;"
gunzip -c /tmp/restore.sql.gz | docker exec -i sigav_db mysql -uroot -p"$DB_ROOT_PASSWORD" --default-character-set=latin1 restore_test
docker exec sigav_db mysql -uroot -p"$DB_ROOT_PASSWORD" restore_test -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='restore_test';"
```
Expected: `53` (la restauración reproduce el esquema).

- [ ] **Step 2: Limpiar la base de prueba**

```bash
docker exec sigav_db mysql -uroot -p"$DB_ROOT_PASSWORD" -e "DROP DATABASE restore_test;"
```

---

### Task D3: Checklist de verificación final

- [ ] `https://$DOMAIN/login.php` responde 200 con certificado válido.
- [ ] Login legacy y de Laravel funcionan; se puede cargar un producto y navegar.
- [ ] `https://$DOMAIN/AFIP/key/...` devuelve **403** (claves protegidas).
- [ ] SSH público cerrado: `nc -vz -w3 $DOMAIN 22` falla / timeout (solo IAP).
- [ ] MySQL no expuesto: `nc -vz -w3 $DOMAIN 3306` falla.
- [ ] `APP_DEBUG=false` en `.env`.
- [ ] Snapshot diario configurado (`gcloud compute disks describe "$VM_NAME" --zone="$ZONE" --format='value(resourcePolicies)'` lista `sigav-daily`).
- [ ] Backup en el bucket + restauración probada (Task D2).

---

## Notas de operación (deploy de cambios futuros)

```bash
cd /opt/sigav
git pull
docker run --rm -v "$PWD":/app -w /app composer:2 install --no-dev --optimize-autoloader --ignore-platform-reqs   # si cambió composer.lock
docker run --rm -v "$PWD":/app -w /app node:14 sh -c "npm ci && npm run prod"                                      # si cambiaron assets
docker compose -f docker-compose.prod.yml restart app
```
> Solo correr `php artisan migrate --force` si en el futuro se agregan migraciones nuevas Y la tabla `migrations` está poblada coherentemente. Con el arranque por dump, la tabla `migrations` está vacía: evaluar marcar el baseline antes de migrar.

## Follow-ups (post-deploy, fuera de alcance)

- Rate limiting / `fail2ban` en `/login.php`; migrar hashing legacy SHA1 → bcrypt.
- Activar proxy de Cloudflare (DNS-01 + token API + SSL Full strict) para CDN/DDoS.
- Evaluar Cloud SQL gestionado si crece la operación.
