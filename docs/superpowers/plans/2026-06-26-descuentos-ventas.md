# Descuentos en ventas — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Permitir descuentos porcentuales en ventas (fijo por producto + total por venta, apilados, opcionales), reflejados en factura/AFIP, persistidos y auditados.

**Architecture:** La matemática de venta + IVA se centraliza en una clase PHP pura testeable (`App\Ventas\CalculadoraVenta`), autoloadeada por Composer y consumida tanto por el motor legacy (`public/facturar.php`) como por los tests. El motor AFIP y las pantallas siguen en legacy; solo se les inyecta el resultado ya descontado. La auditoría usa una tabla nueva `descuentos_logs` (Eloquent `App\Models\DescuentoLog` del lado Laravel; INSERT preparado del lado legacy).

**Tech Stack:** PHP 7.2 / Laravel 7, MySQL (prod) / SQLite in-memory (tests), PHPUnit, jQuery (legacy JS), Laravel Mix.

## Global Constraints

- Spec de referencia: `docs/superpowers/specs/2026-06-26-descuentos-ventas-design.md` (decisiones de alcance verbatim).
- PHP floor **7.2.5**: NO usar arrow functions (`fn()` es 7.4). Usar closures clásicas.
- El descuento es **siempre %**; clamp a `[0, 100]`; no numérico → `0`.
- Apilado: primero descuento de **línea** (producto), luego descuento **total** sobre ese subtotal.
- AFIP recibe el **total ya descontado**; NO se cambia la estructura del request WSFE.
- `precio` se mantiene como **precio original** en todas las tablas; el descuento va en columna aparte.
- Columnas de descuento: `DECIMAL(5,2) DEFAULT 0`. `descuentos_logs.monto_descontado`: `DECIMAL(12,2)`.
- No tocar verbos de rutas destructivas; no hardcodear credenciales; no usar `--no-verify`.
- Tests corren en Docker: `docker compose exec sigav_app vendor/bin/phpunit` (driver `pdo_sqlite`, `phpunit.xml` ya configurado a `:memory:`).
- Trailer de commit obligatorio:
  ```
  Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>
  ```

## Branch

Antes de la Task 1, crear la rama del feature desde la rama base actual:

```bash
git checkout -b feat/descuentos-ventas
```

## File Structure

| Archivo | Acción | Responsabilidad |
|---------|--------|-----------------|
| `app/Ventas/CalculadoraVenta.php` | Crear | Cálculo puro: descuentos apilados + subtotal/total/neto/iva. |
| `tests/Unit/CalculadoraVentaTest.php` | Crear | Tests del núcleo de cálculo (sin DB). |
| `database/migrations/2026_06_26_100000_create_descuentos_logs_table.php` | Crear | Tabla de auditoría. |
| `database/migrations/2026_06_26_100100_add_descuento_columns.php` | Crear | Columnas de descuento en tablas legacy (guarded). |
| `app/Models/DescuentoLog.php` | Crear | Modelo Eloquent de `descuentos_logs`. |
| `tests/Unit/DescuentoLogTest.php` | Crear | Persistencia del log + columnas/default de la migración. |
| `app/Models/Producto.php` | Modificar | `descuento` en `$fillable`. |
| `app/Http/Controllers/ProductoController.php` | Modificar | Guardar `descuento`, auditar `DESCUENTO_PRODUCTO_CONFIG`, exponerlo en `searchProducts`. |
| `resources/views/productos/_accion.blade.php` | Modificar | Input "Descuento %". |
| `public/assets/js/pages/carga.js` | Modificar | Prefill del `descuento` al editar. |
| `public/ventas_post.php` | Modificar | INSERT explícito al carrito + snapshot del `descuento`. |
| `public/facturar.php` | Modificar | Usar `CalculadoraVenta`, persistir descuentos, log `DESCUENTO_TOTAL`, AFIP con total descontado, PDF. |
| `public/assets/js/pages/ventas.js` | Modificar | Total en vivo con descuentos + input "Descuento total %" + envío del parámetro. |
| `public/ventas.php` | Modificar | Mostrar descuento por línea en la grilla "Ventas de hoy". |

**Esquema legacy real (del dump `dump/c2101314_ma.sql`) — los INSERT actuales son posicionales y se rompen al agregar una columna:**

- `productos_en_carrito(id, venta_id, producto_id, estado, fecha, usuario, sucursal_id, cantidad, precio, costo)` → se agrega `descuento`.
- `ventas(id, productos_id, cantidad, precio, costo, fecha, usuario, sucursal_id, estado, factura_id, tipo_pago, lista_precio)` → se agrega `descuento`.
- `factura(... documento, mail, tipo_documento, iva)` → se agrega `descuento_total`. (El INSERT de `factura` ya es por columnas explícitas.)
- `productos(...)` → se agrega `descuento`.

---

### Task 1: `CalculadoraVenta` (núcleo de cálculo, TDD)

**Files:**
- Create: `app/Ventas/CalculadoraVenta.php`
- Test: `tests/Unit/CalculadoraVentaTest.php`

**Interfaces:**
- Consumes: nada (clase pura).
- Produces: `App\Ventas\CalculadoraVenta::calcular(array $lineas, float $descuentoTotalPct): array`.
  - `$lineas`: `[ ['precio'=>float, 'cantidad'=>int|float, 'descuento'=>float], ... ]`.
  - Retorno: `['subtotalBruto'=>float, 'subtotal'=>float, 'descuentoTotalMonto'=>float, 'total'=>float, 'neto'=>float, 'iva'=>float, 'lineas'=>[ ['bruto'=>float,'descuentoMonto'=>float,'subtotal'=>float], ... ]]`.

- [ ] **Step 1: Escribir el test que falla**

Create `tests/Unit/CalculadoraVentaTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Ventas\CalculadoraVenta;
use PHPUnit\Framework\TestCase;

class CalculadoraVentaTest extends TestCase
{
    /** @test */
    public function sin_descuentos_total_igual_a_bruto()
    {
        $r = CalculadoraVenta::calcular(
            [['precio' => 100, 'cantidad' => 2, 'descuento' => 0],
             ['precio' => 50,  'cantidad' => 1, 'descuento' => 0]],
            0
        );
        $this->assertSame(250.0, $r['subtotalBruto']);
        $this->assertSame(250.0, $r['subtotal']);
        $this->assertSame(0.0, $r['descuentoTotalMonto']);
        $this->assertSame(250.0, $r['total']);
    }

    /** @test */
    public function solo_descuento_de_linea()
    {
        $r = CalculadoraVenta::calcular([['precio' => 100, 'cantidad' => 1, 'descuento' => 10]], 0);
        $this->assertSame(100.0, $r['subtotalBruto']);
        $this->assertSame(90.0, $r['subtotal']);
        $this->assertSame(90.0, $r['total']);
        $this->assertSame(10.0, $r['lineas'][0]['descuentoMonto']);
    }

    /** @test */
    public function solo_descuento_total()
    {
        $r = CalculadoraVenta::calcular([['precio' => 100, 'cantidad' => 1, 'descuento' => 0]], 10);
        $this->assertSame(100.0, $r['subtotal']);
        $this->assertSame(10.0, $r['descuentoTotalMonto']);
        $this->assertSame(90.0, $r['total']);
    }

    /** @test */
    public function descuentos_apilados_linea_primero_luego_total()
    {
        // línea: 100 -10% = 90 ; total: 90 -10% = 81
        $r = CalculadoraVenta::calcular([['precio' => 100, 'cantidad' => 1, 'descuento' => 10]], 10);
        $this->assertSame(90.0, $r['subtotal']);
        $this->assertSame(9.0, $r['descuentoTotalMonto']);
        $this->assertSame(81.0, $r['total']);
    }

    /** @test */
    public function redondeo_a_centavos()
    {
        $r = CalculadoraVenta::calcular([['precio' => 100, 'cantidad' => 1, 'descuento' => 33.33]], 0);
        $this->assertSame(66.67, $r['subtotal']);
        $this->assertSame(66.67, $r['total']);
    }

    /** @test */
    public function iva_se_calcula_sobre_el_total_ya_descontado()
    {
        // total 121 -> neto 100, iva 21
        $r = CalculadoraVenta::calcular([['precio' => 121, 'cantidad' => 1, 'descuento' => 0]], 0);
        $this->assertSame(121.0, $r['total']);
        $this->assertSame(100.0, $r['neto']);
        $this->assertSame(21.0, $r['iva']);
    }

    /** @test */
    public function borde_cien_por_ciento_de_linea_deja_total_en_cero()
    {
        $r = CalculadoraVenta::calcular([['precio' => 100, 'cantidad' => 1, 'descuento' => 100]], 0);
        $this->assertSame(0.0, $r['subtotal']);
        $this->assertSame(0.0, $r['total']);
    }

    /** @test */
    public function porcentaje_no_numerico_se_trata_como_cero()
    {
        $r = CalculadoraVenta::calcular([['precio' => 100, 'cantidad' => 1, 'descuento' => 'abc']], 'xx');
        $this->assertSame(100.0, $r['total']);
    }

    /** @test */
    public function porcentaje_fuera_de_rango_se_clampea()
    {
        // descuento de línea 150 -> 100% ; descuento total -10 -> 0%
        $r = CalculadoraVenta::calcular([['precio' => 100, 'cantidad' => 1, 'descuento' => 150]], -10);
        $this->assertSame(0.0, $r['subtotal']);
        $this->assertSame(0.0, $r['total']);
    }

    /** @test */
    public function carrito_vacio_da_todo_cero()
    {
        $r = CalculadoraVenta::calcular([], 50);
        $this->assertSame(0.0, $r['subtotalBruto']);
        $this->assertSame(0.0, $r['subtotal']);
        $this->assertSame(0.0, $r['descuentoTotalMonto']);
        $this->assertSame(0.0, $r['total']);
        $this->assertSame(0.0, $r['neto']);
        $this->assertSame(0.0, $r['iva']);
    }

    /** @test */
    public function invariante_subtotal_menos_descuento_total_igual_total()
    {
        $r = CalculadoraVenta::calcular(
            [['precio' => 123.45, 'cantidad' => 2, 'descuento' => 7.5],
             ['precio' => 10,     'cantidad' => 3, 'descuento' => 0]],
            12.5
        );
        $this->assertSame($r['total'], round($r['subtotal'] - $r['descuentoTotalMonto'], 2));
    }
}
```

- [ ] **Step 2: Correr el test y verificar que falla**

Run: `docker compose exec sigav_app vendor/bin/phpunit --filter CalculadoraVentaTest`
Expected: FAIL — `Class "App\Ventas\CalculadoraVenta" not found`.

- [ ] **Step 3: Implementar la clase**

Create `app/Ventas/CalculadoraVenta.php`:

```php
<?php

declare(strict_types=1);

namespace App\Ventas;

/**
 * Cálculo puro de una venta con descuentos porcentuales apilados.
 * Sin DB ni cookies: única fuente de la matemática de venta + IVA.
 */
class CalculadoraVenta
{
    /** Alícuota de IVA en %. */
    private const IVA_PCT = 21.0;

    /**
     * @param array $lineas Lista de ['precio'=>float, 'cantidad'=>int|float, 'descuento'=>float(%)]
     * @param float|mixed $descuentoTotalPct % de descuento sobre el subtotal ya descontado por línea
     * @return array
     */
    public static function calcular(array $lineas, $descuentoTotalPct): array
    {
        $subtotalBruto = 0.0;
        $subtotal = 0.0;
        $detalle = [];

        foreach ($lineas as $linea) {
            $precio    = self::num(isset($linea['precio']) ? $linea['precio'] : 0);
            $cantidad  = self::num(isset($linea['cantidad']) ? $linea['cantidad'] : 0);
            $descuento = self::clampPct(isset($linea['descuento']) ? $linea['descuento'] : 0);

            $bruto = round($precio * $cantidad, 2);
            $lineaConDesc = round($bruto * (1 - $descuento / 100), 2);
            $descuentoMonto = round($bruto - $lineaConDesc, 2);

            $subtotalBruto += $bruto;
            $subtotal += $lineaConDesc;

            $detalle[] = [
                'bruto'          => $bruto,
                'descuentoMonto' => $descuentoMonto,
                'subtotal'       => $lineaConDesc,
            ];
        }

        $subtotalBruto = round($subtotalBruto, 2);
        $subtotal = round($subtotal, 2);

        $descTotalPct = self::clampPct($descuentoTotalPct);
        $descuentoTotalMonto = round($subtotal * $descTotalPct / 100, 2);
        $total = round($subtotal - $descuentoTotalMonto, 2);

        $neto = round($total / (1 + self::IVA_PCT / 100), 2);
        $iva = round($neto * (self::IVA_PCT / 100), 2);

        return [
            'subtotalBruto'       => $subtotalBruto,
            'subtotal'            => $subtotal,
            'descuentoTotalMonto' => $descuentoTotalMonto,
            'total'               => $total,
            'neto'                => $neto,
            'iva'                 => $iva,
            'lineas'              => $detalle,
        ];
    }

    /** @param mixed $v */
    private static function num($v): float
    {
        return is_numeric($v) ? (float) $v : 0.0;
    }

    /** Normaliza y clampea un porcentaje a [0, 100]. @param mixed $v */
    private static function clampPct($v): float
    {
        $n = self::num($v);
        if ($n < 0) {
            return 0.0;
        }
        if ($n > 100) {
            return 100.0;
        }
        return $n;
    }
}
```

- [ ] **Step 4: Correr el test y verificar que pasa**

Run: `docker compose exec sigav_app vendor/bin/phpunit --filter CalculadoraVentaTest`
Expected: PASS (11 tests).

Si el autoload no resuelve la clase nueva: `docker compose exec sigav_app composer dump-autoload`.

- [ ] **Step 5: Commit**

```bash
git add app/Ventas/CalculadoraVenta.php tests/Unit/CalculadoraVentaTest.php
git commit -m "feat(ventas): CalculadoraVenta pura con descuentos apilados e IVA"
```

---

### Task 2: Migraciones (auditoría + columnas) y modelo `DescuentoLog`

**Files:**
- Create: `database/migrations/2026_06_26_100000_create_descuentos_logs_table.php`
- Create: `database/migrations/2026_06_26_100100_add_descuento_columns.php`
- Create: `app/Models/DescuentoLog.php`
- Test: `tests/Unit/DescuentoLogTest.php`

**Interfaces:**
- Consumes: nada.
- Produces:
  - Tabla `descuentos_logs(id, usuario, sucursal_id, tipo_operacion, factura_id, productos_id, descuento_anterior, descuento_nuevo, monto_descontado, created_at, updated_at)`.
  - Columnas `productos.descuento`, `productos_en_carrito.descuento`, `ventas.descuento`, `factura.descuento_total` (`DECIMAL(5,2) DEFAULT 0`).
  - `App\Models\DescuentoLog` (Eloquent, `$table='descuentos_logs'`, timestamps automáticos, `$guarded=['id']`).

- [ ] **Step 1: Escribir el test que falla**

Create `tests/Unit/DescuentoLogTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Models\DescuentoLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DescuentoLogTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function persiste_un_descuento_total_con_timestamps()
    {
        $log = DescuentoLog::create([
            'usuario'           => 'cajero1',
            'sucursal_id'       => 3,
            'tipo_operacion'    => 'DESCUENTO_TOTAL',
            'factura_id'        => 42,
            'descuento_nuevo'   => 15.00,
            'monto_descontado'  => 123.45,
        ]);

        $this->assertNotNull($log->id);
        $this->assertNotNull($log->created_at);
        $this->assertDatabaseHas('descuentos_logs', [
            'tipo_operacion'   => 'DESCUENTO_TOTAL',
            'factura_id'       => 42,
            'descuento_nuevo'  => 15.00,
            'monto_descontado' => 123.45,
        ]);
    }

    /** @test */
    public function la_migracion_agrega_columnas_de_descuento_con_default_cero()
    {
        // Simula las tablas legacy (no existen en migraciones Laravel) y corre la migración.
        foreach (['productos', 'productos_en_carrito', 'ventas'] as $tabla) {
            Schema::create($tabla, function ($t) {
                $t->increments('id');
            });
        }
        Schema::create('factura', function ($t) {
            $t->increments('id');
        });

        (new \AddDescuentoColumns())->up();

        $this->assertTrue(Schema::hasColumn('productos', 'descuento'));
        $this->assertTrue(Schema::hasColumn('productos_en_carrito', 'descuento'));
        $this->assertTrue(Schema::hasColumn('ventas', 'descuento'));
        $this->assertTrue(Schema::hasColumn('factura', 'descuento_total'));

        $id = \DB::table('productos')->insertGetId([]);
        $this->assertEquals(0, (float) \DB::table('productos')->where('id', $id)->value('descuento'));
    }
}
```

- [ ] **Step 2: Correr el test y verificar que falla**

Run: `docker compose exec sigav_app vendor/bin/phpunit --filter DescuentoLogTest`
Expected: FAIL — modelo/migración inexistentes.

- [ ] **Step 3: Crear la migración de auditoría**

Create `database/migrations/2026_06_26_100000_create_descuentos_logs_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDescuentosLogsTable extends Migration
{
    public function up()
    {
        Schema::create('descuentos_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('usuario', 200)->nullable();
            $table->integer('sucursal_id')->nullable();
            $table->string('tipo_operacion', 50);
            $table->integer('factura_id')->nullable();
            $table->integer('productos_id')->nullable();
            $table->decimal('descuento_anterior', 5, 2)->nullable();
            $table->decimal('descuento_nuevo', 5, 2)->default(0);
            $table->decimal('monto_descontado', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('descuentos_logs');
    }
}
```

- [ ] **Step 4: Crear la migración de columnas (guarded, sin arrow fns)**

Create `database/migrations/2026_06_26_100100_add_descuento_columns.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega columnas de descuento a tablas legacy (no creadas por migraciones Laravel).
 * Guarded con hasTable/hasColumn para ser idempotente en prod y no fallar en SQLite (tests).
 */
class AddDescuentoColumns extends Migration
{
    /** @var array<int, array{0:string,1:string}> tabla => columna */
    private $columnas = [
        ['productos', 'descuento'],
        ['productos_en_carrito', 'descuento'],
        ['ventas', 'descuento'],
        ['factura', 'descuento_total'],
    ];

    public function up()
    {
        foreach ($this->columnas as $par) {
            list($tabla, $columna) = $par;
            if (Schema::hasTable($tabla) && !Schema::hasColumn($tabla, $columna)) {
                Schema::table($tabla, function (Blueprint $t) use ($columna) {
                    $t->decimal($columna, 5, 2)->default(0);
                });
            }
        }
    }

    public function down()
    {
        foreach ($this->columnas as $par) {
            list($tabla, $columna) = $par;
            if (Schema::hasTable($tabla) && Schema::hasColumn($tabla, $columna)) {
                Schema::table($tabla, function (Blueprint $t) use ($columna) {
                    $t->dropColumn($columna);
                });
            }
        }
    }
}
```

- [ ] **Step 5: Crear el modelo**

Create `app/Models/DescuentoLog.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DescuentoLog extends Model
{
    protected $table = 'descuentos_logs';

    protected $guarded = ['id'];
}
```

- [ ] **Step 6: Correr el test y verificar que pasa**

Run: `docker compose exec sigav_app vendor/bin/phpunit --filter DescuentoLogTest`
Expected: PASS (2 tests).

- [ ] **Step 7: Aplicar las migraciones en la base real (MySQL)**

> Solo en entorno con la base legacy. En prod, ejecutar tras desplegar el código.

Run: `docker compose exec sigav_app php artisan migrate`
Expected: corre `CreateDescuentosLogsTable` y `AddDescuentoColumns` sin error.

- [ ] **Step 8: Commit**

```bash
git add database/migrations/2026_06_26_100000_create_descuentos_logs_table.php \
        database/migrations/2026_06_26_100100_add_descuento_columns.php \
        app/Models/DescuentoLog.php tests/Unit/DescuentoLogTest.php
git commit -m "feat(ventas): tabla descuentos_logs, columnas de descuento y modelo DescuentoLog"
```

---

### Task 3: Descuento de producto (Laravel) — config, auditoría y API de búsqueda

**Files:**
- Modify: `app/Models/Producto.php:13-15` (`$fillable`)
- Modify: `app/Http/Controllers/ProductoController.php` (`store`: ~234-235, ~285, ~305; `searchProducts`: ~488)
- Modify: `resources/views/productos/_accion.blade.php` (input nuevo, junto a `precio_unidad`)
- Modify: `public/assets/js/pages/carga.js:251` (prefill)

**Interfaces:**
- Consumes: `App\Models\DescuentoLog` (Task 2), columna `productos.descuento` (Task 2).
- Produces: JSON de `searchProducts` con clave `descuento`; registro `DESCUENTO_PRODUCTO_CONFIG` en `descuentos_logs`.

> Nota: en este controlador `store()` maneja **alta y edición** (edición cuando `$request->id != ""`). Los métodos `edit()`/`update()` son stubs vacíos. El formulario de producto se prefilla con `GET /carga/{id}` (`show()` → `Producto::find()` JSON), por lo que `jsonData.descuento` queda disponible al existir la columna.

- [ ] **Step 1: Agregar `descuento` a `$fillable` del modelo**

En `app/Models/Producto.php`, agregar `'descuento'` al array `$fillable` (línea 13-15):

```php
	protected $fillable = ['codigo_barras', 'nombre', 'precio_unidad', 'costo', 'stock', 'stock_minimo',
		'proveedores_id', 'categorias_id', 'precio_mayorista', 'es_comodato', 'descripcion',
		'descripcion_pr', 'descripcion_en', 'material', 'precio_reposicion', 'descuento'];
```

- [ ] **Step 2: Capturar el descuento anterior al inicio de `store()`**

En `app/Http/Controllers/ProductoController.php`, dentro de `store()` (después de `$codigo_barras='';`, línea ~231), inicializar:

```php
        $descuentoAnterior = null;
```

Y dentro del bloque `if ($request->id != ""){` (línea 234), después de `$productos = Producto::find($request->id);` (línea 235), agregar:

```php
            $descuentoAnterior = $productos->descuento;
```

- [ ] **Step 3: Setear el descuento del producto (alta y edición)**

En `store()`, junto al seteo de `precio_unidad` (después de la línea 286 `$productoApi[0]['precio_unidad']= $request->precio_unidad;`), agregar:

```php
        $productos->descuento = is_numeric($request->descuento)
            ? max(0, min(100, floatval($request->descuento)))
            : 0;
```

- [ ] **Step 4: Auditar el cambio de config tras `$productos->save();`**

En `store()`, inmediatamente después de `$productos->save();` (línea 305) y antes de `$productoApi[0]['id']=$productos->id;` (línea 306), agregar:

```php
        if (floatval($descuentoAnterior) != floatval($productos->descuento)) {
            $descuentoLog = new \App\Models\DescuentoLog();
            $descuentoLog->usuario            = $_COOKIE["kiosco"];
            $descuentoLog->sucursal_id        = $sucursal;
            $descuentoLog->tipo_operacion     = 'DESCUENTO_PRODUCTO_CONFIG';
            $descuentoLog->productos_id       = $productos->id;
            $descuentoLog->descuento_anterior = $descuentoAnterior;
            $descuentoLog->descuento_nuevo    = $productos->descuento;
            $descuentoLog->save();
        }
```

> `$sucursal` ya está definido al inicio de `store()` (`$sucursal=Sucursales::getSucursal();`, línea 232).

- [ ] **Step 5: Exponer `descuento` en `searchProducts` (la API que usa la pantalla de venta)**

En `searchProducts`, dentro del primer `foreach` (el de `tipoBusqueda`, líneas 477-490), después de `$datos[$i]["stockactual"] = $producto->stockactual;` (línea 488), agregar:

```php
            $datos[$i]["descuento"]     = ($producto->descuento !== null) ? floatval($producto->descuento) : 0;
```

- [ ] **Step 6: Agregar el input "Descuento %" al formulario de producto**

En `resources/views/productos/_accion.blade.php`, después del bloque del input `precio_reposicion` (input en línea 67), agregar un bloque análogo:

```html
                <div class="form-group">
                    <label for="descuento">Descuento (%)</label>
                    <input type="text" class="form-control numbers" name="descuento" id="descuento" value="0" placeholder="Descuento del producto en % (0 a 100)" />
                </div>
```

> Usar la misma envoltura `form-group`/`label` que los inputs vecinos del archivo; ajustar las clases CSS para que matcheen el markup real circundante.

- [ ] **Step 7: Prefill del descuento al editar**

En `public/assets/js/pages/carga.js`, después de `$("#precio_mayorista").val(jsonData.precio_mayorista);` (línea 251), agregar:

```javascript
            $("#descuento").val(jsonData.descuento != null ? jsonData.descuento : 0);
```

- [ ] **Step 8: Build de assets**

Run: `npm run dev`
Expected: compila sin error (cambios en JS legacy dentro de `public/assets` se sirven directo; correr el build igual para validar que nada se rompió).

- [ ] **Step 9: Verificación manual**

1. Editar un producto en `/carga`, setear Descuento = 20, guardar.
2. Verificar fila en `descuentos_logs`:
   `docker compose exec sigav_app php artisan tinker --execute="echo App\Models\DescuentoLog::latest('id')->first();"`
   Esperado: `tipo_operacion = DESCUENTO_PRODUCTO_CONFIG`, `productos_id` correcto, `descuento_nuevo = 20`.
3. Buscar ese producto en la API: `GET /etiqueta.buscarProductos?producto=<nombre>&tipoBusqueda=1` → el JSON incluye `"descuento":20`.

- [ ] **Step 10: Commit**

```bash
git add app/Models/Producto.php app/Http/Controllers/ProductoController.php \
        resources/views/productos/_accion.blade.php public/assets/js/pages/carga.js \
        public/js
git commit -m "feat(ventas): descuento configurable por producto con auditoria DESCUENTO_PRODUCTO_CONFIG"
```

> `public/js` solo si `npm run dev` regeneró bundles versionados; omitir si no cambió nada compilado.

---

### Task 4: Snapshot del descuento al agregar al carrito (`ventas_post.php`)

**Files:**
- Modify: `public/ventas_post.php:60-72` (lectura del producto) y `:100-113` (INSERT al carrito)

**Interfaces:**
- Consumes: columna `productos_en_carrito.descuento` (Task 2), `productos.descuento` (Task 2).
- Produces: filas de `productos_en_carrito` con `descuento` = snapshot del `%` del producto al momento de agregar.

> El INSERT actual es **posicional** (`INSERT ... VALUES(...)` sin lista de columnas) y se rompe al agregar la columna. Se convierte a INSERT por columnas explícitas con sentencia preparada (parametrizada). El `descuento` se toma del producto leído de la DB (línea 60-66), no del front-end.

- [ ] **Step 1: Capturar el descuento del producto leído**

En `public/ventas_post.php`, dentro del `while($row = $resultado->fetch_assoc())` (líneas 66-73), después de `$precio = $row["precio_unidad"];` (línea 69), agregar:

```php
        $descuento = is_numeric($row["descuento"]) ? max(0, min(100, floatval($row["descuento"]))) : 0;
```

> Si el producto no trae `descuento` (carrito creado antes de la migración), `$row["descuento"]` será `null` → `0`. Inicializar también `$descuento = 0;` antes del `if ($resultado->num_rows > 0)` (línea 64) para el caso sin filas.

- [ ] **Step 2: Reemplazar el INSERT posicional por uno explícito y preparado**

Reemplazar el bloque del `$sql = "INSERT INTO productos_en_carrito VALUES (...)"` (líneas 101-113) y su ejecución `if ($conn->query($sql) === TRUE) {` (línea 118) por:

```php
$sql = "INSERT INTO productos_en_carrito
        (venta_id, producto_id, estado, fecha, usuario, sucursal_id, cantidad, precio, costo, descuento)
        VALUES (?, ?, 0, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$fecha_carrito = date("Y-m-d H:i:s");
$usuario_carrito = $_COOKIE["kiosco"];
$sucursal_carrito = getSucursal($_COOKIE["sucursal"]);
$cantidad_carrito = $_POST["cantidad"];
$stmt->bind_param(
    "iississdd",
    $ventas_id,
    $_POST["id"],
    $fecha_carrito,
    $usuario_carrito,
    $sucursal_carrito,
    $cantidad_carrito,
    $precio,
    $costo,
    $descuento
);

if ($stmt->execute()) {
```

Y reemplazar el `else { echo "Error: " . $sql . "<br>" . $conn->error; }` que cierra ese `if` (líneas 134-136) por:

```php
} else {
    echo "Error: " . $stmt->error;
}
$stmt->close();
```

> Mantener intacto el contenido interno del `if` (líneas 119-133: `$datos["ventas_id"] = $ventas_id; ... echo json_encode($datos);`). Los tipos de `bind_param`: `i`=int, `s`=string, `d`=double. Verificar que el orden de tipos `"iississdd"` matchee los 9 parámetros.

- [ ] **Step 3: Verificación manual**

1. En `/ventas.php`, agregar al carrito un producto con descuento 20%.
2. Inspeccionar la fila:
   `docker compose exec sigav_app php artisan tinker --execute="print_r(DB::table('productos_en_carrito')->latest('id')->first());"`
   Esperado: `descuento = 20.00`, `precio` = precio original (sin descontar), `estado = 0`.
3. Agregar un producto con descuento 0 → fila con `descuento = 0.00`.

- [ ] **Step 4: Commit**

```bash
git add public/ventas_post.php
git commit -m "feat(ventas): snapshot del descuento del producto al agregar al carrito (INSERT parametrizado)"
```

---

### Task 5: Facturación con descuentos (`facturar.php`) — cálculo, persistencia, AFIP, PDF, auditoría

**Files:**
- Modify: `public/facturar.php` — INSERT a `ventas` (167-183), cálculo de total/IVA (192-227), INSERT a `factura` (404-437), PDF (502-528), y log de auditoría tras 440.

**Interfaces:**
- Consumes: `App\Ventas\CalculadoraVenta` (Task 1), columnas `ventas.descuento` / `factura.descuento_total` / `productos_en_carrito.descuento` (Task 2), tabla `descuentos_logs` (Task 2), parámetro GET `descuento_total` (Task 6).
- Produces: factura/venta persistidas con descuentos; AFIP con total descontado; log `DESCUENTO_TOTAL`.

> `facturar.php` hace `require 'vendor/autoload.php'` pero NO bootea el kernel de Laravel; por eso el log se inserta con mysqli preparado (no Eloquent). `CalculadoraVenta` sí está disponible vía autoload PSR-4.

- [ ] **Step 1: Leer el descuento total del form y clampearlo**

En `public/facturar.php`, después del bloque que normaliza valores `undefined`/`null` (línea 35) o junto a las demás lecturas de `$_GET` (~línea 50), agregar:

```php
$descuentoTotalPct = is_numeric($_GET["descuento_total"] ?? null)
    ? max(0, min(100, floatval($_GET["descuento_total"])))
    : 0;
```

- [ ] **Step 2: Persistir `ventas.descuento` por línea (INSERT explícito)**

Reemplazar el INSERT posicional a `ventas` (líneas 167-183) por uno con columnas explícitas que incluya `descuento` tomado de la fila del carrito:

```php
		$descuento_linea = is_numeric($producto_en_carrito["descuento"])
			? floatval($producto_en_carrito["descuento"]) : 0;

		$sql_insertar_venta =
		"
		INSERT INTO ventas
			(productos_id, cantidad, precio, costo, fecha, usuario, sucursal_id, estado, factura_id, tipo_pago, lista_precio, descuento)
		VALUES(
			'{$producto_en_carrito['producto_id']}',
			'{$producto_en_carrito['cantidad']}',
			'{$producto_en_carrito['precio']}',
			'{$producto_en_carrito['costo']}',
			'".date("Y-m-d H:i:s")."',
			'{$_COOKIE["kiosco"]}',
			'".getSucursal($_COOKIE["sucursal"])."',
			'3',
			NULL,
			'1612',
			'$lista_precio',
			'$descuento_linea'
		)";
```

> Valores derivados de la DB (carrito) o server-side; `$descuento_linea` pasa por `floatval`. Mantener el resto del `while` igual.

- [ ] **Step 3: Construir las líneas y calcular total/neto/iva con `CalculadoraVenta`**

En el loop que recalcula `$total` y pasa las ventas a estado 5 (líneas 199-209), construir además `$lineas` para la calculadora. Reemplazar:

```php
$item = array();
$total = 0;
$datos = array();
$resultado = $conn->query($sql);
if ($resultado->num_rows > 0) {
	// output data of each row
	while($row = $resultado->fetch_assoc()) {
		$total += $row["precio"] * $row["cantidad"];
		$datos_productos[] = $row;
		$update_producto = "UPDATE ventas SET estado = 5 WHERE id = ".$row["id"];
		if ($conn->query($update_producto) === FALSE) {
			echo "Error en UPDATE estado venta: " . $update_producto . "<br>" . $conn->error;
		}

	}
	//$eliminar_carrito = "DELETE FROM productos_en_carrito WHERE venta_id = '{$_GET['venta_id']}'";
}else{
	$devolucion["error"] = "No existen productos para facturar";
	echo json_encode($devolucion);
	exit();
}
```

por:

```php
$item = array();
$total = 0;
$datos = array();
$lineas = array();
$resultado = $conn->query($sql);
if ($resultado->num_rows > 0) {
	// output data of each row
	while($row = $resultado->fetch_assoc()) {
		$datos_productos[] = $row;
		$lineas[] = array(
			'precio'    => floatval($row["precio"]),
			'cantidad'  => floatval($row["cantidad"]),
			'descuento' => is_numeric($row["descuento"]) ? floatval($row["descuento"]) : 0,
		);
		$update_producto = "UPDATE ventas SET estado = 5 WHERE id = ".$row["id"];
		if ($conn->query($update_producto) === FALSE) {
			echo "Error en UPDATE estado venta: " . $update_producto . "<br>" . $conn->error;
		}

	}
	//$eliminar_carrito = "DELETE FROM productos_en_carrito WHERE venta_id = '{$_GET['venta_id']}'";
}else{
	$devolucion["error"] = "No existen productos para facturar";
	echo json_encode($devolucion);
	exit();
}

$calc = \App\Ventas\CalculadoraVenta::calcular($lineas, $descuentoTotalPct);
$total = $calc['total'];
```

- [ ] **Step 4: Usar el neto/IVA de la calculadora (preservando el caso comprobante C = 11)**

Reemplazar el bloque de IVA inline (líneas 221-227):

```php
if (intval($comprobante) != 11){
	$ImpNeto = round($total / 1.21,2);
	$impuestoIVA = round($ImpNeto * 0.21,2);
}else{
	$impuestoIVA = 0;
	$ImpNeto = $total;
}
```

por:

```php
if (intval($comprobante) != 11){
	$ImpNeto = $calc['neto'];
	$impuestoIVA = $calc['iva'];
}else{
	$impuestoIVA = 0;
	$ImpNeto = $total;
}
```

> `$total` ya es el total descontado (Step 3), de modo que `$data['ImpTotal'] => $total`, `'ImpNeto' => $ImpNeto`, `'ImpIVA' => $impuestoIVA` (líneas 248-252) y el array `Iva` (262-266) viajan a AFIP ya descontados sin tocar la estructura del request.

- [ ] **Step 5: Persistir `factura.descuento_total`**

En el `INSERT INTO factura` (líneas 404-437), agregar la columna `descuento_total` y su valor. Cambiar la lista de columnas para incluir `descuento_total` antes del cierre `)` y agregar `'$descuentoTotalPct'` al final de los VALUES:

Lista de columnas — agregar `` `descuento_total` `` tras `` `iva` `` (línea 420):

```php
		`iva`,
		`descuento_total`
		)
```

VALUES — agregar tras `'$iva'` (línea 437):

```php
		'$iva',
		'$descuentoTotalPct');";
```

- [ ] **Step 6: Registrar el log `DESCUENTO_TOTAL` (mysqli preparado)**

Tras el `UPDATE ventas SET factura_id` exitoso (después del `foreach($array_ids_ventas as $id)` que cierra en línea 449), y solo si hubo descuento total, agregar:

```php
				if ($descuentoTotalPct > 0) {
					$sql_log_desc = "INSERT INTO descuentos_logs
						(usuario, sucursal_id, tipo_operacion, factura_id, descuento_nuevo, monto_descontado, created_at, updated_at)
						VALUES (?, ?, 'DESCUENTO_TOTAL', ?, ?, ?, ?, ?)";
					$stmt_log = $conn->prepare($sql_log_desc);
					$usuario_log = $_COOKIE["kiosco"];
					$sucursal_log = getSucursal($_COOKIE["sucursal"]);
					$monto_desc = $calc['descuentoTotalMonto'];
					$ahora_log = date("Y-m-d H:i:s");
					$stmt_log->bind_param(
						"siiddss",
						$usuario_log,
						$sucursal_log,
						$factura_id,
						$descuentoTotalPct,
						$monto_desc,
						$ahora_log,
						$ahora_log
					);
					$stmt_log->execute();
					$stmt_log->close();
				}
```

> `$factura_id` está disponible (asignado en línea 440 `$factura_id = $conn->insert_id;`). Colocar este bloque dentro del `if ($conn->query($sql_insert) === TRUE) {` (línea 439), después del cierre del `foreach` de updates (línea 449).

- [ ] **Step 7: PDF — mostrar precios ya descontados**

En el loop del PDF que arma las filas de productos (líneas 502-509), mostrar el precio unitario **ya descontado** por línea. Reemplazar:

```php
		foreach ($datos_productos as $key => $value) {
			$html .= utf8_encode("<tr>
				<td style='border-bottom: 1px solid #000;word-wrap: break-word;width:230px;text-align:justify'><i>".$value["nombre_producto"]."</i></td>
				<td style='border-bottom: 1px solid #000;'>".$value["cantidad"]."</td>
				<td style='border-bottom: 1px solid #000;'>".number_format(floatval($value["precio"]),2,",",".")."</td>
				</tr>
				");
		}
```

por:

```php
		foreach ($datos_productos as $key => $value) {
			$descLinea = is_numeric($value["descuento"]) ? floatval($value["descuento"]) : 0;
			$precioUnitDesc = round(floatval($value["precio"]) * (1 - $descLinea / 100), 2);
			$html .= utf8_encode("<tr>
				<td style='border-bottom: 1px solid #000;word-wrap: break-word;width:230px;text-align:justify'><i>".$value["nombre_producto"]."</i></td>
				<td style='border-bottom: 1px solid #000;'>".$value["cantidad"]."</td>
				<td style='border-bottom: 1px solid #000;'>".number_format($precioUnitDesc,2,",",".")."</td>
				</tr>
				");
		}
```

> La fila "Total" (líneas 511-515) ya usa `$total`, que ahora es el total descontado. `ImpNeto`/`IVA` (517-527) ya usan los valores de la calculadora. No se agrega columna ni línea de "descuento" (decisión de alcance: el PDF muestra solo el precio final).

- [ ] **Step 8: Verificación manual (homologación AFIP)**

1. Cargar carrito con 2 productos (uno con descuento 10%), aplicar descuento total 5% (input de la Task 6) y facturar contra **homologación**.
2. Verificar `ventas` (las 2 filas) con `descuento` correcto, `factura.descuento_total = 5.00`, `factura.total` = total descontado.
3. Verificar `descuentos_logs`: una fila `DESCUENTO_TOTAL` con `factura_id`, `descuento_nuevo = 5`, `monto_descontado` = `$` descontado.
4. Abrir el PDF: líneas con precio descontado, total final correcto, neto/IVA coherentes (`neto = round(total/1.21,2)`).
5. Caso de control: facturar sin descuento total (input vacío/0) y sin descuento de producto → resultado idéntico al comportamiento previo (no debe haber fila en `descuentos_logs`).

- [ ] **Step 9: Commit**

```bash
git add public/facturar.php
git commit -m "feat(ventas): facturar con descuentos via CalculadoraVenta, persistencia, AFIP descontado y auditoria DESCUENTO_TOTAL"
```

---

### Task 6: Front de venta (`ventas.js`) — total en vivo + input "Descuento total %"

**Files:**
- Modify: `public/assets/js/pages/ventas.js` — `addRow`/`eliminar` (334-381), `concretar_venta` ajax (31-43), `presupuesto` ajax (109-120).
- Modify: `public/ventas.php` — agregar el input "Descuento total %" cerca del total (cerca de `#total_ventas`).

**Interfaces:**
- Consumes: `msg.descuento` devuelto por `ventas_post.php` (incluye `productos.*`), `jsonData.precio_unidad`.
- Produces: parámetro GET `descuento_total` enviado a `facturar.php` (consumido en Task 5).

- [ ] **Step 1: Agregar el input del descuento total en la vista**

En `public/ventas.php`, junto al elemento que muestra `#total_ventas` (buscar `id="total_ventas"`), agregar antes o al lado:

```html
<div class="sg-discount">
    <label for="descuento_total_input">Descuento total (%)</label>
    <input type="text" class="form-control numbers sg-mono-input" id="descuento_total_input" name="descuento_total_input" value="0" placeholder="0">
</div>
```

> Ubicarlo dentro del mismo contenedor del total para que sea visible al cerrar la venta. Ajustar clases al markup real circundante.

- [ ] **Step 2: Llevar subtotal descontado y recalcular el total en vivo**

En `public/assets/js/pages/ventas.js`, declarar una variable de subtotal junto a `var total_ventas = 0;` (línea 6):

```javascript
    var subtotal_con_descuento = 0;
```

Reemplazar `addRow` (líneas 334-359) para que cada línea descuente el `%` del producto y acumule en `subtotal_con_descuento`, y luego aplique el descuento total. Cambiar el bloque de actualización del total (líneas 355-358) por:

```javascript
        //Actualizo el subtotal con el descuento de línea del producto
        var descProducto = parseFloat(jsonData.descuento) || 0;
        if (descProducto < 0) descProducto = 0;
        if (descProducto > 100) descProducto = 100;
        var lineaConDesc = (jsonData.precio_unidad * jQuery("#cantidad").val()) * (1 - descProducto / 100);
        subtotal_con_descuento = subtotal_con_descuento + lineaConDesc;
        recalcularTotal();
        detalleProductos.push(new Array($("#producto_id").val(), $("#cantidad").val(), $("#precio").val(), lineaConDesc));
```

> `detalleProductos` ahora guarda en el índice 3 el monto descontado de la línea, para poder reconstruir el subtotal al eliminar.

Agregar la función `recalcularTotal` (a nivel de las otras funciones, p.ej. después de `eliminarProducto`, línea 440):

```javascript
function recalcularTotal() {
    var d = parseFloat($("#descuento_total_input").val()) || 0;
    if (d < 0) d = 0;
    if (d > 100) d = 100;
    total_ventas = subtotal_con_descuento * (1 - d / 100);
    $("#total_ventas").html(total_ventas.toFixed(2));
}
```

- [ ] **Step 3: Recalcular al cambiar el descuento total**

Dentro del `jQuery("document").ready(...)` (cerca de los otros handlers, p.ej. tras el handler de `#cantidad` en línea 203), agregar:

```javascript
        $("#descuento_total_input").on("keyup change", function(){
            recalcularTotal();
        });
```

- [ ] **Step 4: Reconstruir el subtotal al eliminar una línea**

Reemplazar el cálculo de `total_ventas` dentro de `eliminar` (líneas 370-371 y 376-377, en `done` y `fail`) por una resta del subtotal usando el monto descontado guardado. Cambiar ambas ocurrencias de:

```javascript
                total_ventas = total_ventas - (parseFloat(precio)*parseFloat(cantidad));
                $("#total_ventas").html(total_ventas);
                eliminarProducto(producto_id);
```

por:

```javascript
                subtotal_con_descuento = 0;
                for (let i = 0; i < detalleProductos.length; i++) {
                    if (detalleProductos[i][0] != producto_id && detalleProductos[i][3] != null) {
                        subtotal_con_descuento += parseFloat(detalleProductos[i][3]);
                    }
                }
                recalcularTotal();
                eliminarProducto(producto_id);
```

> `eliminarProducto` (líneas 431-440) ya remueve la fila de `detalleProductos`; aquí recalculamos el subtotal sobre los que quedan (excluyendo el que se está por eliminar).

- [ ] **Step 5: Enviar `descuento_total` al facturar y al presupuestar**

En el ajax de `concretar_venta` (líneas 33-41), agregar al final del `url` (antes de `,`):

```javascript
                '&direccion=' + $("#direccion-cliente").val() +
                '&descuento_total=' + ($("#descuento_total_input").val() || 0),
```

> Reemplaza la línea 41 `'&direccion=' + $("#direccion-cliente").val(),` agregando el nuevo parámetro.

En el ajax de `presupuesto` (líneas 110-119), agregar de igual modo a la `url` (tras `'&descontar_stock=' + descontar_stock`):

```javascript
                '&descontar_stock=' + descontar_stock +
                '&descuento_total=' + ($("#descuento_total_input").val() || 0),
```

- [ ] **Step 6: Resetear el descuento total al concretar/limpiar**

En los `setTimeout` que limpian la tabla tras facturar (líneas 49-59) y tras presupuestar (129-135), agregar el reset del subtotal e input. Dentro de cada bloque de limpieza, junto a `total_ventas=0;`, agregar:

```javascript
                        subtotal_con_descuento = 0;
                        $("#descuento_total_input").val("0");
```

- [ ] **Step 7: Build de assets**

Run: `npm run dev`
Expected: compila sin error.

- [ ] **Step 8: Verificación manual**

1. Agregar producto con descuento 10% → el total en vivo muestra la línea descontada.
2. Escribir 5 en "Descuento total %" → el total baja 5% adicional.
3. Eliminar una línea → el total se recalcula correctamente.
4. Facturar → la request a `facturar.php` incluye `descuento_total=5` (verificar en Network o logs); el total facturado coincide con el mostrado.

- [ ] **Step 9: Commit**

```bash
git add public/assets/js/pages/ventas.js public/ventas.php public/js
git commit -m "feat(ventas): total en vivo con descuento de linea y descuento total en la pantalla de venta"
```

> `public/js` solo si el build regeneró bundles versionados.

---

### Task 7: Grilla "Ventas de hoy" — mostrar descuento por línea (`ventas.php`)

**Files:**
- Modify: `public/ventas.php:300` (SELECT con alias) y `:323-329` (render de la fila).

**Interfaces:**
- Consumes: columna `ventas.descuento` (Task 2).
- Produces: ninguno (solo presentación).

> El SELECT hace `v.*, pr.*`; como **ambas** tablas tienen ahora una columna `descuento`, `fetch_assoc` colisiona y `$row["descuento"]` tomaría el del producto. Hay que aliasar `v.descuento as descuento_venta`.

- [ ] **Step 1: Aliasar la columna de descuento de la venta en el SELECT**

En `public/ventas.php`, línea 300, cambiar el `SELECT v.*,...` para agregar el alias explícito. Reemplazar:

```php
                    $sql = "SELECT v.*,v.fecha as fecha_vta, v.usuario as usuario_vta,pr.*,st.stock as stock_sucursal FROM `ventas` v inner join productos pr on pr.id = v.productos_id left join stock st ON (st.productos_id = pr.id AND st.sucursal_id = ".getSucursal($_COOKIE["sucursal"]).") WHERE v.`fecha` > '".date("Y-m-d")."' ORDER BY v.id DESC";
```

por:

```php
                    $sql = "SELECT v.*,v.fecha as fecha_vta, v.usuario as usuario_vta, v.descuento as descuento_venta, pr.*,st.stock as stock_sucursal FROM `ventas` v inner join productos pr on pr.id = v.productos_id left join stock st ON (st.productos_id = pr.id AND st.sucursal_id = ".getSucursal($_COOKIE["sucursal"]).") WHERE v.`fecha` > '".date("Y-m-d")."' ORDER BY v.id DESC";
```

- [ ] **Step 2: Mostrar el descuento y el importe descontado en la fila**

Reemplazar el bloque de las celdas de precio (líneas 323-329):

```php
                                <td>
                                    <p>Precio: <span class="sg-strong">$ <?php echo  $row["precio"] ?></span></p>
                                    <p>Quedan en stock: <?php echo (isset($row["stock_sucursal"]))?$row["stock_sucursal"]:0; ?></p>
                                </td>
                                <td class="sg-num">
                                    <span class="h1">$ <?php echo $row["precio"] * $row["cantidad"]; ?></span>
                                </td>
```

por:

```php
                                <?php
                                    $desc_vta = is_numeric($row["descuento_venta"]) ? floatval($row["descuento_venta"]) : 0;
                                    $importe_linea = round($row["precio"] * $row["cantidad"] * (1 - $desc_vta / 100), 2);
                                ?>
                                <td>
                                    <p>Precio: <span class="sg-strong">$ <?php echo $row["precio"] ?></span></p>
                                    <?php if ($desc_vta > 0){ ?>
                                    <p>Descuento: <span class="sg-strong"><?php echo rtrim(rtrim(number_format($desc_vta, 2, '.', ''), '0'), '.'); ?>%</span></p>
                                    <?php } ?>
                                    <p>Quedan en stock: <?php echo (isset($row["stock_sucursal"]))?$row["stock_sucursal"]:0; ?></p>
                                </td>
                                <td class="sg-num">
                                    <span class="h1">$ <?php echo $importe_linea; ?></span>
                                </td>
```

- [ ] **Step 3: Verificación manual**

1. Concretar una venta con un producto con descuento 20%.
2. Recargar `/ventas.php` → en "Ventas de hoy" la fila muestra "Descuento: 20%" y el importe de la derecha ya descontado.
3. Una venta sin descuento no muestra la línea "Descuento" y el importe es `precio × cantidad`.

- [ ] **Step 4: Commit**

```bash
git add public/ventas.php
git commit -m "feat(ventas): grilla de ventas del dia muestra el descuento por linea"
```

---

## Cierre

- [ ] Correr la suite completa: `docker compose exec sigav_app vendor/bin/phpunit`
  Expected: verde (incluye `CalculadoraVentaTest`, `DescuentoLogTest` y los tests AFIP preexistentes).
- [ ] Verificación E2E manual en homologación AFIP (Task 5, Step 8) con descuentos combinados y caso de control sin descuento.
- [ ] Al terminar, usar la skill `superpowers:finishing-a-development-branch` para decidir merge/PR de `feat/descuentos-ventas`.

## Trazabilidad spec → tasks

| Requisito del spec | Task |
|--------------------|------|
| `CalculadoraVenta` pura + lógica apilada + IVA + validación | Task 1 |
| Migraciones de columnas + `descuentos_logs` + `DescuentoLog` | Task 2 |
| Descuento fijo de producto + auditoría `DESCUENTO_PRODUCTO_CONFIG` + `searchProducts` JSON | Task 3 |
| Snapshot del descuento en `productos_en_carrito` | Task 4 |
| Facturar con total descontado, persistir `ventas.descuento`/`factura.descuento_total`, AFIP, PDF, log `DESCUENTO_TOTAL` | Task 5 |
| Input "Descuento total %" + total en vivo | Task 6 |
| Grilla de venta muestra descuento por línea | Task 7 |
| Tests unitarios `CalculadoraVenta` + esquema/modelo | Tasks 1, 2 |
</content>
</invoke>
