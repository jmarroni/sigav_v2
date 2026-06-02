# Despliegue de SIGAV en Google Cloud (con dominio propio)

Esta guía deja la aplicación **Laravel 7** corriendo en una máquina virtual de
Google Cloud (**Compute Engine**), con base de datos **MySQL**, servidor web
**Nginx + PHP-FPM** y **HTTPS gratis** (Let's Encrypt), accesible desde tu
propio dominio.

> **¿Por qué una VM y no Cloud Run / App Engine?**
> Esta app guarda archivos subidos (facturas, comprobantes, PDFs, imágenes de
> artículos) en carpetas locales dentro de `public/`. Los servicios "sin estado"
> (Cloud Run, App Engine Flexible) **borran el disco al reiniciar**, así que
> perderías esos archivos. Una VM tiene disco persistente y se comporta como un
> hosting tradicional. Es además la opción más barata para una app de un solo
> negocio.

---

## 0) Lo que TENÉS QUE DARLE a Claude Code (o tener a mano)

Completá esta lista y pegásela cuando ejecutes el despliegue. Sin estos datos no
se puede continuar.

| Dato | Ejemplo | ¿Para qué? |
|---|---|---|
| **ID del proyecto GCP** | `sigav-prod-123456` | Identifica tu proyecto en Google Cloud |
| **Facturación activada** | sí / no | GCP exige una tarjeta para crear recursos (hay capa gratuita) |
| **Región** | `southamerica-east1` (São Paulo) | Dónde vivirá el servidor (cercanía = menos latencia) |
| **Dominio** | `miempresa.com` | El dominio que vas a comprar (ver paso 1) |
| **Email** | `vos@miempresa.com` | Para el certificado SSL de Let's Encrypt |
| **Contraseña de la base de datos** | (elegí una fuerte) | Se crea el usuario MySQL `sigav` |
| **Credenciales SMTP** | host/usuario/pass del correo | Para que la app mande emails (facturas, etc.) |
| **Credenciales Mercado Libre** | App ID / Secret | Si vas a usar la integración de ML |

> Las contraseñas y secretos **no se commitean** al repo. Se cargan a mano en el
> archivo `.env` del servidor (ver paso 6).

---

## 1) Conseguir un dominio (todavía no lo tenés)

Tenés dos caminos:

- **Opción A — Comprarlo dentro de Google (Cloud Domains):** queda todo en el
  mismo lugar y el DNS se configura solo.
  ```bash
  gcloud domains registrations register TU-DOMINIO.com
  ```
- **Opción B — Comprarlo en otro registrador** (Namecheap, GoDaddy, Hostinger,
  nic.ar, etc.): suele ser más barato. Después solo hay que apuntar un registro
  DNS de tipo **A** a la IP del servidor (paso 7).

> Si no tenés preferencia, la Opción A es la más simple porque evita tocar DNS a
> mano.

---

## 2) Requisitos previos en tu compu

Instalá el SDK de Google Cloud (`gcloud`) y autenticate:

```bash
# Instalar gcloud: https://cloud.google.com/sdk/docs/install
gcloud auth login
gcloud config set project TU_PROJECT_ID
gcloud services enable compute.googleapis.com
```

---

## 3) Crear la máquina virtual

```bash
gcloud compute instances create sigav-vm \
  --zone=southamerica-east1-a \
  --machine-type=e2-small \
  --image-family=ubuntu-2204-lts \
  --image-project=ubuntu-os-cloud \
  --boot-disk-size=20GB \
  --tags=http-server,https-server

# Abrir puertos 80 (HTTP) y 443 (HTTPS)
gcloud compute firewall-rules create allow-http \
  --allow=tcp:80 --target-tags=http-server || true
gcloud compute firewall-rules create allow-https \
  --allow=tcp:443 --target-tags=https-server || true
```

Anotá la **IP externa** que devuelve el comando (la vas a necesitar en el paso 7):

```bash
gcloud compute instances describe sigav-vm --zone=southamerica-east1-a \
  --format='get(networkInterfaces[0].accessConfigs[0].natIP)'
```

> `e2-small` (2 GB RAM) alcanza para empezar. Si la app va lenta, subí a
> `e2-medium`. El costo aproximado de `e2-small` es ~USD 13/mes.

---

## 4) Provisionar el servidor (instala todo automáticamente)

Conectate por SSH:

```bash
gcloud compute ssh sigav-vm --zone=southamerica-east1-a
```

Ya dentro de la VM, descargá y ejecutá el script de instalación que está en este
repo (`deploy/setup-server.sh`). Instala Nginx, PHP 7.4, MySQL, Composer, Node y
configura todo:

```bash
# Pegá el contenido de deploy/setup-server.sh en un archivo y ejecutalo:
sudo bash setup-server.sh
```

El script te va a pedir la **contraseña de la base de datos** que elegiste.

---

## 5) Subir el código de la aplicación

Desde dentro de la VM, cloná el repo (o subílo por `git`):

```bash
cd /var/www
sudo git clone https://github.com/jmarroni/sigav_v2.git sigav
sudo chown -R www-data:www-data /var/www/sigav
cd /var/www/sigav

# Instalar dependencias de producción
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data npm install && sudo -u www-data npm run prod
```

---

## 6) Configurar el entorno (`.env`)

```bash
cd /var/www/sigav
sudo -u www-data cp deploy/.env.production.example .env
sudo -u www-data php artisan key:generate
sudo nano .env   # completá DB, dominio, SMTP, Mercado Libre, etc.
```

Luego ejecutá las migraciones y optimizaciones:

```bash
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan passport:keys      # claves de Laravel Passport
```

Permisos de escritura para carpetas de archivos subidos:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache public
sudo chmod -R 775 storage bootstrap/cache
```

---

## 7) Apuntar el dominio al servidor (DNS)

En el panel DNS de tu dominio, creá un registro **A**:

| Tipo | Nombre | Valor |
|---|---|---|
| A | `@` | (la IP externa del paso 3) |
| A | `www` | (la misma IP) |

> Si compraste el dominio con **Cloud Domains** y usás **Cloud DNS**, se hace con
> `gcloud dns record-sets`. Si lo compraste en otro lado, se hace en el panel de
> ese registrador. La propagación tarda de minutos a unas horas.

---

## 8) Activar HTTPS (SSL gratis)

Una vez que el dominio resuelve a la IP, dentro de la VM:

```bash
sudo certbot --nginx -d TU-DOMINIO.com -d www.TU-DOMINIO.com
```

Certbot configura el certificado y la renovación automática. ¡Listo, tu sitio ya
está en `https://TU-DOMINIO.com`!

---

## 9) Mantenimiento y actualizaciones

Para subir cambios nuevos del repo en el futuro, usá el script `deploy/deploy.sh`:

```bash
cd /var/www/sigav && sudo bash deploy/deploy.sh
```

### Recomendaciones de seguridad
- Cambiá `APP_DEBUG=false` en `.env` (ya viene así en el ejemplo).
- Hacé backups periódicos de la base de datos: `mysqldump`.
- Considerá backups del disco con **snapshots** de Compute Engine.

---

## Resumen del flujo

```
Comprar dominio → Crear VM → setup-server.sh → Subir código →
Configurar .env → Migrar BD → Apuntar DNS → Activar SSL → ¡Online!
```
