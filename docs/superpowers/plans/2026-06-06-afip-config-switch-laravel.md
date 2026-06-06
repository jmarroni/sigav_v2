# AFIP Config + Switch Homologación/Producción — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Permitir cargar credenciales AFIP de homologación y producción con un switch global, construido en Laravel, sacando las credenciales del webroot; la emisión legacy lee el modo/credenciales desde Laravel vía un puente delgado.

**Architecture:** Fuente de verdad = tabla `afip_config` (modo activo + datos escalares) + archivos `cert`/`key` en `storage/app/afip/{homo,prod}/` (fuera del webroot). Laravel administra todo (Model + Service + Controller + Blade). Los `.php` de facturación leen vía `public/afip_bridge.php` desde la misma tabla y storage. El SDK AFIP vendado (`public/vendor/afipsdk/afip.php`) se reutiliza sin parchear: resuelve los WSDL y la URL homo/prod desde su propio directorio según el flag `production`; de `res_folder`/`ta_folder` solo toma `cert`, `key` y el cache de tokens.

**Tech Stack:** PHP 7.2 / Laravel 7, MySQL (prod) / SQLite memoria (tests), PHPUnit, mysqli (legacy), SDK `afipsdk/afip.php` v0.7 (vendado).

**Spec:** `docs/superpowers/specs/2026-06-06-afip-config-switch-laravel-design.md`

---

## Estructura de archivos

**Nuevos:**
- `config/afip.php` — rutas configurables (storage + SDK), inyectables en tests
- `database/migrations/2026_06_06_000000_create_afip_config_table.php`
- `database/seeds/AfipConfigSeeder.php` (classmap, sin namespace)
- `app/Models/AfipConfig.php`
- `app/Services/Afip/AfipService.php`
- `app/Http/Controllers/AfipConfigController.php`
- `resources/views/afip/configuracion.blade.php`
- `public/afip_bridge.php`
- `tests/TestCase.php`, `tests/CreatesApplication.php`, `tests/Unit/.gitkeep`, `tests/Feature/.gitkeep`
- `tests/Unit/AfipConfigTest.php`, `tests/Unit/AfipServiceTest.php`

**Modificados:**
- `routes/web.php`
- `database/seeds/DatabaseSeeder.php`
- `.gitignore`
- Billing legacy: `facturar.php`, `ventas.php`, `nota_de_credito.php`, `nota_de_debito.php`, `devoluciones.php`, `obtener_tiposDocumentos.php`, `obtener_alicuotas.php`, `taxt_types.php`
- `public/header.php` (menú)
- `deploy/README.md`

**Eliminados (cutover):**
- `public/configuracion_afip.php`, `public/guardar_certificados.php`, `public/emitir_online.php`, `public/solicitar_datos.php`, `public/assets/js/pages/afip.js`
- Credenciales: `public/AFIP/{cert,key}`, `public/vendor/afipsdk/afip.php/src/Afip_res/{cert,key,cuit,ptovta,comprobante,condicion_iva,inicio_actividades,ingresos_brutos,emitir,solicitar_datos,TA-*.xml,TRA-*.xml}` (se conservan `*.wsdl` y `.htaccess`)
- (a confirmar) `public/facturarbk26-07-2022.php`

---

## Task 1: Scaffolding de PHPUnit

No existe `tests/`. Crear la base mínima para poder correr tests.

**Files:**
- Create: `tests/CreatesApplication.php`
- Create: `tests/TestCase.php`
- Create: `tests/Unit/.gitkeep`
- Create: `tests/Feature/.gitkeep`

- [ ] **Step 1: Crear `tests/CreatesApplication.php`**

```php
<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
```

- [ ] **Step 2: Crear `tests/TestCase.php`**

```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
}
```

- [ ] **Step 3: Crear los directorios de suites**

```bash
mkdir -p tests/Unit tests/Feature
touch tests/Unit/.gitkeep tests/Feature/.gitkeep
```

- [ ] **Step 4: Regenerar autoload y verificar que PHPUnit arranca**

Run:
```bash
composer dump-autoload && vendor/bin/phpunit
```
Expected: corre sin error de bootstrap; reporta "No tests executed!" (todavía no hay tests). Si falla por falta de `APP_KEY`, correr `php artisan key:generate` primero.

- [ ] **Step 5: Commit**

```bash
git add tests/ composer.json composer.lock 2>/dev/null; git add tests/
git commit -m "test: scaffolding base de PHPUnit (TestCase + suites)"
```

---

## Task 2: Config `config/afip.php`

Rutas configurables para que el Service sea testeable sin tocar las credenciales reales.

**Files:**
- Create: `config/afip.php`

- [ ] **Step 1: Crear `config/afip.php`**

```php
<?php

return [

    // Carpeta base (FUERA del webroot) donde viven las credenciales por entorno:
    // storage/app/afip/homo/{cert,key} y storage/app/afip/prod/{cert,key}.
    // Override por env (p. ej. en tests) con AFIP_STORAGE_PATH.
    'storage_path' => env('AFIP_STORAGE_PATH', storage_path('app/afip')),

    // Ruta al SDK AFIP vendado. Transicional: cuando el SDK pase a composer,
    // esta entrada se elimina.
    'sdk_path' => base_path('public/vendor/afipsdk/afip.php/src/Afip.php'),

];
```

- [ ] **Step 2: Verificar que el config carga**

Run:
```bash
php artisan config:clear && php -r "require 'vendor/autoload.php'; \$a=require 'config/afip.php'; var_dump(isset(\$a['storage_path']), isset(\$a['sdk_path']));"
```
Expected: dos `bool(true)`.

- [ ] **Step 3: Commit**

```bash
git add config/afip.php
git commit -m "feat(afip): config de rutas (storage fuera del webroot + SDK)"
```

---

## Task 3: Migración `afip_config`

**Files:**
- Create: `database/migrations/2026_06_06_000000_create_afip_config_table.php`

- [ ] **Step 1: Crear la migración**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAfipConfigTable extends Migration
{
    public function up()
    {
        Schema::create('afip_config', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->enum('entorno', ['homo', 'prod'])->unique();
            $table->string('cuit')->nullable();
            $table->string('ptovta')->nullable();
            $table->string('comprobante')->nullable();
            $table->string('condicion_iva')->nullable();
            $table->string('inicio_actividades')->nullable();
            $table->string('ingresos_brutos')->nullable();
            $table->boolean('emitir')->default(false);
            $table->boolean('solicitar_datos')->default(false);
            $table->boolean('activo')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('afip_config');
    }
}
```

- [ ] **Step 2: Correr la migración en sqlite memoria (smoke)**

Run:
```bash
php artisan migrate --env=testing --database=sqlite --path=database/migrations 2>/dev/null; echo "exit:$?"
```
Expected: no error de SQL (si el entorno testing no está configurado para sqlite file, este smoke se valida igual en los tests de la Task 5 con `RefreshDatabase`).

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_06_06_000000_create_afip_config_table.php
git commit -m "feat(afip): migración tabla afip_config"
```

---

## Task 4: Model `AfipConfig`

**Files:**
- Create: `app/Models/AfipConfig.php`
- Test: `tests/Unit/AfipConfigTest.php`

- [ ] **Step 1: Escribir el test que falla**

```php
<?php

namespace Tests\Unit;

use App\Models\AfipConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AfipConfigTest extends TestCase
{
    use RefreshDatabase;

    private function seedEntornos(): void
    {
        AfipConfig::create(['entorno' => 'homo', 'activo' => true]);
        AfipConfig::create(['entorno' => 'prod', 'activo' => false]);
    }

    /** @test */
    public function activar_deja_exactamente_un_entorno_activo()
    {
        $this->seedEntornos();

        AfipConfig::activar('prod');

        $this->assertSame('prod', AfipConfig::activa()->entorno);
        $this->assertSame(1, AfipConfig::where('activo', true)->count());
    }

    /** @test */
    public function ruta_storage_apunta_a_la_carpeta_del_entorno()
    {
        config(['afip.storage_path' => '/tmp/afip_x']);
        $cfg = AfipConfig::create(['entorno' => 'homo']);

        $this->assertSame('/tmp/afip_x/homo/', $cfg->rutaStorage());
    }
}
```

- [ ] **Step 2: Correr el test (debe fallar)**

Run: `vendor/bin/phpunit tests/Unit/AfipConfigTest.php`
Expected: FAIL — `Class 'App\Models\AfipConfig' not found`.

- [ ] **Step 3: Implementar el modelo**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AfipConfig extends Model
{
    protected $table = 'afip_config';

    protected $fillable = [
        'entorno', 'cuit', 'ptovta', 'comprobante', 'condicion_iva',
        'inicio_actividades', 'ingresos_brutos', 'emitir', 'solicitar_datos',
    ];

    protected $casts = [
        'emitir' => 'boolean',
        'solicitar_datos' => 'boolean',
        'activo' => 'boolean',
    ];

    /** Entorno actualmente activo (el switch). */
    public static function activa(): ?self
    {
        return static::where('activo', true)->first();
    }

    /** Activa un entorno de forma atómica (exactamente uno queda activo). */
    public static function activar(string $entorno): void
    {
        DB::transaction(function () use ($entorno) {
            static::query()->update(['activo' => false]);
            static::where('entorno', $entorno)->update(['activo' => true]);
        });
    }

    /** Carpeta (con trailing slash) de credenciales de este entorno, fuera del webroot. */
    public function rutaStorage(): string
    {
        return rtrim(config('afip.storage_path'), '/').'/'.$this->entorno.'/';
    }

    /** ¿Tiene cert y key cargados? */
    public function tieneCredenciales(): bool
    {
        $dir = $this->rutaStorage();
        return is_file($dir.'cert') && is_file($dir.'key');
    }
}
```

> `entorno` está en `$fillable` para poder crearlo en seeder/tests; `activo` NO está (se maneja solo por `activar()`).

- [ ] **Step 4: Correr el test (debe pasar)**

Run: `vendor/bin/phpunit tests/Unit/AfipConfigTest.php`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Models/AfipConfig.php tests/Unit/AfipConfigTest.php
git commit -m "feat(afip): modelo AfipConfig con switch atómico"
```

---

## Task 5: Service `AfipService`

**Files:**
- Create: `app/Services/Afip/AfipService.php`
- Test: `tests/Unit/AfipServiceTest.php`

- [ ] **Step 1: Escribir el test que falla**

```php
<?php

namespace Tests\Unit;

use App\Models\AfipConfig;
use App\Services\Afip\AfipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AfipServiceTest extends TestCase
{
    use RefreshDatabase;

    private string $tmp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmp = sys_get_temp_dir().'/afip_test_'.uniqid();
        config(['afip.storage_path' => $this->tmp]);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tmp)) {
            exec('rm -rf '.escapeshellarg($this->tmp));
        }
        parent::tearDown();
    }

    private function dummyCreds(string $entorno): void
    {
        $dir = $this->tmp.'/'.$entorno.'/';
        @mkdir($dir, 0700, true);
        file_put_contents($dir.'cert', "-----BEGIN CERTIFICATE-----\nX\n-----END CERTIFICATE-----\n");
        file_put_contents($dir.'key', "-----BEGIN PRIVATE KEY-----\nX\n-----END PRIVATE KEY-----\n");
    }

    /** @test */
    public function instancia_homologacion_usa_url_homo_y_no_produccion()
    {
        AfipConfig::create(['entorno' => 'homo', 'cuit' => '20111111112']);
        $this->dummyCreds('homo');

        $afip = (new AfipService())->instancia('homo');

        $this->assertStringContainsString('wsaahomo.afip.gov.ar', $afip->WSAA_URL);
        $this->assertStringContainsString('/homo/', $afip->CERT);
    }

    /** @test */
    public function instancia_produccion_usa_url_de_produccion()
    {
        AfipConfig::create(['entorno' => 'prod', 'cuit' => '20111111112']);
        $this->dummyCreds('prod');

        $afip = (new AfipService())->instancia('prod');

        $this->assertSame('https://wsaa.afip.gov.ar/ws/services/LoginCms', $afip->WSAA_URL);
    }

    /** @test */
    public function guardar_credenciales_rechaza_pem_invalido()
    {
        $this->expectException(\InvalidArgumentException::class);
        (new AfipService())->guardarCredenciales('homo', 'no soy pem', 'tampoco');
    }

    /** @test */
    public function guardar_credenciales_escribe_los_archivos()
    {
        (new AfipService())->guardarCredenciales(
            'homo',
            "-----BEGIN CERTIFICATE-----\nA\n-----END CERTIFICATE-----\n",
            "-----BEGIN PRIVATE KEY-----\nB\n-----END PRIVATE KEY-----\n"
        );

        $this->assertFileExists($this->tmp.'/homo/cert');
        $this->assertFileExists($this->tmp.'/homo/key');
    }
}
```

- [ ] **Step 2: Correr el test (debe fallar)**

Run: `vendor/bin/phpunit tests/Unit/AfipServiceTest.php`
Expected: FAIL — `Class 'App\Services\Afip\AfipService' not found`.

- [ ] **Step 3: Implementar el service**

```php
<?php

namespace App\Services\Afip;

use App\Models\AfipConfig;
use Illuminate\Support\Facades\Log;

class AfipService
{
    public function __construct()
    {
        if (! class_exists('Afip')) {
            require_once config('afip.sdk_path');
        }
    }

    /** Construye una instancia del SDK configurada para el entorno dado (o el activo). */
    public function instancia(?string $entorno = null): \Afip
    {
        $cfg = $entorno
            ? AfipConfig::where('entorno', $entorno)->firstOrFail()
            : AfipConfig::activa();

        if (! $cfg) {
            throw new \RuntimeException('No hay entorno AFIP activo configurado');
        }

        $dir = $cfg->rutaStorage();

        return new \Afip([
            'CUIT'       => floatval($cfg->cuit),
            'production' => $cfg->entorno === 'prod',
            'res_folder' => $dir,
            'ta_folder'  => $dir,
        ]);
    }

    /** Guarda cert/key del entorno en storage (fuera del webroot), con permisos restrictivos. */
    public function guardarCredenciales(string $entorno, string $cert, string $key): void
    {
        if (strpos($cert, '-----BEGIN') === false || strpos($key, '-----BEGIN') === false) {
            throw new \InvalidArgumentException('El certificado o la clave no parecen tener formato PEM válido');
        }

        $dir = rtrim(config('afip.storage_path'), '/').'/'.$entorno.'/';
        if (! is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        file_put_contents($dir.'cert', $cert);
        @chmod($dir.'cert', 0600);
        file_put_contents($dir.'key', $key);
        @chmod($dir.'key', 0600);
    }

    /** Prueba la conexión con AFIP para un entorno. No filtra detalles al usuario. */
    public function probar(string $entorno): array
    {
        try {
            $cfg = AfipConfig::where('entorno', $entorno)->firstOrFail();
            $afip = $this->instancia($entorno);
            $num = $afip->ElectronicBilling->GetLastVoucher($cfg->ptovta, $cfg->comprobante);

            return ['ok' => true, 'mensaje' => "Conexión OK. Último comprobante autorizado: {$num}"];
        } catch (\Throwable $e) {
            Log::error('AFIP probar() falló', ['entorno' => $entorno, 'error' => $e->getMessage()]);

            return ['ok' => false, 'mensaje' => 'No se pudo conectar con AFIP. Revisá las credenciales y los datos cargados.'];
        }
    }

    /** Tipos de comprobante para poblar el select; [] si falla (no rompe la pantalla). */
    public function tiposComprobante(string $entorno): array
    {
        try {
            return (array) $this->instancia($entorno)->ElectronicBilling->GetVoucherTypes();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
```

- [ ] **Step 4: Correr el test (debe pasar)**

Run: `vendor/bin/phpunit tests/Unit/AfipServiceTest.php`
Expected: PASS (4 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Services/Afip/AfipService.php tests/Unit/AfipServiceTest.php
git commit -m "feat(afip): AfipService (instancia/guardar/probar) con tests"
```

---

## Task 6: Seeder + DatabaseSeeder

**Files:**
- Create: `database/seeds/AfipConfigSeeder.php`
- Modify: `database/seeds/DatabaseSeeder.php`

- [ ] **Step 1: Crear `database/seeds/AfipConfigSeeder.php`** (classmap, sin namespace)

```php
<?php

use App\Models\AfipConfig;
use Illuminate\Database\Seeder;

class AfipConfigSeeder extends Seeder
{
    public function run()
    {
        // Dos entornos vacíos; homo activo por defecto (para testear sin emitir real).
        AfipConfig::firstOrCreate(['entorno' => 'homo'], ['activo' => true]);
        AfipConfig::firstOrCreate(['entorno' => 'prod'], ['activo' => false]);
    }
}
```

- [ ] **Step 2: Registrar en `database/seeds/DatabaseSeeder.php`**

Reemplazar el cuerpo de `run()`:

```php
    public function run()
    {
        $this->call(AfipConfigSeeder::class);
    }
```

- [ ] **Step 3: Regenerar autoload (classmap) y verificar la clase**

Run:
```bash
composer dump-autoload && php -r "require 'vendor/autoload.php'; var_dump(class_exists('AfipConfigSeeder'));"
```
Expected: `bool(true)`.

- [ ] **Step 4: Commit**

```bash
git add database/seeds/AfipConfigSeeder.php database/seeds/DatabaseSeeder.php
git commit -m "feat(afip): seeder de entornos (homo activo por defecto)"
```

---

## Task 7: Controller + rutas

**Files:**
- Create: `app/Http/Controllers/AfipConfigController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Crear el controller**

```php
<?php

namespace App\Http\Controllers;

use App\Models\AfipConfig;
use App\Models\Usuario;
use App\Services\Afip\AfipService;
use Illuminate\Http\Request;

class AfipConfigController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /** Gate por rol (≥ 2), replicando configuracion_afip.php legacy. */
    private function autorizar(): void
    {
        $kiosco = $_COOKIE['kiosco'] ?? null;
        $u = $kiosco ? Usuario::where('usuario', $kiosco)->first() : null;
        if (! $u || (int) $u->rol_id < 2) {
            abort(403, 'No autorizado');
        }
    }

    public function index(AfipService $afip)
    {
        $this->autorizar();

        $entornos = [
            'homo' => AfipConfig::firstOrCreate(['entorno' => 'homo']),
            'prod' => AfipConfig::firstOrCreate(['entorno' => 'prod']),
        ];

        $tipos = [
            'homo' => $afip->tiposComprobante('homo'),
            'prod' => $afip->tiposComprobante('prod'),
        ];

        $activo = AfipConfig::activa();

        return view('afip.configuracion', compact('entornos', 'tipos', 'activo'));
    }

    public function guardar(Request $request, string $entorno)
    {
        $this->autorizar();

        $data = $request->validate([
            'cuit' => 'nullable|digits:11',
            'ptovta' => 'nullable|integer',
            'comprobante' => 'nullable|integer',
            'condicion_iva' => 'nullable|string|max:50',
            'inicio_actividades' => 'nullable|string|max:20',
            'ingresos_brutos' => 'nullable|string|max:50',
            'emitir' => 'sometimes|boolean',
            'solicitar_datos' => 'sometimes|boolean',
        ]);

        $data['emitir'] = $request->boolean('emitir');
        $data['solicitar_datos'] = $request->boolean('solicitar_datos');

        AfipConfig::where('entorno', $entorno)->update($data);

        return back()->with('afip_msg', "Datos de {$entorno} guardados.");
    }

    public function subirCredenciales(Request $request, string $entorno, AfipService $afip)
    {
        $this->autorizar();

        $request->validate([
            'cert' => 'required|string',
            'key' => 'required|string',
        ]);

        try {
            $afip->guardarCredenciales($entorno, $request->input('cert'), $request->input('key'));
            return back()->with('afip_msg', "Credenciales de {$entorno} guardadas.");
        } catch (\InvalidArgumentException $e) {
            return back()->with('afip_error', $e->getMessage());
        }
    }

    public function activar(Request $request)
    {
        $this->autorizar();

        $data = $request->validate(['entorno' => 'required|in:homo,prod']);
        AfipConfig::activar($data['entorno']);

        return back()->with('afip_msg', "Entorno activo: {$data['entorno']}.");
    }

    public function probar(string $entorno, AfipService $afip)
    {
        $this->autorizar();

        $r = $afip->probar($entorno);

        return back()->with($r['ok'] ? 'afip_msg' : 'afip_error', $r['mensaje']);
    }
}
```

- [ ] **Step 2: Agregar rutas en `routes/web.php`** (después del bloque de `usuario`, antes de las de transferencia)

```php
// AFIP - configuración de credenciales y switch homologación/producción
Route::get('afip/configuracion', 'AfipConfigController@index');
Route::post('afip/configuracion/{entorno}', 'AfipConfigController@guardar')->where('entorno', 'homo|prod');
Route::post('afip/credenciales/{entorno}', 'AfipConfigController@subirCredenciales')->where('entorno', 'homo|prod');
Route::post('afip/activar', 'AfipConfigController@activar');
Route::post('afip/probar/{entorno}', 'AfipConfigController@probar')->where('entorno', 'homo|prod');
```

- [ ] **Step 3: Verificar que las rutas registran**

Run: `php artisan route:list | grep afip`
Expected: las 5 rutas listadas (`afip/configuracion`, etc.).

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/AfipConfigController.php routes/web.php
git commit -m "feat(afip): controller de configuración + rutas (auth + rol)"
```

---

## Task 8: Vista Blade (acordeón + switch)

**Files:**
- Create: `resources/views/afip/configuracion.blade.php`

- [ ] **Step 1: Crear la vista**

```blade
@extends('layout.layout')

@section('content')
<div class="content content-boxed">

    @if(session('afip_msg'))
        <div class="alert alert-success">{{ session('afip_msg') }}</div>
    @endif
    @if(session('afip_error'))
        <div class="alert alert-danger">{{ session('afip_error') }}</div>
    @endif

    {{-- Switch maestro: entorno activo --}}
    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Facturación AFIP — Entorno activo</h3>
        </div>
        <div class="block-content">
            <p>
                Entorno usado en ventas:
                <strong class="{{ optional($activo)->entorno === 'prod' ? 'text-success' : 'text-warning' }}">
                    {{ optional($activo)->entorno === 'prod' ? 'PRODUCCIÓN' : 'HOMOLOGACIÓN' }}
                </strong>
            </p>
            <form action="/afip/activar" method="post" class="form-inline">
                @csrf
                <select name="entorno" class="form-control">
                    <option value="homo" {{ optional($activo)->entorno === 'homo' ? 'selected' : '' }}>Homologación</option>
                    <option value="prod" {{ optional($activo)->entorno === 'prod' ? 'selected' : '' }}>Producción</option>
                </select>
                <button type="submit" class="btn btn-primary">Cambiar entorno activo</button>
            </form>
        </div>
    </div>

    {{-- Acordeón: dos secciones --}}
    <div class="block block-rounded">
        <div class="block-content">
            @foreach(['homo' => 'Homologación', 'prod' => 'Producción'] as $key => $titulo)
                @php $cfg = $entornos[$key]; @endphp
                <div class="block block-bordered">
                    <div class="block-header">
                        <h3 class="block-title">
                            {{ $titulo }}
                            @if($cfg->tieneCredenciales())
                                <span class="label label-success">credenciales cargadas</span>
                            @else
                                <span class="label label-default">sin credenciales</span>
                            @endif
                        </h3>
                    </div>
                    <div class="block-content">

                        {{-- Credenciales --}}
                        <form action="/afip/credenciales/{{ $key }}" method="post">
                            @csrf
                            <div class="row">
                                <div class="col-xs-6">
                                    <label>Clave Privada (key)</label>
                                    <textarea class="form-control" name="key" rows="4" placeholder="-----BEGIN PRIVATE KEY-----"></textarea>
                                </div>
                                <div class="col-xs-6">
                                    <label>Certificado (crt)</label>
                                    <textarea class="form-control" name="cert" rows="4" placeholder="-----BEGIN CERTIFICATE-----"></textarea>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-sm btn-default" style="margin-top:10px;">Guardar credenciales {{ $titulo }}</button>
                        </form>

                        <hr>

                        {{-- Datos --}}
                        <form action="/afip/configuracion/{{ $key }}" method="post">
                            @csrf
                            <div class="row">
                                <div class="col-xs-3">
                                    <label>CUIT</label>
                                    <input type="text" class="form-control" name="cuit" value="{{ $cfg->cuit }}">
                                </div>
                                <div class="col-xs-3">
                                    <label>Punto de Venta</label>
                                    <input type="text" class="form-control" name="ptovta" value="{{ $cfg->ptovta }}">
                                </div>
                                <div class="col-xs-3">
                                    <label>Tipo Comprobante</label>
                                    <select class="form-control" name="comprobante">
                                        <option value="">--</option>
                                        @foreach($tipos[$key] as $t)
                                            <option value="{{ $t->Id }}" {{ (string) $t->Id === (string) $cfg->comprobante ? 'selected' : '' }}>{{ $t->Desc }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-xs-3">
                                    <label>Ing. Brutos</label>
                                    <input type="text" class="form-control" name="ingresos_brutos" value="{{ $cfg->ingresos_brutos }}">
                                </div>
                            </div>
                            <div class="row" style="margin-top:10px;">
                                <div class="col-xs-3">
                                    <label>Inicio de Actividades</label>
                                    <input type="date" class="form-control" name="inicio_actividades" value="{{ $cfg->inicio_actividades }}">
                                </div>
                                <div class="col-xs-3">
                                    <label>Condición frente al IVA</label>
                                    <input type="text" class="form-control" name="condicion_iva" value="{{ $cfg->condicion_iva }}">
                                </div>
                                <div class="col-xs-3">
                                    <label class="css-input switch switch-success" style="margin-top:25px;">
                                        <input type="checkbox" name="emitir" value="1" {{ $cfg->emitir ? 'checked' : '' }}><span></span> Emitir siempre FE
                                    </label>
                                </div>
                                <div class="col-xs-3">
                                    <label class="css-input switch switch-success" style="margin-top:25px;">
                                        <input type="checkbox" name="solicitar_datos" value="1" {{ $cfg->solicitar_datos ? 'checked' : '' }}><span></span> Solicitar datos al comprador
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary" style="margin-top:10px;">Guardar datos {{ $titulo }}</button>
                        </form>

                        <form action="/afip/probar/{{ $key }}" method="post" style="display:inline-block; margin-top:10px;">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success">Probar conexión {{ $titulo }}</button>
                        </form>

                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
```

> **Verificación de layout:** confirmar que `resources/views/layout/layout.blade.php` define `@yield('content')` y una sección `@section('content')`. Si el layout usa otro nombre de yield (p. ej. `@yield('contenido')`), ajustar el `@extends`/`@section` de esta vista para que coincida. Revisar cómo lo hace una vista existente como `resources/views/productos/*.blade.php`.

- [ ] **Step 2: Verificar que la vista compila**

Run: `php artisan view:clear && php -r "require 'vendor/autoload.php';" && echo "ok"`
(La verificación real es manual en la Task 12, abriendo `/afip/configuracion`.)

- [ ] **Step 3: Commit**

```bash
git add resources/views/afip/configuracion.blade.php
git commit -m "feat(afip): vista de configuración (switch + acordeón homo/prod)"
```

---

## Task 9: Enlace en el menú legacy

**Files:**
- Modify: `public/header.php`

- [ ] **Step 1: Localizar el menú de configuración/AFIP actual**

Run: `grep -n "configuracion_afip\|AFIP\|Facturaci" public/header.php`
Expected: alguna entrada de menú apuntando a `configuracion_afip.php` (o el lugar donde corresponde el ítem).

- [ ] **Step 2: Reapuntar (o agregar) el ítem a la nueva pantalla**

Reemplazar el `href="/configuracion_afip.php"` existente por `href="/afip/configuracion"`. Si no existe, agregar dentro del bloque de menú correspondiente (visible para rol ≥ 2):

```php
<li>
    <a href="/afip/configuracion"><i class="si si-settings"></i><span class="sidebar-mini-hide">Facturación AFIP</span></a>
</li>
```

- [ ] **Step 3: Verificar**

Run: `grep -n "afip/configuracion" public/header.php`
Expected: el enlace presente. (Confirmar que ya no apunta a `configuracion_afip.php`.)

- [ ] **Step 4: Commit**

```bash
git add public/header.php
git commit -m "feat(afip): menú legacy apunta a la pantalla Laravel"
```

---

## Task 10: Puente legacy `afip_bridge.php`

**Files:**
- Create: `public/afip_bridge.php`

- [ ] **Step 1: Crear el puente**

```php
<?php
/**
 * Puente transicional entre la facturación legacy (.php) y la configuración
 * AFIP administrada por Laravel.
 *
 * Lee el entorno activo y los datos escalares desde la MISMA tabla `afip_config`
 * que administra Laravel (vía el mysqli de conection.php) y las credenciales
 * cert/key desde storage/app/afip/{entorno}/ (fuera del webroot).
 *
 * NO contiene credenciales. Cuando la facturación migre a Laravel, se elimina.
 */

require_once __DIR__.'/vendor/afipsdk/afip.php/src/Afip.php';
require_once __DIR__.'/conection.php'; // provee $conn (mysqli)

/** Fila del entorno activo (cacheada por request). */
function afip_config_row()
{
    static $row = null;
    static $loaded = false;
    if ($loaded) {
        return $row;
    }
    global $conn;
    $res = mysqli_query($conn, "SELECT * FROM afip_config WHERE activo = 1 LIMIT 1");
    $row = ($res && mysqli_num_rows($res) > 0) ? mysqli_fetch_assoc($res) : null;
    $loaded = true;
    return $row;
}

/** 'homo' | 'prod' (default 'prod' si no hay config, para no romper comportamiento previo). */
function afip_modo()
{
    $r = afip_config_row();
    return $r ? $r['entorno'] : 'prod';
}

/** Valor escalar del entorno activo (cuit, ptovta, comprobante, ...). */
function afip_valor($clave)
{
    $r = afip_config_row();
    return $r && isset($r[$clave]) ? $r[$clave] : null;
}

/** Instancia del SDK configurada para el entorno activo. */
function afip_instance()
{
    $r = afip_config_row();
    if (! $r) {
        throw new Exception('No hay entorno AFIP activo configurado');
    }
    $dir = dirname(__DIR__).'/storage/app/afip/'.$r['entorno'].'/';

    return new Afip(array(
        'CUIT'       => floatval($r['cuit']),
        'production' => $r['entorno'] === 'prod',
        'res_folder' => $dir,
        'ta_folder'  => $dir,
    ));
}
```

- [ ] **Step 2: Smoke test del puente (sintaxis + carga del SDK)**

Run: `php -l public/afip_bridge.php`
Expected: `No syntax errors detected in public/afip_bridge.php`.

- [ ] **Step 3: Commit**

```bash
git add public/afip_bridge.php
git commit -m "feat(afip): puente legacy lee config/credenciales desde Laravel"
```

---

## Task 11: Refactor de los .php de facturación al puente

Reemplazar en cada archivo: (a) la instancia `new Afip(array(...))` por `afip_instance()`, y (b) las lecturas `file_get_contents(.../Afip_res/<clave>)` por `afip_valor('<clave>')`. Agregar `require_once __DIR__.'/afip_bridge.php';` donde hoy hacen `require 'vendor/autoload.php';`.

**Files:** `facturar.php`, `ventas.php`, `nota_de_credito.php`, `nota_de_debito.php`, `devoluciones.php`, `obtener_tiposDocumentos.php`, `obtener_alicuotas.php`, `taxt_types.php`

- [ ] **Step 1: `public/facturar.php`**

En la línea ~208 reemplazar:
```php
$afip = new Afip(array('CUIT' => floatval($cuit), "production" => TRUE));
```
por:
```php
require_once __DIR__.'/afip_bridge.php';
$afip = afip_instance();
```
Luego buscar en el archivo lecturas de datos y reemplazarlas:
```bash
grep -n "Afip_res" public/facturar.php
```
Cada `file_get_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/<clave>")` → `afip_valor('<clave>')`.

- [ ] **Step 2: `public/ventas.php`** — línea ~218, mismo reemplazo de instancia + `grep -n "Afip_res" public/ventas.php` y sustituir lecturas por `afip_valor()`.

- [ ] **Step 3: `public/nota_de_credito.php`** — línea ~93, ídem + `grep -n "Afip_res" public/nota_de_credito.php`.

- [ ] **Step 4: `public/nota_de_debito.php`** — línea ~74, ídem + `grep -n "Afip_res" public/nota_de_debito.php`.

- [ ] **Step 5: `public/devoluciones.php`** — línea ~193, ídem + `grep -n "Afip_res" public/devoluciones.php`.

- [ ] **Step 6: `public/obtener_tiposDocumentos.php`** — línea ~30, ídem + `grep -n "Afip_res" public/obtener_tiposDocumentos.php`.

- [ ] **Step 7: `public/obtener_alicuotas.php`** — línea ~30, ídem + `grep -n "Afip_res" public/obtener_alicuotas.php`.

- [ ] **Step 8: `public/taxt_types.php`** — línea ~12, ídem + `grep -n "Afip_res" public/taxt_types.php`.

- [ ] **Step 9: Verificar sintaxis de todos**

Run:
```bash
for f in facturar ventas nota_de_credito nota_de_debito devoluciones obtener_tiposDocumentos obtener_alicuotas taxt_types; do php -l public/$f.php; done
```
Expected: `No syntax errors detected` en los 8.

- [ ] **Step 10: Verificar que ya no quedan instancias hardcodeadas a producción**

Run: `grep -rn '"production" => TRUE' public/*.php | grep -v facturarbk`
Expected: sin resultados (todas pasaron al puente).

- [ ] **Step 11: Commit**

```bash
git add public/facturar.php public/ventas.php public/nota_de_credito.php public/nota_de_debito.php public/devoluciones.php public/obtener_tiposDocumentos.php public/obtener_alicuotas.php public/taxt_types.php
git commit -m "refactor(afip): facturación legacy usa el puente (modo homo/prod)"
```

---

## Task 12: Badge de entorno en ventas

**Files:**
- Modify: `public/ventas.php`

- [ ] **Step 1: Asegurar el puente disponible y leer el modo**

Cerca del inicio de `public/ventas.php`, después de `require_once("conection.php");`, agregar (si el puente no fue ya incluido en la Task 11):
```php
require_once __DIR__.'/afip_bridge.php';
$afip_modo_actual = afip_modo();
```

- [ ] **Step 2: Renderizar el badge en el encabezado de la página**

En la zona del header/título de la vista (dentro del HTML), insertar:
```php
<span class="label <?php echo $afip_modo_actual === 'prod' ? 'label-success' : 'label-warning'; ?>" style="font-size:14px;">
    AFIP: <?php echo $afip_modo_actual === 'prod' ? 'PRODUCCIÓN' : 'HOMOLOGACIÓN'; ?>
</span>
```

- [ ] **Step 3: Verificar sintaxis**

Run: `php -l public/ventas.php`
Expected: `No syntax errors detected`.

- [ ] **Step 4: Commit**

```bash
git add public/ventas.php
git commit -m "feat(afip): badge de entorno (homo/prod) en ventas"
```

---

## Task 13: .gitignore + .gitkeep de storage

**Files:**
- Modify: `.gitignore`
- Create: `storage/app/afip/.gitkeep`

- [ ] **Step 1: Crear la carpeta y su .gitkeep**

```bash
mkdir -p storage/app/afip && touch storage/app/afip/.gitkeep
```

- [ ] **Step 2: Agregar a `.gitignore`**

Agregar al final:
```gitignore
# Credenciales AFIP (nunca commitear)
/storage/app/afip/*
!/storage/app/afip/.gitkeep
```

- [ ] **Step 3: Verificar que git ignora las credenciales**

Run:
```bash
mkdir -p storage/app/afip/homo && echo x > storage/app/afip/homo/cert && git check-ignore storage/app/afip/homo/cert; rm -rf storage/app/afip/homo
```
Expected: imprime `storage/app/afip/homo/cert` (está ignorado).

- [ ] **Step 4: Commit**

```bash
git add .gitignore storage/app/afip/.gitkeep
git commit -m "chore(afip): gitignore de credenciales + .gitkeep de storage"
```

---

## Task 14: Verificación manual end-to-end (con credenciales de homologación)

**Sin commits.** Prerequisitos: app servida con servidor PHP real (document root `public/`), DB `laravel` migrada y con `afip_config` poblada (`php artisan migrate && php artisan db:seed --class=AfipConfigSeeder`).

- [ ] **Step 1: Migrar y seedear**

Run:
```bash
php artisan migrate
php artisan db:seed --class=AfipConfigSeeder
php artisan config:clear
```
Expected: tabla `afip_config` con filas `homo` (activo) y `prod`.

- [ ] **Step 2: Autenticarse (cookies legacy)**

Loguearse vía `public/login.php` con un usuario de rol ≥ 2 (o forjar cookies legacy según `~/.claude/.../memory/carga-grid-serverside.md`).

- [ ] **Step 3: Abrir la pantalla**

Navegar a `/afip/configuracion`. Expected: se ve el switch + acordeón con secciones Homologación y Producción.

- [ ] **Step 4: Cargar credenciales de homologación**

Pegar key + cert reales de homologación en la sección Homologación → "Guardar credenciales Homologación". Cargar CUIT, punto de venta y tipo de comprobante → "Guardar datos Homologación". Expected: mensaje de éxito; el label cambia a "credenciales cargadas".

- [ ] **Step 5: Probar conexión**

Click "Probar conexión Homologación". Expected: "Conexión OK. Último comprobante autorizado: N". Si falla, revisar `storage/logs/laravel.log`.

- [ ] **Step 6: Switch + badge**

Con el entorno activo en `homo`, abrir `/ventas.php`. Expected: badge amarillo "AFIP: HOMOLOGACIÓN".

- [ ] **Step 7: Emitir un comprobante de prueba en homologación**

Hacer una venta/facturación de prueba. Expected: se genera CAE contra el entorno de homologación (no producción).

- [ ] **Step 8: Correr toda la suite de tests**

Run: `vendor/bin/phpunit`
Expected: PASS (tests de Task 4 y 5).

---

## Task 15: Cleanup legacy (cutover) — EJECUTAR AL FINAL

⚠️ **Orden crítico.** Solo después de que la Task 14 pase y de haber cargado **también** las credenciales de producción en la pantalla nueva (sección Producción) si se va a seguir facturando real. Al borrar las credenciales de `public/`, la facturación de producción depende 100% de lo cargado en Laravel.

**Files (eliminar):**
- `public/configuracion_afip.php`, `public/guardar_certificados.php`, `public/emitir_online.php`, `public/solicitar_datos.php`, `public/assets/js/pages/afip.js`
- Credenciales en `public/AFIP/{cert,key}` y `public/vendor/afipsdk/afip.php/src/Afip_res/{cert,key,cuit,ptovta,comprobante,condicion_iva,inicio_actividades,ingresos_brutos,emitir,solicitar_datos}` y los `TA-*.xml`/`TRA-*.xml`

- [ ] **Step 1: Confirmar que no quedan referencias a los .php que se eliminan**

Run:
```bash
grep -rn "configuracion_afip\|guardar_certificados\|emitir_online\|solicitar_datos\|pages/afip.js" public/ resources/ routes/ app/ | grep -v "afip_bridge\|afip/configuracion"
```
Expected: sin resultados (o solo en archivos que también se eliminan). Reapuntar cualquier referencia restante antes de borrar.

- [ ] **Step 2: Eliminar los .php reemplazados**

```bash
git rm public/configuracion_afip.php public/guardar_certificados.php public/emitir_online.php public/solicitar_datos.php public/assets/js/pages/afip.js
```

- [ ] **Step 3: Eliminar credenciales del webroot (conservar .wsdl y .htaccess)**

```bash
git rm -f public/AFIP/cert public/AFIP/key
cd public/vendor/afipsdk/afip.php/src/Afip_res
git rm -f cert key cuit ptovta comprobante condicion_iva inicio_actividades ingresos_brutos emitir solicitar_datos
git rm -f TA-*.xml TRA-*.xml
cd -
```

- [ ] **Step 4: Verificar que el SDK conserva sus WSDL**

Run: `ls public/vendor/afipsdk/afip.php/src/Afip_res/*.wsdl public/vendor/afipsdk/afip.php/src/Afip_res/.htaccess`
Expected: los `.wsdl` y el `.htaccess` siguen presentes.

- [ ] **Step 5: Smoke de facturación (homologación) tras el borrado**

Repetir Task 14 Step 7 (emisión de prueba en homo). Expected: sigue funcionando leyendo desde `storage/app/afip/homo/`.

- [ ] **Step 6: Commit**

```bash
git commit -m "chore(afip): elimina pantallas legacy y credenciales del webroot"
```

---

## Task 16: Documentar deploy

**Files:**
- Modify: `deploy/README.md`

- [ ] **Step 1: Agregar sección de credenciales AFIP**

Agregar al `deploy/README.md`:
```markdown
## Credenciales AFIP (post-deploy)

Las credenciales AFIP ya NO viven en el webroot. Se cargan desde la pantalla
Laravel `/afip/configuracion` (rol ≥ 2) y se guardan en `storage/app/afip/{homo,prod}/`.

Requisitos en el server:
- `storage/app/afip/` debe ser escribible por el usuario del web server
  (el SDK escribe ahí el cache de tokens `TA-*.xml`):
  `chown -R www-data:www-data storage/app/afip && chmod -R 750 storage/app/afip`
- Tras el primer deploy: `php artisan migrate && php artisan db:seed --class=AfipConfigSeeder`
- Cargar credenciales de homologación y producción desde la pantalla.
- El switch de entorno activo se cambia desde la misma pantalla (global).
```

- [ ] **Step 2: Commit**

```bash
git add deploy/README.md
git commit -m "docs(deploy): carga de credenciales AFIP y permisos de storage"
```

---

## Self-review (cobertura del spec)

- DB `afip_config` + invariante `activo` único → Task 3, 4 ✓
- Credenciales fuera del webroot → Task 5 (`guardarCredenciales`), Task 13 (gitignore) ✓
- Model / Service / Controller / Blade → Tasks 4, 5, 7, 8 ✓
- Switch global + acordeón 2 secciones + "Guardar y Probar" → Task 8 ✓
- Puente legacy desde la misma tabla → Task 10; refactor billing → Task 11 ✓
- Badge en ventas → Task 12 ✓
- Limpieza de .php + credenciales públicas → Task 15 ✓
- Punto operacional / orden de cutover → Task 14 + Task 15 (guardas explícitas) ✓
- Seguridad (auth+rol, CSRF, mysqli_prepare/SQL estática, no filtrar errores) → Task 7, 5, 10 ✓
- Testing (PHPUnit base + unit + manual) → Tasks 1, 4, 5, 14 ✓
- Deploy (permisos storage) → Task 16 ✓
- Default `homo` activo → Task 6 ✓
