# Mercado Pago: configuración por sucursal + movimientos Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a Mercado Pago section to SIGAV v2 where each sucursal configures its own Access Token, tests the connection, and manually syncs received payments (cobros) into a local table for later invoicing work.

**Architecture:** Mirrors the existing AFIP config feature (`AfipConfig` + `AfipConfigController` + `AfipService`) with two new Eloquent models, a Guzzle-based service (no new SDK dependency), two controllers, two Blade views, and two new tables — `mercadopago_config` (one row per sucursal) and `mercadopago_pagos` (synced payment cache).

**Tech Stack:** Laravel 7.30 (PHP ^7.2.5), Eloquent, GuzzleHttp 7.3 (already a dependency), PHPUnit 8.5 + sqlite in-memory for tests, MySQL 5.7 in the dockerized dev environment for manual verification.

## Global Constraints

- PHP `^7.2.5`, Laravel `^7.0` — no syntax or APIs newer than that (e.g. no constructor property promotion, no union types).
- No new Composer dependency: use the already-installed `guzzlehttp/guzzle` directly against `https://api.mercadopago.com`, not the `mercadopago/dx-php` SDK (decided in the spec to avoid an unverified PHP-version compatibility risk).
- One Mercado Pago account (Access Token) **per sucursal**, production credentials only — no homologación/sandbox mode in this entry.
- `access_token` must be **encrypted at rest**. Laravel 7.30 (installed in this repo) has **no built-in `'encrypted'` Eloquent cast** (verified: no `case 'encrypted'` in `vendor/laravel/framework/.../HasAttributes.php`) — use a manual accessor/mutator with `Illuminate\Support\Facades\Crypt`.
- Movement sync is **manual only** (a "Sincronizar ahora" button) — no cron/queue in this entry.
- Config screen access requires `rol_id >= 2` (same gate as AFIP), for **any** sucursal. Movement viewing defaults to the cookie-scoped sucursal; only `rol_id >= 2` users get a sucursal switcher.
- `mercadopago_pagos.estado_facturacion` defaults to `'pendiente'` and is **not** touched by any code in this plan — it's a hook for the next stage (invoicing), not implemented here.
- No automated PHPUnit test should depend on the `usuarios` or `sucursales` MySQL tables: **neither has a Laravel migration** (verified via `find database/migrations -iname "*usuario*"` → empty, and `sucursales` only exists in the legacy MyISAM dump `dump/c2101314_ma.sql`). `RefreshDatabase` against the sqlite test DB only creates tables that have migrations, so any test touching `App\Models\Usuario` or `App\Models\Sucursales` against a real row will fail with "no such table". This is why `AfipConfigController`'s role gate has no automated test today, and why this plan follows the same precedent: role-gated/sucursal-scoped controller behavior is verified **manually** against the dockerized MySQL DB, not in PHPUnit.
- Do not add a DB-level foreign key from `mercadopago_config.sucursal_id` / `mercadopago_pagos.sucursal_id` to `sucursales.id` — `sucursales` is a legacy MyISAM table with no Laravel migration, so a `Schema::create` referencing it would break `php artisan migrate` on a fresh database (including the test suite). Other tables in this codebase that reference `sucursal_id` (e.g. `transferencias`) don't have this FK either — same pattern.
- Route parameters for sucursal scoping are **plain integers** (`{sucursal_id}` validated `[0-9]+`), not Eloquent route-model-binding — matches the existing convention across the codebase (`ProductoController@consultarStock`, `TransferenciaController`, etc. all take a raw id and query `Sucursales::where('id', ...)` manually; there is no precedent for implicit route-model-binding anywhere in `routes/web.php`). This is a small, intentional deviation from the literal wording in the design spec ("route model binding"), discovered while researching the codebase for this plan — behavior is unchanged, only the binding mechanism.

---

## File Structure

**New files:**
- `database/migrations/2026_06_30_100000_create_mercadopago_config_table.php`
- `database/migrations/2026_06_30_100100_create_mercadopago_pagos_table.php`
- `app/Models/MercadoPagoConfig.php`
- `app/Models/MercadoPagoPago.php`
- `app/Http/Controllers/Concerns/AutorizaRolAdmin.php`
- `app/Services/MercadoPago/MercadoPagoService.php`
- `app/Http/Controllers/MercadoPagoConfigController.php`
- `app/Http/Controllers/MercadoPagoMovimientosController.php`
- `resources/views/mercadopago/configuracion.blade.php`
- `resources/views/mercadopago/movimientos.blade.php`
- `tests/Unit/MercadoPagoConfigTest.php`
- `tests/Unit/MercadoPagoPagoTest.php`
- `tests/Unit/AutorizaRolAdminTest.php`
- `tests/Unit/MercadoPagoServiceTest.php`

**Modified files:**
- `routes/web.php`
- `app/Http/Controllers/AfipConfigController.php` (uses the new shared trait instead of its own private `autorizar()`)
- `public/header.php` (two new entries in the "Configuraciones" quick-settings panel)

---

### Task 1: `mercadopago_config` table + `MercadoPagoConfig` model

**Files:**
- Create: `database/migrations/2026_06_30_100000_create_mercadopago_config_table.php`
- Create: `app/Models/MercadoPagoConfig.php`
- Test: `tests/Unit/MercadoPagoConfigTest.php`

**Interfaces:**
- Consumes: nothing (first task).
- Produces: `App\Models\MercadoPagoConfig` with `$fillable = ['sucursal_id', 'public_key', 'activo']`, `access_token` accessor/mutator (plain string in/out, encrypted in the DB column), `tokenEnmascarado(): ?string`. Used by Task 4 (`MercadoPagoService`), Task 6 (`MercadoPagoConfigController`), Task 7 (`MercadoPagoMovimientosController`).

- [ ] **Step 1: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMercadopagoConfigTable extends Migration
{
    public function up()
    {
        Schema::create('mercadopago_config', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('sucursal_id')->unique();
            $table->text('access_token')->nullable();
            $table->string('public_key')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mercadopago_config');
    }
}
```

Save as `database/migrations/2026_06_30_100000_create_mercadopago_config_table.php`.

- [ ] **Step 2: Write the failing test**

```php
<?php

namespace Tests\Unit;

use App\Models\MercadoPagoConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MercadoPagoConfigTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function el_access_token_se_guarda_cifrado_y_se_lee_en_texto_plano()
    {
        $config = MercadoPagoConfig::create(['sucursal_id' => 1, 'activo' => true]);
        $config->access_token = 'APP_USR-1234567890';
        $config->save();

        $crudo = DB::table('mercadopago_config')->where('id', $config->id)->value('access_token');
        $this->assertNotSame('APP_USR-1234567890', $crudo);

        $releido = MercadoPagoConfig::find($config->id);
        $this->assertSame('APP_USR-1234567890', $releido->access_token);
    }

    /** @test */
    public function access_token_nulo_no_intenta_desencriptar()
    {
        $config = MercadoPagoConfig::create(['sucursal_id' => 2, 'activo' => true]);

        $this->assertNull($config->access_token);
    }

    /** @test */
    public function token_enmascarado_solo_muestra_los_ultimos_4_caracteres()
    {
        $config = MercadoPagoConfig::create(['sucursal_id' => 3, 'activo' => true]);
        $config->access_token = 'APP_USR-1234567890';
        $config->save();

        $this->assertSame('····7890', $config->tokenEnmascarado());
    }

    /** @test */
    public function token_enmascarado_es_null_sin_token()
    {
        $config = MercadoPagoConfig::create(['sucursal_id' => 4, 'activo' => true]);

        $this->assertNull($config->tokenEnmascarado());
    }

    /** @test */
    public function activo_tiene_default_true()
    {
        $config = MercadoPagoConfig::forceCreate(['sucursal_id' => 5]);

        $this->assertTrue($config->activo);
    }

    /** @test */
    public function sucursal_id_es_unico()
    {
        MercadoPagoConfig::create(['sucursal_id' => 6, 'activo' => true]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        MercadoPagoConfig::create(['sucursal_id' => 6, 'activo' => true]);
    }
}
```

Save as `tests/Unit/MercadoPagoConfigTest.php`.

- [ ] **Step 3: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Unit/MercadoPagoConfigTest.php`
Expected: FAIL — `Class 'App\Models\MercadoPagoConfig' not found`.

- [ ] **Step 4: Write the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class MercadoPagoConfig extends Model
{
    protected $table = 'mercadopago_config';

    protected $fillable = ['sucursal_id', 'public_key', 'activo'];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function setAccessTokenAttribute(?string $value): void
    {
        $this->attributes['access_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getAccessTokenAttribute(?string $value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /** Token enmascarado para mostrar en la UI sin exponerlo completo. */
    public function tokenEnmascarado(): ?string
    {
        $token = $this->access_token;

        return $token ? '····'.substr($token, -4) : null;
    }
}
```

Save as `app/Models/MercadoPagoConfig.php`.

- [ ] **Step 5: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Unit/MercadoPagoConfigTest.php`
Expected: OK (6 tests, 6+ assertions).

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_06_30_100000_create_mercadopago_config_table.php app/Models/MercadoPagoConfig.php tests/Unit/MercadoPagoConfigTest.php
git commit -m "feat(mercadopago): tabla y modelo de configuración por sucursal con token cifrado"
```

---

### Task 2: `mercadopago_pagos` table + `MercadoPagoPago` model

**Files:**
- Create: `database/migrations/2026_06_30_100100_create_mercadopago_pagos_table.php`
- Create: `app/Models/MercadoPagoPago.php`
- Test: `tests/Unit/MercadoPagoPagoTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces: `App\Models\MercadoPagoPago` with `$fillable = ['sucursal_id', 'mp_payment_id', 'fecha', 'monto', 'monto_neto', 'estado', 'medio_pago', 'comprador', 'payload_raw']` (note: `estado_facturacion` is intentionally **not** fillable), `payload_raw` cast to `array`, `fecha` cast to `datetime`. Used by Task 5 (`MercadoPagoService::sincronizarPagos`) and Task 7 (`MercadoPagoMovimientosController`).

- [ ] **Step 1: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMercadopagoPagosTable extends Migration
{
    public function up()
    {
        Schema::create('mercadopago_pagos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('sucursal_id');
            $table->string('mp_payment_id');
            $table->dateTime('fecha');
            $table->decimal('monto', 12, 2);
            $table->decimal('monto_neto', 12, 2)->nullable();
            $table->string('estado');
            $table->string('medio_pago')->nullable();
            $table->string('comprador')->nullable();
            $table->json('payload_raw')->nullable();
            $table->string('estado_facturacion')->default('pendiente');
            $table->timestamps();

            $table->unique(['sucursal_id', 'mp_payment_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('mercadopago_pagos');
    }
}
```

Save as `database/migrations/2026_06_30_100100_create_mercadopago_pagos_table.php`.

- [ ] **Step 2: Write the failing test**

```php
<?php

namespace Tests\Unit;

use App\Models\MercadoPagoPago;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MercadoPagoPagoTest extends TestCase
{
    use RefreshDatabase;

    private function datosBase(array $overrides = []): array
    {
        return array_merge([
            'sucursal_id' => 1,
            'mp_payment_id' => '123',
            'fecha' => now(),
            'monto' => 100.50,
            'estado' => 'approved',
            'payload_raw' => ['id' => 123, 'status' => 'approved'],
        ], $overrides);
    }

    /** @test */
    public function estado_facturacion_tiene_default_pendiente_y_no_es_asignable_masivamente()
    {
        $pago = MercadoPagoPago::create($this->datosBase(['estado_facturacion' => 'facturado']));

        $this->assertSame('pendiente', $pago->fresh()->estado_facturacion);
    }

    /** @test */
    public function payload_raw_se_castea_a_array()
    {
        $pago = MercadoPagoPago::create($this->datosBase());

        $this->assertIsArray($pago->fresh()->payload_raw);
        $this->assertSame(123, $pago->fresh()->payload_raw['id']);
    }

    /** @test */
    public function mp_payment_id_es_unico_por_sucursal()
    {
        MercadoPagoPago::create($this->datosBase(['mp_payment_id' => '999']));

        $this->expectException(QueryException::class);
        MercadoPagoPago::create($this->datosBase(['mp_payment_id' => '999']));
    }

    /** @test */
    public function el_mismo_mp_payment_id_es_valido_en_otra_sucursal()
    {
        MercadoPagoPago::create($this->datosBase(['sucursal_id' => 1, 'mp_payment_id' => '777']));
        $pago = MercadoPagoPago::create($this->datosBase(['sucursal_id' => 2, 'mp_payment_id' => '777']));

        $this->assertSame(2, MercadoPagoPago::count());
        $this->assertSame(2, $pago->sucursal_id);
    }
}
```

Save as `tests/Unit/MercadoPagoPagoTest.php`.

- [ ] **Step 3: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Unit/MercadoPagoPagoTest.php`
Expected: FAIL — `Class 'App\Models\MercadoPagoPago' not found`.

- [ ] **Step 4: Write the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MercadoPagoPago extends Model
{
    protected $table = 'mercadopago_pagos';

    protected $fillable = [
        'sucursal_id', 'mp_payment_id', 'fecha', 'monto', 'monto_neto',
        'estado', 'medio_pago', 'comprador', 'payload_raw',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'monto' => 'decimal:2',
        'monto_neto' => 'decimal:2',
        'payload_raw' => 'array',
    ];
}
```

Save as `app/Models/MercadoPagoPago.php`.

- [ ] **Step 5: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Unit/MercadoPagoPagoTest.php`
Expected: OK (4 tests).

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_06_30_100100_create_mercadopago_pagos_table.php app/Models/MercadoPagoPago.php tests/Unit/MercadoPagoPagoTest.php
git commit -m "feat(mercadopago): tabla y modelo de pagos sincronizados desde Mercado Pago"
```

---

### Task 3: `AutorizaRolAdmin` trait + refactor `AfipConfigController`

**Files:**
- Create: `app/Http/Controllers/Concerns/AutorizaRolAdmin.php`
- Modify: `app/Http/Controllers/AfipConfigController.php:17-25`
- Test: `tests/Unit/AutorizaRolAdminTest.php`

**Interfaces:**
- Consumes: `App\Models\Usuario` (existing model, table `usuarios` — only exists in the real MySQL DB, not in the sqlite test DB, see Global Constraints).
- Produces: trait `App\Http\Controllers\Concerns\AutorizaRolAdmin` with `protected function usuarioActual(): ?Usuario`, `protected function tieneRol(int $rolMinimo = 2): bool`, `protected function autorizar(int $rolMinimo = 2): void` (aborts 403). Used by Task 6 (`MercadoPagoConfigController`) and Task 7 (`MercadoPagoMovimientosController`), and by the refactored `AfipConfigController`.

This trait reads `$_COOKIE['kiosco']` and queries `Usuario`, so it cannot be exercised against a real row in PHPUnit (no `usuarios` migration — see Global Constraints). The test below only covers the **pure logic that doesn't touch the DB** (the "not authenticated at all" path); the role-checking paths are verified manually in Step 6 against the real dockerized MySQL DB, exactly like the rest of this codebase's role gates.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit;

use App\Http\Controllers\Concerns\AutorizaRolAdmin;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AutorizaRolAdminTest extends TestCase
{
    private function gate(): object
    {
        return new class {
            use AutorizaRolAdmin;

            public function chequear(int $rolMinimo = 2): void
            {
                $this->autorizar($rolMinimo);
            }

            public function chequearTieneRol(int $rolMinimo = 2): bool
            {
                return $this->tieneRol($rolMinimo);
            }
        };
    }

    protected function tearDown(): void
    {
        unset($_COOKIE['kiosco']);
        parent::tearDown();
    }

    /** @test */
    public function sin_cookie_kiosco_tiene_rol_devuelve_false()
    {
        unset($_COOKIE['kiosco']);

        $this->assertFalse($this->gate()->chequearTieneRol());
    }

    /** @test */
    public function sin_cookie_kiosco_autorizar_aborta_con_403()
    {
        unset($_COOKIE['kiosco']);

        $this->expectException(HttpException::class);
        try {
            $this->gate()->chequear();
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            throw $e;
        }
    }
}
```

Save as `tests/Unit/AutorizaRolAdminTest.php`.

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Unit/AutorizaRolAdminTest.php`
Expected: FAIL — `Trait "App\Http\Controllers\Concerns\AutorizaRolAdmin" not found`.

- [ ] **Step 3: Write the trait**

```php
<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Usuario;

trait AutorizaRolAdmin
{
    /** Usuario legacy logueado según la cookie `kiosco`, o null. */
    protected function usuarioActual(): ?Usuario
    {
        $kiosco = $_COOKIE['kiosco'] ?? null;

        return $kiosco ? Usuario::where('usuario', $kiosco)->first() : null;
    }

    /** ¿El usuario logueado tiene rol_id >= $rolMinimo? */
    protected function tieneRol(int $rolMinimo = 2): bool
    {
        $u = $this->usuarioActual();

        return $u && (int) $u->rol_id >= $rolMinimo;
    }

    /** Aborta con 403 si el usuario logueado no tiene rol_id >= $rolMinimo. */
    protected function autorizar(int $rolMinimo = 2): void
    {
        if (! $this->tieneRol($rolMinimo)) {
            abort(403, 'No autorizado');
        }
    }
}
```

Save as `app/Http/Controllers/Concerns/AutorizaRolAdmin.php`.

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Unit/AutorizaRolAdminTest.php`
Expected: OK (2 tests).

- [ ] **Step 5: Refactor `AfipConfigController` to use the trait**

In `app/Http/Controllers/AfipConfigController.php`, replace:

```php
class AfipConfigController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /** Gate por rol (>= 2), replicando configuracion_afip.php legacy. */
    private function autorizar(): void
    {
        $kiosco = $_COOKIE['kiosco'] ?? null;
        $u = $kiosco ? Usuario::where('usuario', $kiosco)->first() : null;
        if (! $u || (int) $u->rol_id < 2) {
            abort(403, 'No autorizado');
        }
    }
```

with:

```php
class AfipConfigController extends Controller
{
    use \App\Http\Controllers\Concerns\AutorizaRolAdmin;

    public function __construct()
    {
        $this->middleware('auth');
    }
```

Also remove the now-unused `use App\Models\Usuario;` import at the top of the file if nothing else in the class references `Usuario` directly (check with `grep -n "Usuario" app/Http/Controllers/AfipConfigController.php` after the edit — only the `use` import line should remain, since the trait has its own `use App\Models\Usuario;`).

- [ ] **Step 6: Run the full automated suite (regression) and the AFIP screen manually (real DB)**

Run: `vendor/bin/phpunit`
Expected: OK, same pass count as before this task (no test references `AfipConfigController::autorizar` directly, so none should be affected) — `AfipServiceTest` and `AfipConfigTest` must still be green.

Then verify the AFIP screen still works end-to-end against the dockerized MySQL DB (the `autorizar()` call site changed, only this confirms the trait wiring is correct):

```bash
docker compose up -d
SEMILLA='$%Reset20122017AnnaLuca#^'
USER=jmarroni   # usuarios.rol_id = 5 en el dump (dump/c2101314_ma.sql:921) -> pasa el gate >= 2
ROL_ID=$(docker exec sigav_db mysql -uroot -psecret laravel -N -e "SELECT rol_id FROM usuarios WHERE usuario='$USER'")
SUC_ID=$(docker exec sigav_db mysql -uroot -psecret laravel -N -e "SELECT sucursal_id FROM usuarios WHERE usuario='$USER'")
ROL_HASH=$(php -r "echo sha1('$SEMILLA'.$ROL_ID.'$SEMILLA');")
SUC_HASH=$(php -r "echo sha1('$SEMILLA'.$SUC_ID.'$SEMILLA');")
curl -s -o /dev/null -w "%{http_code}\n" -b "kiosco=$USER; rol=$ROL_HASH; sucursal=$SUC_HASH" http://localhost:8080/afip/configuracion
```

Expected: `200`.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Concerns/AutorizaRolAdmin.php app/Http/Controllers/AfipConfigController.php tests/Unit/AutorizaRolAdminTest.php
git commit -m "refactor(afip): extrae el gate de rol a un trait compartido (AutorizaRolAdmin)"
```

---

### Task 4: `MercadoPagoService::probarConexion`

**Files:**
- Create: `app/Services/MercadoPago/MercadoPagoService.php`
- Test: `tests/Unit/MercadoPagoServiceTest.php`

**Interfaces:**
- Consumes: `App\Models\MercadoPagoConfig` (Task 1) — reads `->access_token`.
- Produces: `App\Services\MercadoPago\MercadoPagoService` with constructor `__construct(?\GuzzleHttp\Client $client = null)` and `probarConexion(int $sucursalId): array{ok: bool, mensaje: string}`. Used by Task 6 (`MercadoPagoConfigController@probar`).

- [ ] **Step 1: Write the failing tests**

```php
<?php

namespace Tests\Unit;

use App\Models\MercadoPagoConfig;
use App\Services\MercadoPago\MercadoPagoService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MercadoPagoServiceTest extends TestCase
{
    use RefreshDatabase;

    private function servicioConRespuestas(array $responses): MercadoPagoService
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack, 'base_uri' => 'https://api.mercadopago.com/']);

        return new MercadoPagoService($client);
    }

    private function configConToken(int $sucursalId, string $token): MercadoPagoConfig
    {
        $config = MercadoPagoConfig::create(['sucursal_id' => $sucursalId, 'activo' => true]);
        $config->access_token = $token;
        $config->save();

        return $config->fresh();
    }

    /** @test */
    public function probar_conexion_sin_token_no_pega_a_la_red()
    {
        MercadoPagoConfig::create(['sucursal_id' => 1, 'activo' => true]);
        $servicio = $this->servicioConRespuestas([]);

        $r = $servicio->probarConexion(1);

        $this->assertFalse($r['ok']);
        $this->assertSame('No hay token cargado.', $r['mensaje']);
    }

    /** @test */
    public function probar_conexion_ok_con_token_valido()
    {
        $this->configConToken(1, 'TEST-TOKEN');
        $servicio = $this->servicioConRespuestas([
            new Response(200, [], json_encode(['nickname' => 'mitienda'])),
        ]);

        $r = $servicio->probarConexion(1);

        $this->assertTrue($r['ok']);
        $this->assertStringContainsString('mitienda', $r['mensaje']);
    }

    /** @test */
    public function probar_conexion_token_invalido_no_filtra_el_error_crudo()
    {
        $this->configConToken(1, 'TOKEN-MALO');
        $servicio = $this->servicioConRespuestas([
            new ClientException(
                'Unauthorized',
                new Psr7Request('GET', 'users/me'),
                new Response(401, [], json_encode(['message' => 'invalid token']))
            ),
        ]);

        $r = $servicio->probarConexion(1);

        $this->assertFalse($r['ok']);
        $this->assertStringNotContainsString('invalid token', $r['mensaje']);
    }
}
```

Save as `tests/Unit/MercadoPagoServiceTest.php`.

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/phpunit tests/Unit/MercadoPagoServiceTest.php`
Expected: FAIL — `Class 'App\Services\MercadoPago\MercadoPagoService' not found`.

- [ ] **Step 3: Write the service**

```php
<?php

namespace App\Services\MercadoPago;

use App\Models\MercadoPagoConfig;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class MercadoPagoService
{
    private Client $client;

    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? new Client(['base_uri' => 'https://api.mercadopago.com/']);
    }

    /** Prueba que el Access Token de la sucursal sea válido contra GET /users/me. */
    public function probarConexion(int $sucursalId): array
    {
        $config = MercadoPagoConfig::where('sucursal_id', $sucursalId)->first();

        if (! $config || ! $config->access_token) {
            return ['ok' => false, 'mensaje' => 'No hay token cargado.'];
        }

        try {
            $res = $this->client->request('GET', 'users/me', [
                'headers' => ['Authorization' => 'Bearer '.$config->access_token],
            ]);
            $data = json_decode((string) $res->getBody(), true);
            $nick = $data['nickname'] ?? $data['email'] ?? 'cuenta';

            return ['ok' => true, 'mensaje' => "Conexión OK ({$nick})."];
        } catch (RequestException $e) {
            Log::error('MercadoPago probarConexion falló', ['sucursal_id' => $sucursalId, 'error' => $e->getMessage()]);

            return ['ok' => false, 'mensaje' => 'No se pudo conectar con Mercado Pago. Verificá el token.'];
        }
    }
}
```

Save as `app/Services/MercadoPago/MercadoPagoService.php`.

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/phpunit tests/Unit/MercadoPagoServiceTest.php`
Expected: OK (3 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Services/MercadoPago/MercadoPagoService.php tests/Unit/MercadoPagoServiceTest.php
git commit -m "feat(mercadopago): servicio con probarConexion (GET /users/me vía Guzzle)"
```

---

### Task 5: `MercadoPagoService::sincronizarPagos`

**Files:**
- Modify: `app/Services/MercadoPago/MercadoPagoService.php`
- Modify: `tests/Unit/MercadoPagoServiceTest.php`

**Interfaces:**
- Consumes: `App\Models\MercadoPagoConfig` (Task 1), `App\Models\MercadoPagoPago` (Task 2), `Carbon\Carbon` (ships with Laravel).
- Produces: `MercadoPagoService::sincronizarPagos(int $sucursalId, \Carbon\Carbon $desde, \Carbon\Carbon $hasta): array{ok: bool, mensaje: string, nuevos: int, total: int}`. Used by Task 7 (`MercadoPagoMovimientosController@sincronizar`).

- [ ] **Step 1: Add the failing tests**

Append to `tests/Unit/MercadoPagoServiceTest.php` (inside the class, after the existing tests, before the closing `}`):

```php
    /** @test */
    public function sincronizar_pagos_sin_token_no_pega_a_la_red()
    {
        MercadoPagoConfig::create(['sucursal_id' => 1, 'activo' => true]);
        $servicio = $this->servicioConRespuestas([]);

        $r = $servicio->sincronizarPagos(1, \Carbon\Carbon::parse('2026-06-01'), \Carbon\Carbon::parse('2026-06-30'));

        $this->assertFalse($r['ok']);
        $this->assertSame(0, $r['nuevos']);
    }

    /** @test */
    public function sincronizar_pagos_crea_un_registro_por_pago_nuevo()
    {
        $this->configConToken(1, 'TEST-TOKEN');
        $pagoMp = [
            'id' => 123456,
            'date_approved' => '2026-06-01T10:00:00.000-03:00',
            'transaction_amount' => 1500.50,
            'status' => 'approved',
            'payment_type_id' => 'account_money',
            'payer' => ['email' => 'cliente@test.com'],
        ];
        $servicio = $this->servicioConRespuestas([
            new Response(200, [], json_encode(['results' => [$pagoMp]])),
        ]);

        $r = $servicio->sincronizarPagos(1, \Carbon\Carbon::parse('2026-06-01'), \Carbon\Carbon::parse('2026-06-30'));

        $this->assertTrue($r['ok']);
        $this->assertSame(1, $r['nuevos']);
        $this->assertSame(1, \App\Models\MercadoPagoPago::count());
        $this->assertSame('approved', \App\Models\MercadoPagoPago::first()->estado);
        $this->assertSame('cliente@test.com', \App\Models\MercadoPagoPago::first()->comprador);
    }

    /** @test */
    public function sincronizar_pagos_no_duplica_si_se_corre_dos_veces()
    {
        $this->configConToken(1, 'TEST-TOKEN');
        $pagoMp = [
            'id' => 123456,
            'date_created' => '2026-06-01T10:00:00.000-03:00',
            'transaction_amount' => 500,
            'status' => 'approved',
        ];
        $desde = \Carbon\Carbon::parse('2026-06-01');
        $hasta = \Carbon\Carbon::parse('2026-06-30');

        $servicio1 = $this->servicioConRespuestas([new Response(200, [], json_encode(['results' => [$pagoMp]]))]);
        $r1 = $servicio1->sincronizarPagos(1, $desde, $hasta);
        $this->assertSame(1, $r1['nuevos']);

        $servicio2 = $this->servicioConRespuestas([new Response(200, [], json_encode(['results' => [$pagoMp]]))]);
        $r2 = $servicio2->sincronizarPagos(1, $desde, $hasta);
        $this->assertSame(0, $r2['nuevos']);

        $this->assertSame(1, \App\Models\MercadoPagoPago::count());
    }

    /** @test */
    public function sincronizar_pagos_corta_al_llegar_al_tope_de_paginas_y_lo_avisa()
    {
        $this->configConToken(1, 'TEST-TOKEN');
        $respuestas = [];
        for ($pagina = 0; $pagina < 10; $pagina++) {
            $resultados = [];
            for ($i = 0; $i < 50; $i++) {
                $resultados[] = [
                    'id' => ($pagina * 50) + $i,
                    'date_created' => '2026-06-01T10:00:00.000-03:00',
                    'transaction_amount' => 100,
                    'status' => 'approved',
                ];
            }
            $respuestas[] = new Response(200, [], json_encode(['results' => $resultados]));
        }
        $servicio = $this->servicioConRespuestas($respuestas);

        $r = $servicio->sincronizarPagos(1, \Carbon\Carbon::parse('2026-06-01'), \Carbon\Carbon::parse('2026-06-30'));

        $this->assertTrue($r['ok']);
        $this->assertSame(500, $r['total']);
        $this->assertStringContainsString('máximo', $r['mensaje']);
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/phpunit tests/Unit/MercadoPagoServiceTest.php`
Expected: FAIL — `Call to undefined method App\Services\MercadoPago\MercadoPagoService::sincronizarPagos()`.

- [ ] **Step 3: Add `sincronizarPagos` to the service**

In `app/Services/MercadoPago/MercadoPagoService.php`, add to the top of the file:

```php
use App\Models\MercadoPagoPago;
use Carbon\Carbon;
```

And add this method inside the `MercadoPagoService` class, after `probarConexion`:

```php
    /** Trae cobros nuevos desde la API de pagos de Mercado Pago y los cachea localmente. */
    public function sincronizarPagos(int $sucursalId, Carbon $desde, Carbon $hasta): array
    {
        $config = MercadoPagoConfig::where('sucursal_id', $sucursalId)->first();

        if (! $config || ! $config->access_token) {
            return ['ok' => false, 'mensaje' => 'No hay token cargado.', 'nuevos' => 0, 'total' => 0];
        }

        $limit = 50;
        $maxPaginas = 10;
        $nuevos = 0;
        $total = 0;
        $cortado = false;

        try {
            for ($pagina = 0; $pagina < $maxPaginas; $pagina++) {
                $res = $this->client->request('GET', 'v1/payments/search', [
                    'headers' => ['Authorization' => 'Bearer '.$config->access_token],
                    'query' => [
                        'range' => 'date_created',
                        'begin_date' => $desde->format('Y-m-d\TH:i:s.000P'),
                        'end_date' => $hasta->format('Y-m-d\TH:i:s.000P'),
                        'sort' => 'date_created',
                        'criteria' => 'desc',
                        'limit' => $limit,
                        'offset' => $pagina * $limit,
                    ],
                ]);
                $data = json_decode((string) $res->getBody(), true);
                $resultados = $data['results'] ?? [];
                $total += count($resultados);

                foreach ($resultados as $pago) {
                    $registro = MercadoPagoPago::updateOrCreate(
                        ['sucursal_id' => $sucursalId, 'mp_payment_id' => (string) $pago['id']],
                        [
                            'fecha' => $pago['date_approved'] ?? $pago['date_created'],
                            'monto' => $pago['transaction_amount'] ?? 0,
                            'monto_neto' => $pago['transaction_details']['net_received_amount'] ?? null,
                            'estado' => $pago['status'] ?? 'unknown',
                            'medio_pago' => $pago['payment_type_id'] ?? null,
                            'comprador' => $pago['payer']['email'] ?? null,
                            'payload_raw' => $pago,
                        ]
                    );
                    if ($registro->wasRecentlyCreated) {
                        $nuevos++;
                    }
                }

                if (count($resultados) < $limit) {
                    break;
                }
                if ($pagina === $maxPaginas - 1) {
                    $cortado = true;
                }
            }
        } catch (RequestException $e) {
            Log::error('MercadoPago sincronizarPagos falló', ['sucursal_id' => $sucursalId, 'error' => $e->getMessage()]);

            return ['ok' => false, 'mensaje' => 'No se pudo sincronizar con Mercado Pago.', 'nuevos' => $nuevos, 'total' => $total];
        }

        $mensaje = "Se sincronizaron {$total} pagos ({$nuevos} nuevos).";
        if ($cortado) {
            $mensaje .= ' Se alcanzó el máximo de 500 pagos por corrida; achicá el rango de fechas para traer el resto.';
        }

        return ['ok' => true, 'mensaje' => $mensaje, 'nuevos' => $nuevos, 'total' => $total];
    }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/phpunit tests/Unit/MercadoPagoServiceTest.php`
Expected: OK (7 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Services/MercadoPago/MercadoPagoService.php tests/Unit/MercadoPagoServiceTest.php
git commit -m "feat(mercadopago): sincronizarPagos pagina v1/payments/search y hace upsert por mp_payment_id"
```

---

### Task 6: routes + `MercadoPagoConfigController`

**Files:**
- Modify: `routes/web.php`
- Create: `app/Http/Controllers/MercadoPagoConfigController.php`

**Interfaces:**
- Consumes: `App\Models\MercadoPagoConfig` (Task 1), `App\Models\Sucursales` (existing), `App\Http\Controllers\Concerns\AutorizaRolAdmin` (Task 3), `App\Services\MercadoPago\MercadoPagoService::probarConexion` (Task 4).
- Produces: routes `GET mercadopago/configuracion`, `POST mercadopago/configuracion/{sucursal_id}`, `POST mercadopago/probar/{sucursal_id}`. Used by Task 8 (views) and Task 9 (nav links).

No automated test for this task (controller methods only orchestrate `Usuario`/`Sucursales`-backed gates and views — see Global Constraints). Verified manually in Step 3.

- [ ] **Step 1: Add the routes**

In `routes/web.php`, right after the existing AFIP route group (after the line `Route::post('afip/probar/{entorno}', 'AfipConfigController@probar')->where('entorno', 'homo|prod');` and its closing `});`), add:

```php
// Mercado Pago - configuración de cuenta por sucursal y vista de movimientos
Route::middleware('throttle:20,1')->group(function () {
    Route::get('mercadopago/configuracion', 'MercadoPagoConfigController@index');
    Route::post('mercadopago/configuracion/{sucursal_id}', 'MercadoPagoConfigController@guardar')->where('sucursal_id', '[0-9]+');
    Route::post('mercadopago/probar/{sucursal_id}', 'MercadoPagoConfigController@probar')->where('sucursal_id', '[0-9]+');
    Route::post('mercadopago/movimientos/sincronizar/{sucursal_id}', 'MercadoPagoMovimientosController@sincronizar')->where('sucursal_id', '[0-9]+');
});
Route::get('mercadopago/movimientos', 'MercadoPagoMovimientosController@index');
```

(The last two lines reference `MercadoPagoMovimientosController`, created in Task 7 — adding the routes now keeps all Mercado Pago routes in one place; they will 500 until Task 7 lands, which is fine since this app isn't deployed mid-task.)

- [ ] **Step 2: Write the controller**

```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AutorizaRolAdmin;
use App\Models\MercadoPagoConfig;
use App\Models\Sucursales;
use App\Services\MercadoPago\MercadoPagoService;
use Illuminate\Http\Request;

class MercadoPagoConfigController extends Controller
{
    use AutorizaRolAdmin;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->autorizar();

        $sucursales = Sucursales::orderBy('nombre')->get();
        $configs = $sucursales->mapWithKeys(function ($sucursal) {
            return [$sucursal->id => MercadoPagoConfig::firstOrCreate(['sucursal_id' => $sucursal->id])];
        });

        return view('mercadopago.configuracion', compact('sucursales', 'configs'));
    }

    public function guardar(Request $request, int $sucursal_id)
    {
        $this->autorizar();

        $data = $request->validate([
            'access_token' => 'nullable|string|max:255',
            'public_key' => 'nullable|string|max:255',
            'activo' => 'sometimes|boolean',
        ]);

        $config = MercadoPagoConfig::firstOrCreate(['sucursal_id' => $sucursal_id]);

        if (! empty($data['access_token'])) {
            $config->access_token = $data['access_token'];
        }
        $config->public_key = $data['public_key'] ?? $config->public_key;
        $config->activo = $request->boolean('activo');
        $config->save();

        return back()->with('mp_msg', 'Configuración guardada.');
    }

    public function probar(int $sucursal_id, MercadoPagoService $mp)
    {
        $this->autorizar();

        $r = $mp->probarConexion($sucursal_id);

        return back()->with($r['ok'] ? 'mp_msg' : 'mp_error', $r['mensaje']);
    }
}
```

Save as `app/Http/Controllers/MercadoPagoConfigController.php`.

- [ ] **Step 3: Manually verify `index` and `guardar` against the real DB**

```bash
docker compose up -d
docker exec sigav_app php artisan migrate
SEMILLA='$%Reset20122017AnnaLuca#^'
USER=jmarroni   # usuarios.rol_id = 5 (dump/c2101314_ma.sql:921) -> pasa el gate >= 2
ROL_ID=$(docker exec sigav_db mysql -uroot -psecret laravel -N -e "SELECT rol_id FROM usuarios WHERE usuario='$USER'")
SUC_ID=$(docker exec sigav_db mysql -uroot -psecret laravel -N -e "SELECT sucursal_id FROM usuarios WHERE usuario='$USER'")
ROL_HASH=$(php -r "echo sha1('$SEMILLA'.$ROL_ID.'$SEMILLA');")
SUC_HASH=$(php -r "echo sha1('$SEMILLA'.$SUC_ID.'$SEMILLA');")
curl -s -o /dev/null -w "%{http_code}\n" -b "kiosco=$USER; rol=$ROL_HASH; sucursal=$SUC_HASH" http://localhost:8080/mercadopago/configuracion
```

Expected: `200` once Task 8's view exists (this controller alone will 500 on a missing view — re-run this check after Task 8 if it fails now with a `ViewException` referencing `mercadopago.configuracion`). Note in the task log if it had to be deferred.

- [ ] **Step 4: Commit**

```bash
git add routes/web.php app/Http/Controllers/MercadoPagoConfigController.php
git commit -m "feat(mercadopago): rutas y controller de configuración por sucursal"
```

---

### Task 7: `MercadoPagoMovimientosController`

**Files:**
- Create: `app/Http/Controllers/MercadoPagoMovimientosController.php`

**Interfaces:**
- Consumes: `App\Models\MercadoPagoPago` (Task 2), `App\Models\MercadoPagoConfig` (Task 1), `App\Models\Sucursales` (existing), `App\Http\Controllers\Concerns\AutorizaRolAdmin` (Task 3), `App\Services\MercadoPago\MercadoPagoService::sincronizarPagos` (Task 5), routes from Task 6.
- Produces: data for Task 8's `mercadopago/movimientos.blade.php` (`$pagos` paginator, `$config`, `$sucursales`, `$sucursalId`, `$esAdmin`, `$desde`, `$hasta`).

No automated test (same reasoning as Task 6 — depends on `Usuario`/`Sucursales` rows). Verified manually in Step 2, together with Task 8's view.

- [ ] **Step 1: Write the controller**

```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AutorizaRolAdmin;
use App\Models\MercadoPagoConfig;
use App\Models\MercadoPagoPago;
use App\Models\Sucursales;
use App\Services\MercadoPago\MercadoPagoService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MercadoPagoMovimientosController extends Controller
{
    use AutorizaRolAdmin;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $esAdmin = $this->tieneRol();

        $sucursalId = $esAdmin && $request->filled('sucursal_id')
            ? (int) $request->input('sucursal_id')
            : Sucursales::getSucursal();

        $desde = $request->filled('desde') ? Carbon::parse($request->input('desde')) : now()->subDays(30);
        $hasta = $request->filled('hasta') ? Carbon::parse($request->input('hasta')) : now();

        $pagos = MercadoPagoPago::where('sucursal_id', $sucursalId)
            ->whereBetween('fecha', [$desde->copy()->startOfDay(), $hasta->copy()->endOfDay()])
            ->orderBy('fecha', 'desc')
            ->paginate(25)
            ->appends($request->query());

        $config = MercadoPagoConfig::where('sucursal_id', $sucursalId)->first();
        $sucursales = $esAdmin ? Sucursales::orderBy('nombre')->get() : collect();

        return view('mercadopago.movimientos', compact('pagos', 'config', 'sucursales', 'sucursalId', 'esAdmin', 'desde', 'hasta'));
    }

    public function sincronizar(Request $request, int $sucursal_id, MercadoPagoService $mp)
    {
        if ($sucursal_id !== Sucursales::getSucursal()) {
            $this->autorizar();
        }

        $request->validate([
            'desde' => 'nullable|date',
            'hasta' => 'nullable|date',
        ]);

        $desde = $request->filled('desde') ? Carbon::parse($request->input('desde')) : now()->subDays(30);
        $hasta = $request->filled('hasta') ? Carbon::parse($request->input('hasta')) : now();

        if ($desde->diffInDays($hasta) > 90) {
            return back()->with('mp_error', 'El rango máximo por sincronización es de 90 días.');
        }

        $r = $mp->sincronizarPagos($sucursal_id, $desde, $hasta);

        return back()->with($r['ok'] ? 'mp_msg' : 'mp_error', $r['mensaje']);
    }
}
```

Save as `app/Http/Controllers/MercadoPagoMovimientosController.php`.

- [ ] **Step 2: Manually verify `index` against the real DB**

```bash
docker compose up -d
docker exec sigav_app php artisan migrate
SEMILLA='$%Reset20122017AnnaLuca#^'
USER=jmarroni
ROL_ID=$(docker exec sigav_db mysql -uroot -psecret laravel -N -e "SELECT rol_id FROM usuarios WHERE usuario='$USER'")
SUC_ID=$(docker exec sigav_db mysql -uroot -psecret laravel -N -e "SELECT sucursal_id FROM usuarios WHERE usuario='$USER'")
ROL_HASH=$(php -r "echo sha1('$SEMILLA'.$ROL_ID.'$SEMILLA');")
SUC_HASH=$(php -r "echo sha1('$SEMILLA'.$SUC_ID.'$SEMILLA');")
curl -s -o /dev/null -w "%{http_code}\n" -b "kiosco=$USER; rol=$ROL_HASH; sucursal=$SUC_HASH" http://localhost:8080/mercadopago/movimientos
```

Expected: `500` until Task 8's view exists (`ViewException` referencing `mercadopago.movimientos` — note as deferred, re-check after Task 8) — at this point the goal is just confirming the controller class loads, the route resolves, and the role/sucursal logic runs without a PHP fatal unrelated to the missing view.

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/MercadoPagoMovimientosController.php
git commit -m "feat(mercadopago): controller de movimientos con selector de sucursal y sync manual"
```

---

### Task 8: Blade views

**Files:**
- Create: `resources/views/mercadopago/configuracion.blade.php`
- Create: `resources/views/mercadopago/movimientos.blade.php`

**Interfaces:**
- Consumes: `$sucursales`, `$configs` (Task 6's `index`); `$pagos`, `$config`, `$sucursales`, `$sucursalId`, `$esAdmin`, `$desde`, `$hasta` (Task 7's `index`).
- Produces: the two screens described in the spec. Used by Task 9 (nav links point here).

No automated test (Blade views, no JS behavior to unit test). Verified manually in Step 3.

- [ ] **Step 1: Write the configuration view**

```blade
@extends('layout.layout')

@section('body')
<div class="content content-boxed">

    @if(session('mp_msg'))
        <div class="alert alert-success">{{ session('mp_msg') }}</div>
    @endif
    @if(session('mp_error'))
        <div class="alert alert-danger">{{ session('mp_error') }}</div>
    @endif

    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Mercado Pago — cuentas por sucursal</h3>
        </div>
        <div class="block-content">
            @foreach($sucursales as $sucursal)
                @php $cfg = $configs[$sucursal->id]; @endphp
                <div class="block block-bordered">
                    <div class="block-header">
                        <h3 class="block-title">
                            {{ $sucursal->nombre }}
                            @if($cfg->tokenEnmascarado())
                                <span class="label label-success">token configurado ({{ $cfg->tokenEnmascarado() }})</span>
                            @else
                                <span class="label label-default">sin configurar</span>
                            @endif
                        </h3>
                    </div>
                    <div class="block-content">
                        <form action="/mercadopago/configuracion/{{ $sucursal->id }}" method="post">
                            @csrf
                            <div class="row">
                                <div class="col-xs-5">
                                    <label>Access Token</label>
                                    <input type="password" class="form-control" name="access_token"
                                        placeholder="{{ $cfg->tokenEnmascarado() ? 'Dejar vacío para no cambiarlo' : 'APP_USR-...' }}">
                                </div>
                                <div class="col-xs-5">
                                    <label>Public Key</label>
                                    <input type="text" class="form-control" name="public_key" value="{{ $cfg->public_key }}">
                                </div>
                                <div class="col-xs-2">
                                    <label class="css-input switch switch-success" style="margin-top:25px;">
                                        <input type="checkbox" name="activo" value="1" {{ $cfg->activo ? 'checked' : '' }}><span></span> Activo
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary" style="margin-top:10px;">Guardar</button>
                        </form>

                        <form action="/mercadopago/probar/{{ $sucursal->id }}" method="post" style="display:inline-block; margin-top:10px;">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success">Probar conexión</button>
                        </form>

                        <a href="/mercadopago/movimientos?sucursal_id={{ $sucursal->id }}" class="btn btn-sm btn-default" style="margin-top:10px;">Ver movimientos</a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
```

Save as `resources/views/mercadopago/configuracion.blade.php`.

- [ ] **Step 2: Write the movements view**

```blade
@extends('layout.layout')

@section('body')
<div class="content content-boxed">

    @if(session('mp_msg'))
        <div class="alert alert-success">{{ session('mp_msg') }}</div>
    @endif
    @if(session('mp_error'))
        <div class="alert alert-danger">{{ session('mp_error') }}</div>
    @endif

    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Movimientos Mercado Pago</h3>
        </div>
        <div class="block-content">

            <form action="/mercadopago/movimientos" method="get" class="form-inline" style="margin-bottom:15px;">
                @if($esAdmin)
                    <label>Sucursal</label>
                    <select name="sucursal_id" class="form-control" onchange="this.form.submit()">
                        @foreach($sucursales as $s)
                            <option value="{{ $s->id }}" {{ (int) $sucursalId === (int) $s->id ? 'selected' : '' }}>{{ $s->nombre }}</option>
                        @endforeach
                    </select>
                @endif
                <label>Desde</label>
                <input type="date" class="form-control" name="desde" value="{{ $desde->toDateString() }}">
                <label>Hasta</label>
                <input type="date" class="form-control" name="hasta" value="{{ $hasta->toDateString() }}">
                <button type="submit" class="btn btn-default">Filtrar</button>
            </form>

            @if(! $config || ! $config->tokenEnmascarado())
                <div class="alert alert-warning">
                    Esta sucursal todavía no tiene un Access Token de Mercado Pago configurado.
                    <a href="/mercadopago/configuracion">Configurar ahora</a>.
                </div>
            @else
                <form action="/mercadopago/movimientos/sincronizar/{{ $sucursalId }}" method="post" class="form-inline" style="margin-bottom:15px;">
                    @csrf
                    <input type="hidden" name="desde" value="{{ $desde->toDateString() }}">
                    <input type="hidden" name="hasta" value="{{ $hasta->toDateString() }}">
                    <button type="submit" class="btn btn-sm btn-success">Sincronizar ahora</button>
                </form>

                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Monto</th>
                            <th>Monto neto</th>
                            <th>Estado</th>
                            <th>Medio de pago</th>
                            <th>Comprador</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pagos as $pago)
                            <tr>
                                <td>{{ $pago->fecha->format('d/m/Y H:i') }}</td>
                                <td>${{ number_format($pago->monto, 2, ',', '.') }}</td>
                                <td>{{ $pago->monto_neto ? '$'.number_format($pago->monto_neto, 2, ',', '.') : '-' }}</td>
                                <td>
                                    <span class="label {{ $pago->estado === 'approved' ? 'label-success' : ($pago->estado === 'rejected' ? 'label-danger' : 'label-warning') }}">
                                        {{ $pago->estado }}
                                    </span>
                                </td>
                                <td>{{ $pago->medio_pago ?? '-' }}</td>
                                <td>{{ $pago->comprador ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6">No hay movimientos sincronizados en este rango.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                {{ $pagos->links() }}
            @endif

        </div>
    </div>

</div>
@endsection
```

Save as `resources/views/mercadopago/movimientos.blade.php`.

- [ ] **Step 3: Manually verify both screens end-to-end against the real DB**

```bash
docker compose up -d
docker exec sigav_app php artisan migrate
SEMILLA='$%Reset20122017AnnaLuca#^'
USER=jmarroni
ROL_ID=$(docker exec sigav_db mysql -uroot -psecret laravel -N -e "SELECT rol_id FROM usuarios WHERE usuario='$USER'")
SUC_ID=$(docker exec sigav_db mysql -uroot -psecret laravel -N -e "SELECT sucursal_id FROM usuarios WHERE usuario='$USER'")
ROL_HASH=$(php -r "echo sha1('$SEMILLA'.$ROL_ID.'$SEMILLA');")
SUC_HASH=$(php -r "echo sha1('$SEMILLA'.$SUC_ID.'$SEMILLA');")
COOKIES="kiosco=$USER; rol=$ROL_HASH; sucursal=$SUC_HASH"

curl -s -o /dev/null -w "configuracion: %{http_code}\n" -b "$COOKIES" http://localhost:8080/mercadopago/configuracion
curl -s -o /dev/null -w "movimientos: %{http_code}\n" -b "$COOKIES" http://localhost:8080/mercadopago/movimientos
```

Expected: both `200`. If `sucursales` is empty in this dump (check with `docker exec sigav_db mysql -uroot -psecret laravel -N -e "SELECT COUNT(*) FROM sucursales"`), insert one test row first — per the existing `carga-grid-serverside` memory, the dump has **no rows in `sucursales`**, so this is expected and required before these screens render anything beyond an empty list:

```bash
docker exec sigav_db mysql -uroot -psecret laravel -e "INSERT INTO sucursales (id, nombre, imagen, provincia, codigo_postal, pto_vta) VALUES (3, 'Sucursal Test', '', 'Buenos Aires', '7600', 1) ON DUPLICATE KEY UPDATE nombre=nombre"
```

(`id=3` matches `jmarroni`'s `sucursal_id` from Step 2/3's `SUC_ID` lookup, so the movimientos screen's own-sucursal view has a row to resolve.)

Then also use a browser (via the `run` skill or manually) with the same forged cookies set through devtools to visually confirm: the "Probar conexión" button shows a result, the date filters work, and "Sincronizar ahora" round-trips without a 500 (it will report `No hay token cargado.` since no real MP token is loaded — that's expected; loading a real token to test a true sync is a separate manual step the user can do once this lands, using their own Mercado Pago Access Token).

- [ ] **Step 4: Commit**

```bash
git add resources/views/mercadopago/configuracion.blade.php resources/views/mercadopago/movimientos.blade.php
git commit -m "feat(mercadopago): vistas de configuración por sucursal y de movimientos"
```

---

### Task 9: Navigation entries in `public/header.php`

**Files:**
- Modify: `public/header.php` (Configuraciones quick-settings panel, the block right after the AFIP entry)

**Interfaces:**
- Consumes: nothing (static HTML links to Task 6/7's routes).
- Produces: two visible nav entries.

No automated test (legacy plain-PHP markup). Verified manually in Step 2.

- [ ] **Step 1: Add the two entries**

In `public/header.php`, find this existing block (the AFIP entry in the "Configuraciones" panel):

```php
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-xs-10">
                                    <a href="/afip/configuracion" style="color:black;"><div class="font-s13 font-w600">AFIP</div></a>
                                        <div class="font-s13 font-w400 text-muted">Configuracion facturaci&oacute;n electr&oacute;n ica</div>
                                    </div>
                                </div>
                            </div>
```

Insert immediately after it (still before the `PERFIL` block):

```php
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-xs-10">
                                    <a href="/mercadopago/configuracion" style="color:black;"><div class="font-s13 font-w600">Mercado Pago</div></a>
                                        <div class="font-s13 font-w400 text-muted">Cuenta y token por sucursal</div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-xs-10">
                                    <a href="/mercadopago/movimientos" style="color:black;"><div class="font-s13 font-w600">Movimientos MP</div></a>
                                        <div class="font-s13 font-w400 text-muted">Cobros sincronizados de Mercado Pago</div>
                                    </div>
                                </div>
                            </div>
```

- [ ] **Step 2: Manually verify the links render and navigate correctly**

```bash
docker compose up -d
SEMILLA='$%Reset20122017AnnaLuca#^'
USER=jmarroni
ROL_ID=$(docker exec sigav_db mysql -uroot -psecret laravel -N -e "SELECT rol_id FROM usuarios WHERE usuario='$USER'")
SUC_ID=$(docker exec sigav_db mysql -uroot -psecret laravel -N -e "SELECT sucursal_id FROM usuarios WHERE usuario='$USER'")
ROL_HASH=$(php -r "echo sha1('$SEMILLA'.$ROL_ID.'$SEMILLA');")
SUC_HASH=$(php -r "echo sha1('$SEMILLA'.$SUC_ID.'$SEMILLA');")
curl -s -b "kiosco=$USER; rol=$ROL_HASH; sucursal=$SUC_HASH" http://localhost:8080/ventas.php | grep -o 'href="/mercadopago/[a-z]*"'
```

Expected: both `href="/mercadopago/configuracion"` and `href="/mercadopago/movimientos"` appear in the output (the Configuraciones panel is rendered on any page that includes `header.php`, e.g. `ventas.php`).

- [ ] **Step 3: Commit**

```bash
git add public/header.php
git commit -m "feat(mercadopago): agrega Mercado Pago al panel de Configuraciones"
```

---

## Spec Coverage Check

- Cuenta por sucursal con Access Token cifrado + Public Key + switch activo → Task 1, 6, 8.
- Probar conexión (GET /users/me) sin filtrar errores crudos → Task 4.
- Sincronización manual de cobros (Payments API), upsert por `mp_payment_id`, tope de 500/90 días → Task 5, 7.
- Pantalla de movimientos con filtro de fechas, selector de sucursal solo para admins, estado vacío si no hay token → Task 7, 8.
- Gate `rol_id >= 2` para configurar, reuso del patrón de AFIP → Task 3, 6.
- Navegación en el panel de Configuraciones → Task 9.
- `estado_facturacion` como gancho sin lógica de facturación → Task 2 (campo creado, fillable lo excluye a propósito, ningún controller lo modifica).
