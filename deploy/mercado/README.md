# Mercado Artesanal en la VM de SIGAV — runbook

Segunda instancia de SIGAV v2 en la misma VM que Acantilado Sur (`sigav-a`,
`southamerica-east1-a`, IP `35.198.36.159`). Spec y decisiones:
`docs/superpowers/specs/2026-09-13-mercado-artesanal-actualizacion-design.md`.

Decisiones del operator (2026-09-14):
- **DB compartida**: base `mercado` dentro del MySQL de Acantilado (`sigav_db`,
  MySQL 5.7 con `character-set-server=latin1`), con usuario propio `mercado`
  limitado a esa base. La VM (e2-small, 2 GB) no tiene RAM para un segundo MySQL.
- **Charset**: la base de Mercado guarda bytes UTF-8 dentro de columnas latin1
  (herencia del POS legacy, que conecta sin `set_charset`). Por eso el `.env`
  lleva `DB_CHARSET=latin1` / `DB_COLLATION=latin1_swedish_ci`: Laravel lee y
  escribe los bytes tal cual. Con utf8mb4 los acentos se ven como `NIÃ‘EZ`.
- **Alias de pre-producción** `mercado-artesanal.sigav.ar` (A en Cloudflare,
  DNS only → 35.198.36.159) para probar con HTTPS real antes de apuntar
  `sistema.mercado-artesanal.com.ar`.
- `netsuite_proxy.php` y `limpiar_cache.php` del hosting viejo **no se copian**.

## Archivos

| Archivo | Para qué |
|---|---|
| `docker-compose.mercado.yml` | Servicio `mercado_app` (misma imagen `sigav-app:prod`), sin DB propia, en la red externa `sigav_sigav` donde viven `sigav_caddy` y `sigav_db` |
| `.env.mercado.example` | Plantilla del `.env` de la instancia (Laravel + compose) |
| `01-esquema-desde-8d14505.sql` | Lleva la base del hosting (master@8d14505) al esquema actual y registra las migraciones. Idempotente. |
| `02-datos-condicion-iva.sql` | Saneo de `clientes.condicion_iva` (RG 5616) y listado de precios no numéricos a corregir a mano |
| `03-unificar-convencion-utf8.sql` | Una sola vez: pasa las columnas con acentos de las tablas utf8mb4 (creadas por Laravel en Ferozo) a la convención de bytes del resto de la base, para que se lean bien con la conexión latin1. Deja marca en `charset_convencion_aplicada`. |
| `../Caddyfile` | Bloque `sistema.mercado-artesanal.com.ar, mercado-artesanal.sigav.ar` → `mercado_app:80` |
| `../backup.sh` | Ya incluye la base `mercado` y los archivos de `/opt/mercado` |

## Qué hace falta del hosting Ferozo (antes del corte)

Con el sitio de Ferozo **en mantenimiento** (para que no entren ventas nuevas):

1. Dump fresco: en phpMyAdmin exportar `c2101314_ma` completo (SQL, con
   `DROP TABLE` y datos). El export de phpMyAdmin declara `SET NAMES utf8mb4`
   y trae los bytes ya "dobles": importarlo tal cual reproduce exactamente lo
   que hay en el hosting.
2. Archivos (tar/zip desde el administrador de archivos o FTP):
   `storage/` entero (incluye `storage/oauth-private.key` y
   `storage/oauth-public.key`, sin los cuales los tokens del API dejan de
   validar), `public/AFIP/cert`, `public/AFIP/key`, `public/assets/perfil/`,
   `public/assets/sucursales/`, `public/productos/`, `public/facturas/`,
   `public/presupuesto/`, `public/clientes/`, `public/branchs/`,
   `public/notas_credito/`, `public/cobros/`, `upload_articles/`.
3. El `.env` del hosting: solo hace falta el valor de `APP_KEY`.

## Deploy en la VM

### 0. Pre-flight de recursos

```bash
free -m; df -h /; sudo docker stats --no-stream --format '{{.Name}} {{.MemUsage}}'
```
Seguir solo con ≥ 700 MB disponibles y ≥ 5 GB de disco libre. `mercado_app`
corre con `mem_limit: 384m`; si la VM queda justa, subir a e2-medium antes.

Todo desde la VM (`gcloud compute ssh sigav-a --zone=southamerica-east1-a --tunnel-through-iap`).

### 1. Base y usuario en el MySQL compartido

```bash
# La password de root se toma del propio contenedor (la del .env de /opt/sigav
# no coincide con la del volumen). La de Mercado queda en /root/mercado_db_pw.
openssl rand -hex 24 | sudo tee /root/mercado_db_pw >/dev/null && sudo chmod 600 /root/mercado_db_pw
MPW="$(sudo cat /root/mercado_db_pw)"
printf "CREATE DATABASE IF NOT EXISTS mercado CHARACTER SET latin1 COLLATE latin1_swedish_ci;\nCREATE USER IF NOT EXISTS 'mercado'@'%%' IDENTIFIED BY '%s';\nGRANT ALL PRIVILEGES ON mercado.* TO 'mercado'@'%%';\nFLUSH PRIVILEGES;\n" "$MPW" \
  | sudo docker exec -i sigav_db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD"'
```

Para correr SQL como root en los pasos siguientes usar siempre la forma
`sudo docker exec -i sigav_db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" mercado' < archivo.sql`.

### 2. Código

```bash
sudo git clone -b prod-mercado-artesanal https://github.com/jmarroni/sigav_v2.git /opt/mercado   # repo público; la VM no tiene deploy key
sudo git config --global --add safe.directory /opt/mercado   # el clon es de root
cd /opt/mercado
sudo cp deploy/mercado/.env.mercado.example .env
sudo nano .env    # APP_KEY (el de Ferozo), DB_PASSWORD y LEGACY_DB_PASS (el generado), LEGACY_SEMILLA (misma que Acantilado, entre comillas simples), API_SECRET_KEY nuevo, MAIL_* si aplica
sudo chown root:www-data .env && sudo chmod 640 .env
sudo chown -R www-data:www-data storage bootstrap/cache
```

El `.env` entra al contenedor por `env_file` (sin interpolación de Compose), así
que los valores con `$` o `#` viajan tal cual. Verificación obligatoria tras
levantar el contenedor (paso 5): el hash de la semilla debe coincidir con el de
Acantilado.

```bash
sudo docker exec mercado_app sh -c 'printf %s "$LEGACY_SEMILLA"' | sha1sum
sudo grep -E '^LEGACY_SEMILLA=' /opt/sigav/.env | cut -d= -f2- | sed -E "s/^'(.*)'$/\1/" | tr -d '\n' | sha1sum
```

**Artefactos trackeados por error.** El repo trackea PDFs e imágenes de
Acantilado en `public/presupuesto`, `public/upload_articles`, `public/clientes`,
`public/branchs`, `public/cobros` y `public/assets/sucursales` (aunque están en
`.gitignore`). En el checkout de Mercado hay que vaciarlos y decirle a git que
ignore sus cambios, si no `git pull` se niega y el sitio sirve archivos del
otro negocio:

```bash
cd /opt/mercado
for d in public/presupuesto public/upload_articles public/clientes public/branchs public/cobros public/assets/sucursales; do
  sudo git ls-files -z "$d" | sudo xargs -0 git update-index --skip-worktree
  sudo find "$d" -type f ! -name '.gitkeep' ! -name 'index.php' -delete
done
```
(Untrackearlos en el repo es lo correcto, pero borraría esos archivos en la VM de
Acantilado en el próximo `pull`; queda para un cambio dedicado.)

`vendor/` no está en git: instalar las dependencias con el `composer.phar`
versionado, dentro del contenedor (paso 5, después de levantarlo). `public/vendor`
(tcpdf, html2pdf, afipsdk) sí viene en el repo.

La imagen es la misma que Acantilado (`sigav-app:prod`, ya construida en la VM).
Si en algún momento se rebuildéa, hacerlo desde `/opt/sigav` con
`docker compose -f docker-compose.prod.yml build app`; ambas instancias la comparten.

### 3. Importar datos y aplicar el esquema

```bash
# dump fresco copiado a /opt/mercado/dump.sql (scp vía IAP desde la PC)
# El export de phpMyAdmin NO debe traer CREATE DATABASE / USE: si los trae, los
# datos caen en otra base y la app arranca vacía.
grep -inE '^(CREATE DATABASE|USE )' dump.sql && { echo "ABORTAR: el dump trae CREATE DATABASE/USE"; false; }
M='sudo docker exec -i sigav_db sh -c '"'"'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" mercado'"'"''
eval "$M" < dump.sql
eval "$M" < deploy/mercado/01-esquema-desde-8d14505.sql
eval "$M" < deploy/mercado/01-esquema-desde-8d14505.sql   # 2ª vez: mismo resumen, sin errores
eval "$M" < deploy/mercado/02-datos-condicion-iva.sql
eval "$M" < deploy/mercado/03-unificar-convencion-utf8.sql   # una sola vez por importación (si se reimporta el dump, se vuelve a correr: la marca viaja con la base)
sudo rm dump.sql
```

### 4. Archivos del hosting

Descomprimir el tar de Ferozo sobre `/opt/mercado` respetando rutas, y luego:

```bash
cd /opt/mercado
sudo mkdir -p storage/app/afip/prod storage/app/afip/homo
sudo mv public/AFIP/cert storage/app/afip/prod/cert && sudo mv public/AFIP/key storage/app/afip/prod/key   # master ya no lee public/AFIP
sudo chown -R www-data:www-data storage public/assets/perfil public/assets/sucursales public/productos public/presupuesto public/facturas public/clientes public/branchs public/notas_credito public/cobros
sudo chmod -R 750 storage/app/afip
```

### 5. Levantar la app y Caddy

```bash
cd /opt/mercado
sudo docker compose -f deploy/mercado/docker-compose.mercado.yml --env-file .env up -d
sudo docker exec -u root mercado_app sh -c 'cd /var/www/html && COMPOSER_ALLOW_SUPERUSER=1 php composer.phar install --no-dev --no-interaction --prefer-dist --optimize-autoloader'
sudo chown -R www-data:www-data vendor bootstrap/cache storage
sudo docker exec mercado_app php artisan config:clear && sudo docker exec mercado_app php artisan route:clear && sudo docker exec mercado_app php artisan view:clear
sudo docker exec mercado_app php artisan migrate --pretend --force   # APP_ENV=production pide confirmación: --force. Debe decir "Nothing to migrate."
[ -e public/storage ] || sudo docker exec mercado_app php artisan storage:link
sudo docker exec mercado_app php artisan tinker --execute="echo config('app.key') ? 'APP_KEY ok' : 'FALTA APP_KEY';"

# Caddy es el de Acantilado. El Caddyfile de la VM (/opt/sigav/deploy/Caddyfile)
# es el que manda: agregarle el bloque de Mercado (copiarlo de este repo), NO
# pisarlo con checkout (la VM tiene bloques que el repo puede no tener).
awk '/^# Mercado Artesanal — segunda instancia/{p=1} p' /opt/mercado/deploy/Caddyfile | sudo tee -a /opt/sigav/deploy/Caddyfile
sudo docker exec sigav_caddy caddy validate --config /etc/caddy/Caddyfile
sudo docker exec sigav_caddy caddy reload --config /etc/caddy/Caddyfile
```

`https://mercado-artesanal.sigav.ar/login.php` debe responder 200 con certificado válido.

### 6. AFIP producción

En `https://mercado-artesanal.sigav.ar/afip/configuracion` (usuario con rol 5):
sección Producción → completar CUIT, punto de venta, comprobante, condición IVA,
inicio de actividades e ingresos brutos de Mercado; los archivos cert/key ya
están en `storage/app/afip/prod/` (o pegarlos ahí mismo); **Probar conexión**;
activar Producción solo cuando se vaya a facturar de verdad. Hasta entonces el
badge en `/ventas` dice "homo" y las facturas van a homologación.

### 7. Smoke test por el alias (antes del DNS)

- Login de 2 usuarios reales (sha1) y `/carga`, `/ventas.php`, `/pedido`, reportes.
- `/notas/credito` y `/notas/debito` muestran el histórico.
- Acentos: un cliente con ñ en `/cliente` y en el buscador de `/ventas.php`.
- Reimpresión de una factura vieja (PDF en `public/facturas`).
- Mail saliente desde una factura (si `MAIL_*` está cargado).
- `POST /api/auth/login` con un usuario de `users` y un token pre-existente de
  `oauth_access_tokens` contra `/api/auth/productos` (documentar paridad con Ferozo).
- AFIP prod: solo "Probar conexión".

### 8. Cambio de DNS y punto de no retorno

1. Bajar el TTL de `sistema.mercado-artesanal.com.ar` a 300 s con 24 h de anticipación.
2. Ferozo en mantenimiento → dump y archivos frescos → reimportar (pasos 3 y 4;
   la base se puede `DROP DATABASE mercado` y recrear antes de importar).
3. Apuntar el A a `35.198.36.159`. En `/opt/sigav/deploy/Caddyfile` cambiar la
   cabecera del bloque a `sistema.mercado-artesanal.com.ar, mercado-artesanal.sigav.ar {`,
   `caddy validate` y `caddy reload`. Verificar `https://sistema.mercado-artesanal.com.ar/login.php`.
4. **Mientras no haya ventas ni comprobantes AFIP en la base nueva**, el rollback
   es volver el DNS y sacar el mantenimiento en Ferozo. La primera factura real es
   el punto de no retorno: desde ahí se corrige hacia adelante y Ferozo queda en
   mantenimiento fijo.
5. Convertir el alias en redirección para no servir el sitio en dos nombres:
   en el Caddyfile, un bloque `mercado-artesanal.sigav.ar { redir https://sistema.mercado-artesanal.com.ar{uri} permanent }`
   y sacar el alias de la cabecera del bloque principal; `caddy reload`.
6. A los 7 días estables: borrar `netsuite_proxy.php` y `limpiar_cache.php` del
   hosting, rotar la password de la DB de Ferozo y las credenciales NetSuite.

## Reimportar la base desde un dump nuevo (hecho el 2026-09-17)

El dump de phpMyAdmin trae `CREATE DATABASE`/`USE` y no trae `DROP TABLE`, así
que se limpia y se recrea la base. La config AFIP/MP se preserva porque no
viene del hosting:

```bash
# PC: quitar las 2 líneas de CREATE DATABASE/USE y subir
grep -vE '^(CREATE DATABASE IF NOT EXISTS `c2101314_ma`|USE `c2101314_ma`;)' c2101314_ma.sql > dump.sql
gcloud compute scp --zone=southamerica-east1-a --tunnel-through-iap dump.sql sigav-a:/tmp/
# VM
cd /opt/mercado; TS=$(date +%Y%m%d-%H%M%S); M() { sudo docker exec -i sigav_db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" '"$1"; }
sudo sh -c "docker exec sigav_db sh -c 'mysqldump -uroot -p\"\$MYSQL_ROOT_PASSWORD\" --default-character-set=latin1 mercado' > /root/mercado_backups/mercado-pre-reimport-$TS.sql"
sudo sh -c "docker exec sigav_db sh -c 'mysqldump -uroot -p\"\$MYSQL_ROOT_PASSWORD\" --no-create-info --default-character-set=latin1 mercado afip_config mercadopago_config' > /root/mercado_backups/afip_mp_config-$TS.sql"
printf 'DROP DATABASE mercado; CREATE DATABASE mercado CHARACTER SET latin1 COLLATE latin1_swedish_ci;\n' | M ''
M mercado < /tmp/dump.sql && sudo rm /tmp/dump.sql
M mercado < deploy/mercado/01-esquema-desde-8d14505.sql
M mercado < deploy/mercado/02-datos-condicion-iva.sql
M mercado < deploy/mercado/03-unificar-convencion-utf8.sql
printf 'DELETE FROM afip_config; DELETE FROM mercadopago_config;\n' | M mercado
sudo cat /root/mercado_backups/afip_mp_config-$TS.sql | M mercado
sudo docker exec mercado_app php artisan config:clear && sudo docker exec mercado_app php artisan migrate --pretend --force
```
Los privilegios del usuario `mercado` sobreviven al `DROP DATABASE`.

## Deploy de cambios futuros

```bash
cd /opt/mercado && sudo git pull
sudo docker exec mercado_app php artisan config:clear && sudo docker exec mercado_app php artisan route:clear && sudo docker exec mercado_app php artisan view:clear
sudo docker exec mercado_app php artisan migrate --force   # ahora sí funciona: migrations está completa
# si cambió composer.lock: repetir el composer install del paso 5
```

## Deploy inicial realizado (2026-09-14)

Hecho en la VM con el dump de **febrero 2026** para dejar el sitio funcional
mientras llega el dump fresco: base `mercado` + usuario, clon en `/opt/mercado`
(rama `prod-mercado-artesanal`), `.env` con el `APP_KEY` de Ferozo, esquema
aplicado (idempotencia verificada), cert/key AFIP de febrero en
`storage/app/afip/prod/` (entorno prod **inactivo**, homo activo), contenedor
`mercado_app` arriba, bloque agregado al Caddyfile de la VM y recargado.
Pendiente: alias DNS en Cloudflare → certificado; reimportar con datos frescos;
cargar datos AFIP prod en la pantalla; cutover DNS.

Datos con problemas detectados en el dump (los lista `02-datos-condicion-iva.sql`):
varios productos tienen una **fecha** (`2025-07-02`) en `precio_mayorista` y tres
tienen precios con puntos de miles (`1.200.000`). Corregir desde `/carga`.

## AFIP producción: TLS con `servicios1.afip.gov.ar`

El WSFE de producción negocia Diffie-Hellman de 1024 bits y OpenSSL 1.1.1
(SECLEVEL=2 por defecto en la imagen) lo rechaza con `dh key too small`; la
prueba de conexión autentica en WSAA pero falla en WSFE con "SOAP Fault: HTTP".
Homologación no lo sufre. Solución: `deploy/openssl-afip.cnf` montado en el
contenedor y `OPENSSL_CONF` apuntando ahí (ya en ambos compose). Verificar:

```bash
sudo docker exec mercado_app curl -s -o /dev/null -w '%{http_code}\n' https://servicios1.afip.gov.ar/wsfev1/service.asmx?WSDL   # 200
```

Las credenciales productivas reales de Mercado estaban en
`public/vendor/afipsdk/afip.php/src/Afip_res/{cert,key}` del hosting (no en
`public/AFIP/`, que contenía un CSR de homologación). Certificado CUIT
30715251988, **vence el 13-nov-2026**: renovar antes en AFIP.

## Pendiente conocido

- `pedidos_legacy` conserva 3 filas de 2019; el flujo legacy `public/pedidos*.php` apunta ahí y es candidato a borrar.
- Guard `api` con driver `token` (preexistente): el API OAuth puede no validar tokens. Paridad con Ferozo, no regresión.
- Productos con precio como texto (`1.200.000`) o con una fecha en `precio_mayorista`: los lista `02-datos-condicion-iva.sql`; el reporte de stock los marca con ⚠ y los excluye del total. Corregir desde `/carga`.
- `public/ventas_post.php` fuerza `mysqli_set_charset("utf8")` (también en Ferozo): el alta de productos libres desde el POS escribe con otra convención de bytes que el resto. Paridad, no regresión; revisar en Fase 4.
- La imagen `sigav-app:prod` es compartida con Acantilado y se construye desde `/opt/sigav`; si los Dockerfiles divergen, etiquetar por tenant.
