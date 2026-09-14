# Mercado Artesanal — Fase 0: saneamiento de master — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Dejar `master` de sigav_v2 sin secretos ni datos de tenant hardcodeados, sin el choque de esquemas sobre la tabla `pedidos`, y con un guard que impida borrar el histórico de Mercado por error, para que el mismo código pueda servir a Acantilado Sur y a Mercado Artesanal.

**Architecture:** El lado legacy (`public/*.php`) gana un único archivo de helpers puros (`public/legacy_config.php`) que lee configuración por `getenv()` y falla explícito si falta; `conection.php`, `header.php` y los dos scripts de mail lo consumen. Los datos de tenant (URLs de imagen fallback, CUIT, logo) pasan a derivarse del host de la request, de `afip_config` o de `perfil`. Una migración guardada archiva la tabla `pedidos` legacy como `pedidos_legacy` y garantiza la tabla con el esquema Laravel. Un test de regresión escanea el código buscando literales de Mercado para que no vuelvan.

**Tech Stack:** PHP 7.4 (Docker `sigav_app`), Laravel 7.30, PHPUnit 8.5 con SQLite en memoria (`phpunit.xml`), MySQL 5.7 (`sigav_db`) para verificación manual, Apache `mod_php` con `PassEnv`.

**Spec:** `docs/superpowers/specs/2026-09-13-mercado-artesanal-actualizacion-design.md` (secciones 2.2, 2.4 y Fase 0 del plan por fases).

## Global Constraints

- PHP `^7.2.5` (sin sintaxis 8.x: nada de `match`, `?->`, enums, named arguments, `str_contains`).
- Laravel `^7.0`; Passport `~9.0`.
- Todo cambio en `public/*.php` que toque una query usa `mysqli_prepare` para los valores nuevos; no se agregan interpolaciones nuevas.
- No se hardcodea ningún valor de tenant ni credencial. Los valores viven en `.env` / variables del contenedor.
- Los tests corren dentro del contenedor: `docker compose exec app vendor/bin/phpunit` (o `docker exec sigav_app vendor/bin/phpunit`). No afirmar "tests pasan" sin ver la salida con conteo de tests.
- No se toca producción (VM GCP ni Ferozo) en esta fase.
- Convención de commits: `<tipo>(<ámbito>): <descripción>` en español, tipos `feat|fix|refactor|docs|test|chore`.
- El branch de trabajo nace de `master` (== `origin/master`), no de `chore/caddyfile-cementos-del-sur`.

---

## Mapa de archivos

| Archivo | Acción | Responsabilidad |
|---|---|---|
| `public/legacy_config.php` | Crear | Helpers puros: leer env, config DB legacy, config mail, URL de imagen fallback, clave Smartsupp |
| `tests/Unit/LegacyConfigTest.php` | Crear | Tests de los helpers (`putenv`) |
| `tests/Unit/SinDatosDeTenantHardcodeadosTest.php` | Crear | Escaneo de regresión: ningún literal de Mercado/Ferozo en el código |
| `tests/Feature/ArchivarPedidosLegacyMigrationTest.php` | Crear | Test de la migración guardada sobre SQLite |
| `tests/Feature/ImportarCatalogoGuardTest.php` | Crear | Test del guard de `catalogo:importar` |
| `public/conection.php` | Modificar | Sin fallback de credenciales; `SEMILLA` desde env |
| `public/header.php:555-563` | Modificar | Smartsupp solo si hay clave en env |
| `public/enviar_por_mail.php:44-56` | Modificar | SMTP y From desde env / `perfil` |
| `public/enviar_por_mail_pedido.php:44-61` | Modificar | Ídem + quitar `mail()` de debug a un Gmail personal |
| `public/search.php:26,43`, `public/ventas_post.php:73`, `public/facturar.php:124` | Modificar | Imagen fallback relativa al host |
| `app/Http/Controllers/ProductoController.php:500` | Modificar | Imagen fallback con `asset()` |
| `app/Http/Controllers/Api/SucursalesController.php:58-62` | Modificar | URL de imagen con `url()` |
| `public/generar_facturar.php`, `public/reimprimir.php`, `resources/views/login/login.php` | Borrar | Código muerto con CUIT/URLs hardcodeados (verificado: sin referencias) |
| `database/migrations/2026_09_14_000000_archivar_pedidos_legacy.php` | Crear | Renombra `pedidos` legacy → `pedidos_legacy` y asegura el esquema Laravel |
| `public/pedidos.php:48,259`, `public/pedidos_post.php:21,35,49`, `public/pedidos_optica.php:12` | Modificar | Apuntar el flujo legacy a `pedidos_legacy` / `pedidos_optica` |
| `app/Console/Commands/ImportarCatalogo.php:35` | Modificar | Guard `CATALOGO_IMPORTAR_PERMITIDO` |
| `config/app.php` | Modificar | Clave `catalogo_importar_permitido` |
| `.env.example`, `docker-compose.yml`, `deploy/.env.production.example`, `deploy/afip-protect.conf` | Modificar | Nuevas variables y `PassEnv` |
| `CLAUDE.md`, `deploy/README.md` | Modificar | Documentar el cambio de contrato |

---

### Task 0: Branch limpio y baseline de tests

**Files:**
- Ninguno del repo; solo git y Docker.

- [ ] **Step 1: Crear el branch desde master**

```bash
cd /home/juan/sitios/sigav_v2
git fetch origin
git checkout -b feat/mercado-fase0-saneamiento origin/master
git log --oneline -1   # debe mostrar el tip de origin/master
```

Nota: el working tree tiene PDFs modificados en `public/presupuesto/` y varios untracked. No agregarlos a ningún commit de este plan (`git add` siempre con rutas explícitas).

- [ ] **Step 2: Levantar el entorno dev y correr la suite**

```bash
docker compose up -d --build app
docker compose exec app vendor/bin/phpunit
```

Expected: `OK (N tests, M assertions)` con N > 0. Anotar N para comparar al final. Si `.env` no tiene `LEGACY_SEMILLA`, agregarla ahora con el mismo valor que `define('SEMILLA', ...)` en `public/conection.php:2` (sin commitear `.env`).

- [ ] **Step 3: Verificar que el login legacy responde**

```bash
curl -s -o /dev/null -w '%{http_code}\n' http://localhost:8080/login.php
```

Expected: `200`.

---

### Task 1: Helpers de configuración legacy (`public/legacy_config.php`)

**Files:**
- Create: `public/legacy_config.php`
- Test: `tests/Unit/LegacyConfigTest.php`

**Interfaces:**
- Produces:
  - `legacy_env(string $name): ?string` — `getenv($name)`; devuelve `null` si no existe o es cadena vacía.
  - `legacy_env_requerida(array $names): array` — devuelve `[nombre => valor]`; lanza `RuntimeException("Faltan variables de entorno: A, B")` si alguna falta.
  - `legacy_db_config(): array` — `['host','user','pass','name']` desde `LEGACY_DB_HOST/USER/PASS/NAME` (las cuatro requeridas).
  - `legacy_mail_config(): array` — `['host','port','username','password','encryption','from_address','from_name']` desde `MAIL_HOST` (req), `MAIL_PORT` (default `'587'`), `MAIL_USERNAME` (req), `MAIL_PASSWORD` (req), `MAIL_ENCRYPTION` (default `'tls'`), `MAIL_FROM_ADDRESS` (default = username), `MAIL_FROM_NAME` (default `'SIGAV'`).
  - `legacy_imagen_default(string $host): string` — `"//{$host}/assets/img/photos/no-image-featured-image.png"` (protocol-relative: sirve en http y https).
  - `legacy_smartsupp_key(): ?string` — `legacy_env('SMARTSUPP_KEY')`.

- [ ] **Step 1: Escribir el test que falla**

```php
<?php
// tests/Unit/LegacyConfigTest.php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class LegacyConfigTest extends TestCase
{
    private $vars = [
        'LEGACY_DB_HOST', 'LEGACY_DB_USER', 'LEGACY_DB_PASS', 'LEGACY_DB_NAME',
        'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_ENCRYPTION',
        'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME', 'SMARTSUPP_KEY',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__.'/../../public/legacy_config.php';
        foreach ($this->vars as $v) {
            putenv($v); // limpia
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->vars as $v) {
            putenv($v);
        }
        parent::tearDown();
    }

    /** @test */
    public function legacy_env_devuelve_null_si_falta_o_esta_vacia()
    {
        $this->assertNull(legacy_env('LEGACY_DB_HOST'));
        putenv('LEGACY_DB_HOST=');
        $this->assertNull(legacy_env('LEGACY_DB_HOST'));
        putenv('LEGACY_DB_HOST=db');
        $this->assertSame('db', legacy_env('LEGACY_DB_HOST'));
    }

    /** @test */
    public function db_config_falla_listando_las_variables_que_faltan()
    {
        putenv('LEGACY_DB_HOST=db');
        putenv('LEGACY_DB_USER=root');
        try {
            legacy_db_config();
            $this->fail('Debía lanzar RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertSame('Faltan variables de entorno: LEGACY_DB_PASS, LEGACY_DB_NAME', $e->getMessage());
        }
    }

    /** @test */
    public function db_config_completa_devuelve_las_cuatro_claves()
    {
        putenv('LEGACY_DB_HOST=db');
        putenv('LEGACY_DB_USER=root');
        putenv('LEGACY_DB_PASS=secret');
        putenv('LEGACY_DB_NAME=laravel');
        $this->assertSame(
            ['host' => 'db', 'user' => 'root', 'pass' => 'secret', 'name' => 'laravel'],
            legacy_db_config()
        );
    }

    /** @test */
    public function mail_config_aplica_defaults_y_exige_host_usuario_y_password()
    {
        putenv('MAIL_HOST=smtp.ejemplo.com');
        putenv('MAIL_USERNAME=facturacion@ejemplo.com');
        putenv('MAIL_PASSWORD=pw');
        $cfg = legacy_mail_config();
        $this->assertSame('smtp.ejemplo.com', $cfg['host']);
        $this->assertSame('587', $cfg['port']);
        $this->assertSame('tls', $cfg['encryption']);
        $this->assertSame('facturacion@ejemplo.com', $cfg['from_address']);
        $this->assertSame('SIGAV', $cfg['from_name']);

        putenv('MAIL_PASSWORD');
        $this->expectException(\RuntimeException::class);
        legacy_mail_config();
    }

    /** @test */
    public function imagen_default_es_relativa_al_protocolo_y_al_host()
    {
        $this->assertSame(
            '//sistema.ejemplo.com/assets/img/photos/no-image-featured-image.png',
            legacy_imagen_default('sistema.ejemplo.com')
        );
    }

    /** @test */
    public function smartsupp_key_es_null_sin_env()
    {
        $this->assertNull(legacy_smartsupp_key());
        putenv('SMARTSUPP_KEY=abc');
        $this->assertSame('abc', legacy_smartsupp_key());
    }
}
```

- [ ] **Step 2: Correr el test para verificar que falla**

Run: `docker compose exec app vendor/bin/phpunit tests/Unit/LegacyConfigTest.php`
Expected: FAIL/ERROR con "failed to open stream: No such file or directory" sobre `public/legacy_config.php`.

- [ ] **Step 3: Implementar los helpers**

```php
<?php
// public/legacy_config.php
/**
 * Helpers de configuración para el lado legacy (public/*.php).
 * Solo funciones puras sobre getenv(): sin conexiones, sin side effects,
 * para poder testearlas con PHPUnit sin Laravel.
 *
 * Bajo Apache/mod_php las variables del contenedor llegan a getenv() solo si
 * están listadas en `PassEnv` (deploy/afip-protect.conf).
 */

if (! function_exists('legacy_env')) {
    function legacy_env($name)
    {
        $v = getenv($name);
        if ($v === false || $v === '') {
            return null;
        }
        return $v;
    }
}

if (! function_exists('legacy_env_requerida')) {
    function legacy_env_requerida(array $names)
    {
        $out = [];
        $faltan = [];
        foreach ($names as $n) {
            $v = legacy_env($n);
            if ($v === null) {
                $faltan[] = $n;
            } else {
                $out[$n] = $v;
            }
        }
        if ($faltan) {
            throw new RuntimeException('Faltan variables de entorno: '.implode(', ', $faltan));
        }
        return $out;
    }
}

if (! function_exists('legacy_db_config')) {
    function legacy_db_config()
    {
        $e = legacy_env_requerida(['LEGACY_DB_HOST', 'LEGACY_DB_USER', 'LEGACY_DB_PASS', 'LEGACY_DB_NAME']);
        return [
            'host' => $e['LEGACY_DB_HOST'],
            'user' => $e['LEGACY_DB_USER'],
            'pass' => $e['LEGACY_DB_PASS'],
            'name' => $e['LEGACY_DB_NAME'],
        ];
    }
}

if (! function_exists('legacy_mail_config')) {
    function legacy_mail_config()
    {
        $e = legacy_env_requerida(['MAIL_HOST', 'MAIL_USERNAME', 'MAIL_PASSWORD']);
        $from = legacy_env('MAIL_FROM_ADDRESS');
        return [
            'host'         => $e['MAIL_HOST'],
            'port'         => legacy_env('MAIL_PORT') ?: '587',
            'username'     => $e['MAIL_USERNAME'],
            'password'     => $e['MAIL_PASSWORD'],
            'encryption'   => legacy_env('MAIL_ENCRYPTION') ?: 'tls',
            'from_address' => $from ?: $e['MAIL_USERNAME'],
            'from_name'    => legacy_env('MAIL_FROM_NAME') ?: 'SIGAV',
        ];
    }
}

if (! function_exists('legacy_imagen_default')) {
    function legacy_imagen_default($host)
    {
        return '//'.$host.'/assets/img/photos/no-image-featured-image.png';
    }
}

if (! function_exists('legacy_smartsupp_key')) {
    function legacy_smartsupp_key()
    {
        return legacy_env('SMARTSUPP_KEY');
    }
}
```

- [ ] **Step 4: Correr el test para verificar que pasa**

Run: `docker compose exec app vendor/bin/phpunit tests/Unit/LegacyConfigTest.php`
Expected: `OK (6 tests, ...)`.

- [ ] **Step 5: Commit**

```bash
git add public/legacy_config.php tests/Unit/LegacyConfigTest.php
git commit -m "feat(legacy): helpers de configuración por entorno para public/*.php"
```

---

### Task 2: `conection.php` sin credenciales ni semilla hardcodeadas

**Files:**
- Modify: `public/conection.php:1-19`
- Modify: `.env.example`
- Modify: `docker-compose.yml:14-19`
- Modify: `deploy/afip-protect.conf:14`
- Modify: `deploy/.env.production.example` (ya tiene `LEGACY_*`; agregar `SMARTSUPP_KEY`)

**Interfaces:**
- Consumes: `legacy_db_config()`, `legacy_env()` de Task 1.
- Produces: `define('SEMILLA', ...)` sigue existiendo con el mismo valor (lo leen `setRol`, `setSucursal`, `getSucursal`, `getRol`, `login.php`, y `LegacyCookieAuth` vía `config('app.legacy_semilla')`).

- [ ] **Step 1: Confirmar el valor actual de la semilla en el `.env` local (sin imprimirlo)**

```bash
grep -c '^LEGACY_SEMILLA=' .env      # debe ser 1
php -r 'include "public/conection.php";' 2>/dev/null; echo   # solo para asegurarse de que el archivo parsea
```

Si `grep -c` da 0: copiar el valor literal de `define('SEMILLA','...')` (línea 2 de `public/conection.php`) a `.env` como `LEGACY_SEMILLA='<valor>'` (comillas simples: tiene caracteres especiales).

- [ ] **Step 2: Reescribir la cabecera de `public/conection.php`**

Reemplazar las líneas 1-19 (desde `<?php` hasta el cierre del `if (!$conn) {...}`) por:

```php
<?php
require_once __DIR__.'/legacy_config.php';

// La semilla de los hashes de cookies (rol, sucursal) y de las claves sha1
// legacy viene del entorno. DEBE ser idéntica a LEGACY_SEMILLA del .env de
// Laravel (config/app.php -> legacy_semilla), o /carga y demás rutas Laravel
// rebotan al login.
try {
    $legacyDb = legacy_db_config();
    $legacySemilla = legacy_env_requerida(['LEGACY_SEMILLA'])['LEGACY_SEMILLA'];
} catch (RuntimeException $e) {
    http_response_code(500);
    error_log('[legacy] '.$e->getMessage());
    echo 'Error de configuración del servidor.';
    exit;
}

define('SEMILLA', $legacySemilla);
define('PRODUCTOS_LIBRE', 'SI');

$conn = mysqli_connect($legacyDb['host'], $legacyDb['user'], $legacyDb['pass'], $legacyDb['name']);
date_default_timezone_set('America/Argentina/Buenos_Aires');
if (! $conn) {
    http_response_code(500);
    error_log('[legacy] mysqli_connect: '.mysqli_connect_error().' ('.mysqli_connect_errno().')');
    echo 'Error de conexión a la base de datos.';
    exit;
}
unset($legacyDb, $legacySemilla);
```

El resto del archivo (funciones `setRol`, `setSucursal`, `getSucursal`, `getRol`, etc.) queda igual.

- [ ] **Step 3: Exponer `LEGACY_SEMILLA` al contenedor dev y a mod_php**

`docker-compose.yml`, dentro de `services.app.environment`, agregar después de `LEGACY_DB_NAME: laravel`:

```yaml
      # Misma semilla que LEGACY_SEMILLA del .env de Laravel (compose la lee del .env del proyecto)
      LEGACY_SEMILLA: ${LEGACY_SEMILLA}
      # Opcionales del lado legacy (mail saliente y chat). Vacíos = deshabilitado.
      MAIL_HOST: ${MAIL_HOST:-}
      MAIL_PORT: ${MAIL_PORT:-587}
      MAIL_USERNAME: ${MAIL_USERNAME:-}
      MAIL_PASSWORD: ${MAIL_PASSWORD:-}
      MAIL_ENCRYPTION: ${MAIL_ENCRYPTION:-tls}
      MAIL_FROM_ADDRESS: ${MAIL_FROM_ADDRESS:-}
      MAIL_FROM_NAME: ${MAIL_FROM_NAME:-SIGAV}
      SMARTSUPP_KEY: ${SMARTSUPP_KEY:-}
```

`deploy/afip-protect.conf`, reemplazar la línea `PassEnv ...` por:

```apache
PassEnv LEGACY_DB_HOST LEGACY_DB_USER LEGACY_DB_PASS LEGACY_DB_NAME LEGACY_SEMILLA API_SECRET_KEY
PassEnv MAIL_HOST MAIL_PORT MAIL_USERNAME MAIL_PASSWORD MAIL_ENCRYPTION MAIL_FROM_ADDRESS MAIL_FROM_NAME
PassEnv SMARTSUPP_KEY
```

Nota: en producción (`docker-compose.prod.yml`) el `app` toma `env_file: .env`, así que estas variables ya llegan al contenedor; `PassEnv` es lo que las hace visibles a `getenv()` bajo Apache. Verificar que `docker-compose.prod.yml` no filtre variables con una lista `environment:` explícita; si la tiene, agregar las mismas claves ahí.

- [ ] **Step 4: Documentar las variables en `.env.example`**

Agregar al final de `.env.example`:

```dotenv

# --- Lado legacy (public/*.php) ---
# Conexión mysqli de public/conection.php. Sin estas cuatro, el sitio legacy
# devuelve 500 "Error de configuración del servidor" (ya no hay fallback).
LEGACY_DB_HOST=db
LEGACY_DB_USER=root
LEGACY_DB_PASS=
LEGACY_DB_NAME=laravel
# Semilla de hashes legacy (cookies rol/sucursal y claves sha1). La leen
# public/conection.php (SEMILLA) y config/app.php (legacy_semilla): mismo valor.
LEGACY_SEMILLA=
# Chat Smartsupp en el header legacy. Vacío = no se carga el script.
SMARTSUPP_KEY=
# Guard del importador de catálogo (borra productos/stock/ventas/factura).
# Solo true en instancias que arrancan de cero. NUNCA en Mercado Artesanal.
CATALOGO_IMPORTAR_PERMITIDO=false
```

En `deploy/.env.production.example` agregar, debajo del bloque `LEGACY_SEMILLA`:

```dotenv
# Chat Smartsupp en el header legacy (opcional; vacío = deshabilitado)
SMARTSUPP_KEY=
# Guard del importador de catálogo: solo true en una instancia que arranca vacía
CATALOGO_IMPORTAR_PERMITIDO=false
```

- [ ] **Step 5: Verificar manualmente**

```bash
docker compose up -d app          # recrea el contenedor con las env nuevas
curl -s -o /dev/null -w '%{http_code}\n' http://localhost:8080/login.php   # 200
docker compose exec -e LEGACY_DB_PASS= app php -r 'require "public/conection.php";' ; echo "exit=$?"
```

Expected: `200`; el segundo comando imprime `Error de configuración del servidor.` (sin volcar credenciales) y termina.

Probar además el puente de auth: loguearse en `http://localhost:8080/login.php` con un usuario de la DB dev y navegar a `http://localhost:8080/carga`: debe cargar (si rebota al login, `LEGACY_SEMILLA` del `.env` no coincide con la semilla vieja).

- [ ] **Step 6: Correr toda la suite y commitear**

Run: `docker compose exec app vendor/bin/phpunit`
Expected: `OK` con N+6 tests respecto al baseline.

```bash
git add public/conection.php .env.example docker-compose.yml deploy/afip-protect.conf deploy/.env.production.example
git commit -m "fix(legacy): conection.php sin credenciales ni semilla hardcodeadas (falla explícito si falta env)"
```

---

### Task 3: Test de regresión "sin datos de tenant hardcodeados"

**Files:**
- Test: `tests/Unit/SinDatosDeTenantHardcodeadosTest.php`

Este test se escribe **antes** de limpiar (Tasks 4-6) y debe fallar hoy. Cuando pase, garantiza que no vuelvan.

- [ ] **Step 1: Escribir el test**

```php
<?php
// tests/Unit/SinDatosDeTenantHardcodeadosTest.php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * El mismo código sirve a más de un negocio. Ningún literal de un tenant
 * (dominio, cuenta de hosting, CUIT, SMTP, clave de chat) puede vivir en el
 * código: va en .env, afip_config o perfil.
 */
class SinDatosDeTenantHardcodeadosTest extends TestCase
{
    /** Patrones prohibidos (regex, case-insensitive). */
    private $prohibidos = [
        'mercado-artesanal\.com\.ar',
        'mercado-artisanal',            // typo histórico en enviar_por_mail_pedido.php
        'c2101314',                     // cuenta de hosting Ferozo (DB, SMTP)
        'ferozo\.com',
        '_smartsupp\.key\s*=\s*[\'"][0-9a-f]{8,}',
        '\$cuitempresa\s*=\s*"\d{11}',
        '<b>CUIT</b>&nbsp;\d{11}',
        'jmarroni@gmail\.com',
    ];

    /** Dónde buscar. */
    private $rutas = ['app', 'routes', 'config', 'resources/views', 'public'];

    /** Qué saltar dentro de esas rutas. */
    private $excluir = [
        '/public/vendor/', '/public/assets/', '/public/presupuesto/', '/public/facturas/',
        '/public/clientes/', '/public/branchs/', '/public/cobros/', '/public/notas_credito/',
        '/public/AFIP/', '/public/productos/', '/public/upload', '/public/css/', '/public/js/',
        '/public/img/', '/public/fonts/',
    ];

    /** @test */
    public function no_hay_literales_de_tenant_en_el_codigo()
    {
        $base = realpath(__DIR__.'/../..');
        $hallazgos = [];
        foreach ($this->rutas as $ruta) {
            $dir = $base.'/'.$ruta;
            if (! is_dir($dir)) {
                continue;
            }
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                /** @var \SplFileInfo $file */
                $path = $file->getPathname();
                if (! preg_match('/\.(php|js|blade\.php)$/', $path)) {
                    continue;
                }
                foreach ($this->excluir as $ex) {
                    if (strpos($path, $ex) !== false) {
                        continue 2;
                    }
                }
                $contenido = file_get_contents($path);
                foreach ($this->prohibidos as $p) {
                    if (preg_match('/'.$p.'/i', $contenido, $m)) {
                        $hallazgos[] = substr($path, strlen($base) + 1).' :: '.$m[0];
                    }
                }
            }
        }
        $this->assertSame([], $hallazgos, "Literales de tenant hardcodeados:\n".implode("\n", $hallazgos));
    }
}
```

- [ ] **Step 2: Correr y confirmar que falla listando los archivos conocidos**

Run: `docker compose exec app vendor/bin/phpunit tests/Unit/SinDatosDeTenantHardcodeadosTest.php`
Expected: FAIL. La lista debe incluir al menos: `public/header.php`, `public/enviar_por_mail.php`, `public/enviar_por_mail_pedido.php`, `public/search.php`, `public/ventas_post.php`, `public/facturar.php`, `public/generar_facturar.php`, `public/reimprimir.php`, `resources/views/login/login.php`, `app/Http/Controllers/ProductoController.php`, `app/Http/Controllers/Api/SucursalesController.php`. Si aparece alguno **no** previsto, anotarlo: se limpia en Task 5 igual.

- [ ] **Step 3: Commit del test (en rojo, a propósito)**

```bash
git add tests/Unit/SinDatosDeTenantHardcodeadosTest.php
git commit -m "test(tenant): escaneo de regresión contra literales de tenant hardcodeados (rojo hasta Task 6)"
```

---

### Task 4: Smartsupp y mail saliente por entorno

**Files:**
- Modify: `public/header.php:555-563`
- Modify: `public/enviar_por_mail.php:44-56`
- Modify: `public/enviar_por_mail_pedido.php:44-61`

**Interfaces:**
- Consumes: `legacy_smartsupp_key()`, `legacy_mail_config()` (Task 1). `header.php` y ambos scripts ya hacen `require_once("conection.php")`, que ahora incluye `legacy_config.php`.
- Consumes: `$perfil["mail"]` (columna `perfil.mail`, existe en el esquema) y `$perfil["nombre"]`, que ambos scripts ya cargan.

- [ ] **Step 1: Smartsupp condicional en `public/header.php`**

Reemplazar el bloque desde `<!-- Smartsupp Live Chat script -->` hasta el `</script>` que le sigue por:

```php
<?php if (legacy_smartsupp_key()): ?>
<!-- Smartsupp Live Chat script (solo si SMARTSUPP_KEY está definida en el entorno) -->
<script type="text/javascript">
var _smartsupp = _smartsupp || {};
_smartsupp.key = <?php echo json_encode(legacy_smartsupp_key()); ?>;
window.smartsupp||(function(d) {
  var s,c,o=smartsupp=function(){ o._.push(arguments)};o._=[];
  s=d.getElementsByTagName('script')[0];c=d.createElement('script');
  c.type='text/javascript';c.charset='utf-8';c.async=true;
  c.src='https://www.smartsuppchat.com/loader.js?';s.parentNode.insertBefore(c,s);
})(document);
</script>
<?php endif; ?>
```

- [ ] **Step 2: `public/enviar_por_mail.php` con SMTP por entorno**

Reemplazar el bloque `try { ... $mail->Password = '...'; ` (desde `//Recipients` hasta la línea `$mail->Password`) por:

```php
    $smtp = legacy_mail_config();
    $from = ! empty($perfil["mail"]) ? $perfil["mail"] : $smtp['from_address'];
    $mail->setFrom($from, $perfil["nombre"]);
    $mail->addAddress($_GET["mail"], 'Cliente');
    $mail->addReplyTo($from, $perfil["nombre"]);
    $mail->IsSMTP();
    $mail->Host       = $smtp['host'];
    $mail->Port       = (int) $smtp['port'];
    $mail->SMTPSecure = $smtp['encryption'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp['username'];
    $mail->Password   = $smtp['password'];
```

El `catch (Exception $e)` existente debe capturar también la `RuntimeException` de `legacy_mail_config()`: cambiar `catch (Exception $e)` por `catch (\Throwable $e)` y el mensaje por `echo "No se pudo enviar el mail."; error_log('[mail] '.$e->getMessage());` (no volcar `$mail->ErrorInfo` al navegador: puede incluir el host SMTP).

- [ ] **Step 3: `public/enviar_por_mail_pedido.php` igual, y sin el mail de debug**

Borrar las 6 líneas del `mail("jmarroni@gmail.com", ...)` (desde `$header = "From: ...` hasta el `}else{ echo "Error en el envio";}`). Reemplazar el bloque de `//Recipients` a `$mail->Password` por el mismo código del Step 2 (idéntico; los dos archivos cargan `$perfil` de la misma forma). Aplicar el mismo cambio al `catch`.

- [ ] **Step 4: Verificar**

Run: `docker compose exec app vendor/bin/phpunit tests/Unit/SinDatosDeTenantHardcodeadosTest.php`
Expected: sigue FAIL, pero **sin** `header.php`, `enviar_por_mail.php` ni `enviar_por_mail_pedido.php` en la lista.

```bash
curl -s http://localhost:8080/login.php | grep -c smartsupp   # 0 (SMARTSUPP_KEY vacía en dev)
php -l public/header.php && php -l public/enviar_por_mail.php && php -l public/enviar_por_mail_pedido.php
```

- [ ] **Step 5: Commit**

```bash
git add public/header.php public/enviar_por_mail.php public/enviar_por_mail_pedido.php
git commit -m "fix(legacy): Smartsupp y SMTP saliente por variables de entorno (sin credenciales en código)"
```

---

### Task 5: URLs de imagen fallback relativas a la instancia

**Files:**
- Modify: `public/search.php:26,43`
- Modify: `public/ventas_post.php:73`
- Modify: `public/facturar.php:124`
- Modify: `app/Http/Controllers/ProductoController.php:500`
- Modify: `app/Http/Controllers/Api/SucursalesController.php:58-62`

**Interfaces:**
- Consumes: `legacy_imagen_default(string $host)` (Task 1). En Laravel: `asset()` y `url()` (usan `APP_URL` / la request).

- [ ] **Step 1: Legacy**

`public/search.php`, líneas 26 y 43: reemplazar `"http://mercado-artesanal.com.ar/assets/img/photos/no-image-featured-image.png"` por `legacy_imagen_default($_SERVER['HTTP_HOST'])` (sin comillas: es una llamada). Resultado en la 26:

```php
            $datos[$i]["imagen"]         = (isset($row["imagen"]))?$row["imagen"]:legacy_imagen_default($_SERVER['HTTP_HOST']);
```

`public/ventas_post.php:73`: ídem con `"http://sistema.mercado-artesanal.com.ar/..."`.

`public/facturar.php:124`: borrar la línea completa `if (strpos($logo,"127.0.0.1") > 0) $logo = "http://sistema.mercado-artesanal.com.ar/...";` (el `$logo` de arriba ya es `file://` local; la línea es un resto de cuando se bajaba por http).

- [ ] **Step 2: Laravel**

`app/Http/Controllers/ProductoController.php:500`:

```php
            $datos[$i]["imagen"]         = ($producto->imagen!=NULL)?$producto->imagen:asset('assets/img/photos/no-image-featured-image.png');
```

`app/Http/Controllers/Api/SucursalesController.php`, reemplazar las líneas 58-62 (el `// if (file_exists(...` comentado, el `if`, el `array_push` y el `//s }`) por:

```php
                    if ($imagen->imagen_url != "assets/img/photos/no-image-featured-image.png"){
                        array_push($array_imagenes, url($imagen->imagen_url));
                    }
```

- [ ] **Step 3: Verificar**

```bash
php -l public/search.php && php -l public/ventas_post.php && php -l public/facturar.php
docker compose exec app vendor/bin/phpunit tests/Unit/SinDatosDeTenantHardcodeadosTest.php
```

Expected: FAIL solo con `public/generar_facturar.php`, `public/reimprimir.php` y `resources/views/login/login.php` (los tres se borran en Task 6).

Manual: en `http://localhost:8080/ventas.php`, buscar un producto sin imagen; el `<img>` del resultado debe apuntar a `//localhost:8080/assets/img/photos/no-image-featured-image.png`.

- [ ] **Step 4: Commit**

```bash
git add public/search.php public/ventas_post.php public/facturar.php app/Http/Controllers/ProductoController.php app/Http/Controllers/Api/SucursalesController.php
git commit -m "fix(tenant): imagen fallback relativa al host de la instancia (no al dominio de Mercado)"
```

---

### Task 6: Borrar código muerto con datos de tenant

**Files:**
- Delete: `public/generar_facturar.php` (plantilla de prueba con datos de 2020; sin referencias en `public/`, `resources/`, JS)
- Delete: `public/reimprimir.php` (stub con CAE/fechas de 2021 hardcodeados; sin referencias)
- Delete: `resources/views/login/login.php` (Laravel resuelve `login.blade.php`; el `.php` nunca se renderiza)

- [ ] **Step 1: Re-verificar que no hay referencias (el estado pudo cambiar)**

```bash
grep -rnE 'generar_facturar|reimprimir\.php|login/login\.php' --include='*.php' --include='*.js' --include='*.blade.php' app routes resources public/*.php public/assets/js 2>/dev/null
```

Expected: sin salida. Si aparece alguna referencia a `reimprimir.php`, **no borrarlo**: en su lugar reemplazar `$cuitempresa="..."` por `$cuitempresa = afip_valor('cuit');` agregando `require_once __DIR__.'/afip_bridge.php';` tras `require_once ("conection.php");`, y dejar constancia en el commit.

- [ ] **Step 2: Borrar**

```bash
git rm public/generar_facturar.php public/reimprimir.php resources/views/login/login.php
```

- [ ] **Step 3: El test de regresión pasa y la suite completa también**

Run: `docker compose exec app vendor/bin/phpunit`
Expected: `OK`, incluyendo `SinDatosDeTenantHardcodeadosTest`.

```bash
curl -s -o /dev/null -w '%{http_code}\n' http://localhost:8080/login     # ruta Laravel de login: 200
```

- [ ] **Step 4: Commit**

```bash
git commit -m "chore(tenant): elimina plantillas muertas con CUIT y URLs de Mercado hardcodeados"
```

---

### Task 7: Archivar la tabla `pedidos` legacy y garantizar el esquema Laravel

**Files:**
- Create: `database/migrations/2026_09_14_000000_archivar_pedidos_legacy.php`
- Test: `tests/Feature/ArchivarPedidosLegacyMigrationTest.php`
- Modify: `public/pedidos.php:48,259`
- Modify: `public/pedidos_post.php:21,35,49`
- Modify: `public/pedidos_optica.php:12`

**Contexto que el implementador necesita:** en master conviven dos esquemas sobre `pedidos`. El Laravel (`app/Models/Pedido.php`, `PedidoController`, ruta `/pedido` enlazada desde el menú `public/header.php:473`) es `(id, id_sucursal, fecha, monto, id_usuario, id_cliente, estado)` + `detalle_pedidos`, creado por `2021_08_31_015258_create_pedidos_table.php`. El legacy (`pedidos.php`, `pedidos_post.php`; el JS `pedidos.js`/`pedidos_optica.js` postea a `pedidos_post.php`) espera otras columnas (`nro_pedido`, `productos_id`, `fecha_pedido`, `item`...) y **ya no coincide ni con el dump de Mercado** (que tiene una tercera variante de 2019 con 3 filas). El flujo legacy está roto desde antes; esta tarea solo evita que choque con la tabla Laravel: lo apunta a `pedidos_legacy` y deja que la migración archive lo que haya. La decisión de borrarlo queda para la Fase 4.

**Interfaces:**
- Produces: tabla `pedidos` con el esquema Laravel garantizada tras `php artisan migrate`; tabla `pedidos_legacy` solo si existía una `pedidos` sin `id_sucursal`.

- [ ] **Step 1: Escribir el test que falla**

```php
<?php
// tests/Feature/ArchivarPedidosLegacyMigrationTest.php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ArchivarPedidosLegacyMigrationTest extends TestCase
{
    private function migracion()
    {
        return require base_path('database/migrations/2026_09_14_000000_archivar_pedidos_legacy.php');
    }

    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropIfExists('pedidos');
        Schema::dropIfExists('pedidos_legacy');
    }

    /** @test */
    public function renombra_la_tabla_legacy_y_crea_la_de_laravel()
    {
        Schema::create('pedidos', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('nro_pedido');
            $t->integer('productos_id');
            $t->integer('cantidad');
            $t->string('precio', 20);
            $t->integer('estado');
        });
        \DB::table('pedidos')->insert(['nro_pedido' => 1, 'productos_id' => 7, 'cantidad' => 2, 'precio' => '10', 'estado' => 0]);

        $this->migracion()->up();

        $this->assertTrue(Schema::hasTable('pedidos_legacy'));
        $this->assertSame(1, \DB::table('pedidos_legacy')->count());
        $this->assertTrue(Schema::hasColumn('pedidos', 'id_sucursal'));
        $this->assertTrue(Schema::hasColumn('pedidos', 'monto'));
        $this->assertSame(0, \DB::table('pedidos')->count());
    }

    /** @test */
    public function no_toca_una_tabla_que_ya_tiene_el_esquema_laravel()
    {
        Schema::create('pedidos', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('id_sucursal');
            $t->dateTime('fecha')->nullable();
            $t->double('monto')->nullable();
            $t->integer('id_usuario')->nullable();
            $t->integer('id_cliente')->nullable();
            $t->integer('estado')->nullable();
        });
        \DB::table('pedidos')->insert(['id_sucursal' => 1, 'monto' => 5]);

        $this->migracion()->up();

        $this->assertFalse(Schema::hasTable('pedidos_legacy'));
        $this->assertSame(1, \DB::table('pedidos')->count());
    }

    /** @test */
    public function crea_la_tabla_si_no_existe_ninguna()
    {
        $this->migracion()->up();
        $this->assertTrue(Schema::hasTable('pedidos'));
        $this->assertTrue(Schema::hasColumn('pedidos', 'id_sucursal'));
    }
}
```

- [ ] **Step 2: Correr el test para verificar que falla**

Run: `docker compose exec app vendor/bin/phpunit tests/Feature/ArchivarPedidosLegacyMigrationTest.php`
Expected: ERROR "failed to open stream" sobre la migración.

- [ ] **Step 3: Escribir la migración**

Antes, copiar las columnas exactas de `database/migrations/2021_08_31_015258_create_pedidos_table.php` (abrirla) para que `crearEsquemaLaravel()` sea idéntico. Con lo que hay hoy en el repo:

```php
<?php
// database/migrations/2026_09_14_000000_archivar_pedidos_legacy.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * En instancias que nacieron de un dump legacy, `pedidos` tiene el esquema
 * viejo (nro_pedido, productos_id, ...) y choca con el esquema Laravel que
 * usan App\Models\Pedido y PedidoController (/pedido). Si la tabla existente
 * no tiene `id_sucursal`, se archiva como `pedidos_legacy` y se crea la
 * tabla Laravel. Idempotente: correrla dos veces no cambia nada.
 */
class ArchivarPedidosLegacy extends Migration
{
    public function up()
    {
        if (Schema::hasTable('pedidos') && ! Schema::hasColumn('pedidos', 'id_sucursal')) {
            if (Schema::hasTable('pedidos_legacy')) {
                throw new RuntimeException('pedidos_legacy ya existe; resolver a mano antes de migrar');
            }
            Schema::rename('pedidos', 'pedidos_legacy');
        }

        if (! Schema::hasTable('pedidos')) {
            $this->crearEsquemaLaravel();
        }
    }

    public function down()
    {
        // No se revierte: perderíamos la distinción entre ambas tablas.
    }

    private function crearEsquemaLaravel()
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_sucursal');
            $table->dateTime('fecha')->nullable();
            $table->double('monto')->nullable();
            $table->integer('id_usuario')->nullable();
            $table->integer('id_cliente')->nullable();
            $table->integer('estado')->nullable();
        });
    }
}
```

Si `create_pedidos_table.php` define tipos o columnas distintos (por ejemplo `bigIncrements`, `timestamps`), usar exactamente esos.

- [ ] **Step 4: Correr el test para verificar que pasa**

Run: `docker compose exec app vendor/bin/phpunit tests/Feature/ArchivarPedidosLegacyMigrationTest.php`
Expected: `OK (3 tests, ...)`.

- [ ] **Step 5: Apuntar el flujo legacy a `pedidos_legacy`**

`public/pedidos.php:48`: `$sql = "SELECT * FROM \`pedidos_legacy\`";`
`public/pedidos.php:259`: `FROM pedidos_legacy p`
`public/pedidos_optica.php:12`: el contador de esa pantalla es de pedidos de óptica, no del flujo legacy: `$sql = "SELECT * FROM \`pedidos_optica\`";`
`public/pedidos_post.php`:
- línea 21: reemplazar la query interpolada por una preparada:

```php
    $stmt = mysqli_prepare($conn, "UPDATE pedidos_legacy SET estado = ? WHERE nro_pedido = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $estado_nuevo, $pedido);
    if (mysqli_stmt_execute($stmt)) {
        $datos["pedido_nro"] = $pedido;
        echo json_encode($datos);
    } else {
        echo "Error al actualizar el pedido.";
    }
    mysqli_stmt_close($stmt);
    exit();
```

  (y `$pedido = (int) $_GET["pedido"]; $estado_nuevo = (int) $_GET["estado_nuevo"];` en las dos líneas de arriba).
- línea 35: `FROM pedidos_legacy p`
- línea 49: `INSERT INTO pedidos_legacy VALUES (...)` (solo cambia el nombre de la tabla: la lista de valores queda como está, esta tarea no rediseña el flujo).

- [ ] **Step 6: Verificar y correr la suite**

```bash
php -l public/pedidos.php && php -l public/pedidos_post.php && php -l public/pedidos_optica.php
grep -nE "FROM \`?pedidos\`?( |$|\")|INTO pedidos |UPDATE pedidos " public/pedidos.php public/pedidos_post.php public/pedidos_optica.php   # sin salida
docker compose exec app php artisan migrate   # aplica la nueva migración en la DB dev (MySQL)
docker compose exec app php artisan migrate   # segunda vez: "Nothing to migrate."
docker compose exec app vendor/bin/phpunit
```

Expected: suite `OK`; `/pedido` en `http://localhost:8080/pedido` carga sin error 500 tras loguearse.

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_09_14_000000_archivar_pedidos_legacy.php tests/Feature/ArchivarPedidosLegacyMigrationTest.php public/pedidos.php public/pedidos_post.php public/pedidos_optica.php
git commit -m "fix(pedidos): archiva la tabla legacy como pedidos_legacy y garantiza el esquema Laravel"
```

---

### Task 8: Guard en `catalogo:importar`

**Files:**
- Modify: `app/Console/Commands/ImportarCatalogo.php:35-40`
- Modify: `config/app.php` (junto a `legacy_semilla`, línea 73)
- Test: `tests/Feature/ImportarCatalogoGuardTest.php`

**Interfaces:**
- Produces: `config('app.catalogo_importar_permitido')` (bool, default `false`, env `CATALOGO_IMPORTAR_PERMITIDO`).

- [ ] **Step 1: Escribir el test que falla**

```php
<?php
// tests/Feature/ImportarCatalogoGuardTest.php

namespace Tests\Feature;

use Tests\TestCase;

class ImportarCatalogoGuardTest extends TestCase
{
    /** @test */
    public function con_force_y_sin_permiso_aborta_antes_de_leer_nada()
    {
        config(['app.catalogo_importar_permitido' => false]);

        $this->artisan('catalogo:importar', ['--force' => true, '--path' => '/no/existe.csv'])
            ->expectsOutput('Importación bloqueada: CATALOGO_IMPORTAR_PERMITIDO no es true en esta instancia. Este comando BORRA productos, stock, ventas y facturas.')
            ->assertExitCode(2);
    }

    /** @test */
    public function el_dry_run_sigue_disponible_sin_permiso()
    {
        config(['app.catalogo_importar_permitido' => false]);

        // Sin --force no borra nada, así que el guard no aplica: llega a la validación del CSV.
        $this->artisan('catalogo:importar', ['--path' => '/no/existe.csv'])
            ->expectsOutput('No se encontró el CSV en: /no/existe.csv')
            ->assertExitCode(1);
    }
}
```

- [ ] **Step 2: Correr el test para verificar que falla**

Run: `docker compose exec app vendor/bin/phpunit tests/Feature/ImportarCatalogoGuardTest.php`
Expected: el primer test FAIL (exit 1 y salida distinta); el segundo puede pasar ya.

- [ ] **Step 3: Implementar**

`config/app.php`, debajo de `'legacy_semilla' => env('LEGACY_SEMILLA'),`:

```php
    // Guard del importador de catálogo (borra productos/stock/ventas/factura).
    // Solo true en instancias que arrancan de cero. NUNCA en Mercado Artesanal.
    'catalogo_importar_permitido' => filter_var(env('CATALOGO_IMPORTAR_PERMITIDO', false), FILTER_VALIDATE_BOOLEAN),
```

`app/Console/Commands/ImportarCatalogo.php`, como **primeras líneas** de `handle()`:

```php
        if ($this->option('force') && ! config('app.catalogo_importar_permitido')) {
            $this->error('Importación bloqueada: CATALOGO_IMPORTAR_PERMITIDO no es true en esta instancia. Este comando BORRA productos, stock, ventas y facturas.');
            return 2;
        }
```

- [ ] **Step 4: Correr el test para verificar que pasa**

Run: `docker compose exec app vendor/bin/phpunit tests/Feature/ImportarCatalogoGuardTest.php`
Expected: `OK (2 tests, ...)`.

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/ImportarCatalogo.php config/app.php tests/Feature/ImportarCatalogoGuardTest.php
git commit -m "feat(catalogo): guard CATALOGO_IMPORTAR_PERMITIDO para el importador destructivo"
```

---

### Task 9: Documentación y cierre de la fase

**Files:**
- Modify: `CLAUDE.md` (sección "Security context" y "Hybrid architecture → Legacy PHP app")
- Modify: `deploy/README.md` (variables nuevas y `PassEnv`)
- Modify: `docs/superpowers/specs/2026-09-13-mercado-artesanal-actualizacion-design.md` (marcar Fase 0 como hecha)

- [ ] **Step 1: `CLAUDE.md`**

En "Legacy PHP app (under `public/`)", reemplazar el bullet `DB connection: **hardcoded** mysqli_connect credentials in public/conection.php (known tech-debt — ...)` por:

```markdown
- DB connection: `public/conection.php` reads `LEGACY_DB_HOST/USER/PASS/NAME` and `LEGACY_SEMILLA` via `getenv()` (helpers in `public/legacy_config.php`) and fails with a generic 500 if any is missing. There is **no fallback** anymore. Under Apache/mod_php the container env only reaches `getenv()` through `PassEnv` in `deploy/afip-protect.conf` — add new legacy env vars there too.
```

En "Security context", agregar al final:

```markdown
- Tenant data (domain, CUIT, SMTP, chat key) must never be hardcoded: `tests/Unit/SinDatosDeTenantHardcodeadosTest.php` scans `app/`, `routes/`, `config/`, `resources/views/` and `public/*.php` and fails the suite if it finds any. Read them from `.env`, `afip_config` (`afip_valor()` in legacy) or the `perfil` table.
- `catalogo:importar --force` is blocked unless `CATALOGO_IMPORTAR_PERMITIDO=true`. It wipes `ventas`, `factura`, `stock`, `stock_logs`, `descuentos_logs`, `productos_en_carrito` and `productos`.
- `pedidos` has the Laravel schema (`id_sucursal`, `monto`, ...). Instances born from a legacy dump get their old table archived as `pedidos_legacy` by `2026_09_14_000000_archivar_pedidos_legacy`; the legacy `public/pedidos*.php` flow points there and is a candidate for removal.
```

Actualizar el "Tests caveat": ahora sí existe `tests/` con suites Unit y Feature; los tests corren con `docker compose exec app vendor/bin/phpunit`.

- [ ] **Step 2: `deploy/README.md`**

En la sección de variables/`.env`, agregar `LEGACY_SEMILLA` (si no está), `SMARTSUPP_KEY`, `MAIL_*` y `CATALOGO_IMPORTAR_PERMITIDO`, y una nota: "el `PassEnv` de `deploy/afip-protect.conf` es la lista de variables que el código legacy puede leer; toda variable nueva para `public/*.php` va ahí".

- [ ] **Step 3: Marcar la Fase 0 en el spec**

En el spec, encabezado de "### Fase 0", agregar: `**Estado: implementada en la rama `feat/mercado-fase0-saneamiento` (plan `docs/superpowers/plans/2026-09-14-mercado-fase0-saneamiento.md`). Pendiente del operator: rotar la password de la DB de Ferozo y la clave Smartsupp (ambas estuvieron en el repo público).**`

- [ ] **Step 4: Suite completa, checklist de seguridad y commit**

```bash
docker compose exec app vendor/bin/phpunit
grep -rnE 'c2101314|mercado-artesanal|ferozo' app routes config resources/views public/*.php   # sin salida
git status --short | grep -v 'public/presupuesto/'   # solo lo esperado
git add CLAUDE.md deploy/README.md docs/superpowers/specs/2026-09-13-mercado-artesanal-actualizacion-design.md docs/superpowers/plans/2026-09-14-mercado-fase0-saneamiento.md
git commit -m "docs(mercado): documenta el contrato de configuración legacy y cierra la Fase 0"
```

- [ ] **Step 5: Acciones manuales del operator (no automatizables desde el repo)**

1. Rotar la password de la DB `c2101314_ma` en el panel de Ferozo y actualizar el `.env` del hosting (el valor viejo está en la historia de git del repo público).
2. Regenerar la clave de Smartsupp (o dar de baja el chat) y cargarla como `SMARTSUPP_KEY` donde corresponda.
3. En la VM de Acantilado, agregar a `/opt/sigav/.env` las variables nuevas que apliquen (`SMARTSUPP_KEY` vacío, `CATALOGO_IMPORTAR_PERMITIDO=false`, `MAIL_*` si se usa el mail) **antes** de desplegar este branch, porque `conection.php` ya no tiene fallback: si `LEGACY_DB_*`/`LEGACY_SEMILLA` faltaran, el sitio legacy daría 500. Verificar con `docker exec sigav_app php -r 'var_dump(getenv("LEGACY_SEMILLA") !== false);'` → `true`.

---

## Self-review

- **Cobertura del spec (Fase 0):** paso 1 (secretos) → Tasks 2 y 4; paso 2 (tenant) → Tasks 3, 5, 6; paso 3 (`pedidos`) → Task 7; paso 4 (`netsuite_proxy.php`/`limpiar_cache.php`) no es código del repo: queda en la Fase 3 del spec (no copiar al deploy) y en las acciones manuales; guard de `catalogo:importar` (riesgos) → Task 8; `APP_APEX`/`login.php` muerto → Task 6 y docs.
- **Placeholders:** ninguno; cada paso tiene el código o el comando.
- **Consistencia de nombres:** `legacy_env`, `legacy_env_requerida`, `legacy_db_config`, `legacy_mail_config`, `legacy_imagen_default`, `legacy_smartsupp_key` se usan con esos nombres en Tasks 1, 2, 4 y 5. `config('app.catalogo_importar_permitido')` igual en Task 8 y `.env.example` de Task 2. `pedidos_legacy` igual en Task 7 y CLAUDE.md.
