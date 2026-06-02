# Deploy de SIGAV v2 a Google Cloud (VM única) — Diseño

**Fecha:** 2026-06-02
**Estado:** Aprobado para planificar
**Alcance:** Llevar SIGAV v2 (app híbrida Laravel 7 + PHP legacy) a producción en una única VM de Google Compute Engine, de forma económica y operable por una sola persona.

---

## 1. Contexto y restricciones

- App **híbrida**: Laravel 7 (Eloquent, Passport) + ~88 scripts PHP legacy bajo `public/`, compartiendo la misma base MySQL.
- **Escribe archivos al filesystem** en runtime: certs/logs de AFIP (`public/AFIP/...`), PDFs (`public/presupuesto`, `public/notas_credito`, `public/branchs`, …), imágenes de productos (intervention/image), y `storage/` de Laravel. **Esto descarta serverless (Cloud Run) sin un refactor grande**, por lo que se usa una VM con disco persistente.
- Ya está **dockerizada** (`Dockerfile` PHP 7.4 + Apache; `docker-compose.yml` con MySQL 5.7).
- Perfil acordado: **producción real**, escala **chica (1-3 sucursales, ~10-15 usuarios concurrentes)**, **operada por el dueño vía VM**, **costo mínimo viable** (DB en la misma VM). Dominio propio **disponible**.

## 2. Decisión de arquitectura

**Una sola VM de Compute Engine** corriendo Docker Compose de producción con 3 contenedores: reverse proxy (Caddy) → app (PHP/Apache) → MySQL. Datos y archivos en **disco persistente**; backups por **snapshots de disco** + **dump lógico a Cloud Storage**.

Se descartaron: Cloud SQL (suma costo, va contra "DB en la VM"; queda como upgrade futuro) y Cloud Run (filesystem efímero incompatible con las escrituras del legacy).

## 3. Topología

```
                 Internet
                    │  (HTTPS 443 / HTTP 80→redir)
                    ▼
        ┌───────────────────────────┐
        │  VM Compute Engine         │  Debian 12, southamerica-east1
        │  e2-small (2 vCPU, 2GB)    │  IP estática externa
        │  disco pd-balanced 30GB    │
        │                            │
        │   Docker Compose (prod)    │
        │   ┌─────────┐              │
        │   │  caddy  │ :80/:443     │  TLS automático (Let's Encrypt)
        │   └────┬────┘              │
        │        │ proxy interno     │
        │   ┌────▼────┐              │
        │   │   app   │ :80 interno  │  imagen Dockerfile (PHP 7.4 + Apache)
        │   └────┬────┘              │  código bind-mount desde el disco
        │        │ red docker        │
        │   ┌────▼────┐              │
        │   │   db    │ MySQL 5.7    │  volumen en disco persistente
        │   └─────────┘              │  sin puerto público
        └───────────────────────────┘
```

## 4. Infraestructura GCP

| Recurso | Valor |
|---|---|
| Región / zona | `southamerica-east1` (São Paulo), zona `-b` |
| VM | `e2-small` (escalable a `e2-medium` en caliente si hace falta) |
| Disco | 30 GB `pd-balanced` (boot + datos) |
| SO | Debian 12 |
| IP | Estática externa, reservada |
| Firewall | `80`, `443` desde `0.0.0.0/0`; **SSH (22) solo vía IAP** (sin 22 público); `3306` nunca expuesto |
| Snapshots | Política de snapshot diaria del disco, retención 14 días |
| Storage | 1 bucket Nearline en misma región para dumps, lifecycle 30 días |

## 5. Empaquetado de la app (producción)

Para no romper las escrituras del legacy ni "cazar" cada carpeta escribible, **el código vive en el disco de la VM y se monta en el contenedor** (estilo dev, pero con árbol buildeado para prod):

- `git clone` del repo en `/opt/sigav` en la VM.
- Build de dependencias **vía contenedores one-shot** (no se ensucia el host ni se modifica la imagen de la app), montando `/opt/sigav`:
  - `docker run ... composer:2 install --no-dev --optimize-autoloader` (imagen oficial `composer`, con PHP 7.x compatible).
  - `docker run ... node:14 sh -c "npm ci && npm run prod"` (Laravel Mix 5 / webpack).
  - `php artisan config:cache` **sí**. **`route:cache` NO**: `routes/web.php` define rutas con closures (p. ej. `/`), y el cacheo de rutas de Laravel falla ante closures.
- `docker-compose.prod.yml` con:
  - **app**: build del `Dockerfile` actual, `volumes: ./:/var/www/html`, `restart: always`, sin puertos publicados (solo expone a la red interna).
  - **db**: `mysql:5.7`, volumen nombrado `sigav_db_data`, `restart: always`, `MYSQL_ROOT_PASSWORD` fuerte + usuario `sigav` acotado; sin `ports`.
  - **caddy**: `restart: always`, publica `80`/`443`, `Caddyfile` con `reverse_proxy app:80` y `tls` automático para el dominio; volúmenes para `caddy_data`/`caddy_config` (persistir certificados).
- **`.env` de producción**: `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` y `API_SECRET_KEY` generados, `DB_*` apuntando al servicio `db`, `FRONTEND_URL` con el dominio real. El legacy (`public/conection.php`) lee `LEGACY_DB_*` por env (ya soportado).
- **TrustProxies**: configurar `App\Http\Middleware\TrustProxies` para confiar en el proxy (Caddy) y que Laravel genere URLs `https`. Asegurar que los redirects legacy (`header('Location: ...')`) funcionen detrás de TLS.

## 6. Persistencia y datos

- **MySQL**: volumen Docker `sigav_db_data` sobre el disco persistente.
- **Archivos de la app** (AFIP, PDFs, imágenes, `storage/`): viven en `/opt/sigav` (el repo montado) sobre el disco persistente.
- Permisos: el usuario de Apache del contenedor (`www-data`) debe poder escribir `storage/`, `bootstrap/cache/` y las carpetas de `public/` que generan archivos.
- **Carga inicial de datos**: importar el dump actual a MySQL; subir certs de AFIP de producción a `public/AFIP/{cert,key}` (no versionados).

## 7. HTTPS y dominio

- Registro **A** del dominio → IP estática de la VM.
- Caddy emite y renueva el certificado Let's Encrypt automáticamente; redirige `http → https`.

## 8. Backups y recuperación

1. **Snapshots de disco** (política GCP, sin scripting): diario, retención 14 días. Restauración: crear disco desde snapshot y arrancar VM. Cubre DB + archivos + config.
2. **Dump lógico a GCS** (cron nocturno en la VM):
   - `mysqldump` (gzip) de la base.
   - `tar` de las carpetas escribibles (`public/AFIP`, imágenes, `presupuesto`, `notas_credito`, `branchs`, `storage`, …).
   - `gcloud storage cp` al bucket Nearline; lifecycle borra a los 30 días.
   - Da un backup **portable** (descargable / migrable a otro proveedor).

## 9. Seguridad (exposición a internet)

**Bloqueante antes de exponer:**
- **Proteger claves AFIP**: `public/AFIP/key/` (y `cert/`) están dentro del docroot → hoy serían descargables por URL. Bloquear acceso web (regla Apache/Caddy `Require all denied` sobre esas rutas) o reubicar fuera de `public/`. **Imprescindible.**
- `APP_DEBUG=false` (no filtrar stack traces).
- `Options -Indexes` en Apache (sin listado de directorios).
- MySQL: password root fuerte, usuario `sigav` con privilegios acotados, sin puerto público.
- SSH solo por **IAP** (sin 22 abierto a internet).

**Follow-up (no bloqueante, anotado):**
- Login legacy usa **SHA1 + SEMILLA**; sin rate limiting. Recomendado sumar `fail2ban` o rate limit en `/login.php` y, a futuro, migrar a `password_hash()`.
- Revisar headers de seguridad (HSTS, X-Content-Type-Options) en Caddy.

## 10. Flujo de operación

- **Provisión (una vez)**: `gcloud` para VM + IP estática + reglas de firewall + política de snapshots + bucket.
- **Bootstrap**: instalar Docker + plugin compose; `git clone`; build de assets; `.env`; importar dump; `php artisan migrate` + `passport:install`; `php artisan storage:link`; `docker compose -f docker-compose.prod.yml up -d`.
- **Deploy de cambios**: `git pull` → `composer install --no-dev` / `npm run prod` si cambió → `php artisan migrate` → `docker compose ... restart app`.

## 11. Costo estimado (mensual, aprox.)

| Ítem | USD/mes |
|---|---|
| VM e2-small | ~13-17 |
| Disco 30GB pd-balanced | ~3 |
| IP estática (en uso) | 0 |
| Snapshots (14 días) | ~1-2 |
| Bucket Nearline (dumps) | <1 |
| **Total** | **~20-25** |

## 12. Fuera de alcance (este spec)

- Alta disponibilidad / multi-zona / autoscaling.
- Migración de la DB a Cloud SQL (posible upgrade futuro).
- Refactor del legacy para almacenamiento de archivos en GCS.
- Migración de hashing de contraseñas legacy (SHA1 → bcrypt).
- CI/CD automatizado (los deploys son manuales por `git pull`).

## 13. Criterios de éxito

- La app responde por HTTPS en el dominio, con certificado válido.
- Login (legacy y Laravel), carga de productos, generación de PDFs y facturación AFIP funcionan con datos reales.
- Los archivos generados persisten tras reiniciar/recrear contenedores.
- Existe al menos un backup restaurable (snapshot + dump en GCS) y se probó una restauración.
- Las claves de AFIP no son accesibles por URL.
- SSH no expuesto públicamente; MySQL no expuesto.
