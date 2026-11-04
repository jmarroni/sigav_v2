<?php
// tests/Feature/VentaOnlineProductoTest.php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Botón "Venta online" de la grilla /carga:
 *  - POST /productos/{id}/venta-online con el estado deseado (no invierte)
 *  - solo roles Carga y Administrador
 *  - la grilla marca el producto y muestra el precio web con el recargo
 */
class VentaOnlineProductoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('productos', function (Blueprint $t) {
            $t->increments('id');
            $t->string('codigo_barras', 100)->nullable();
            $t->string('nombre', 200)->nullable();
            $t->string('precio_unidad', 50)->nullable();
            $t->string('precio_reposicion', 50)->nullable();
            $t->string('costo', 50)->nullable();
            $t->integer('proveedores_id')->nullable();
            $t->string('categorias_id', 50)->nullable();
            $t->boolean('venta_online')->default(false);
            $t->timestamps();
        });
        Schema::create('imagen_producto', function (Blueprint $t) {
            $t->increments('id');
            $t->string('imagen_url', 300);
            $t->integer('productos_id');
        });
        Schema::create('stock', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('stock')->nullable();
            $t->integer('sucursal_id')->nullable();
            $t->integer('productos_id')->nullable();
        });
        Schema::create('usuarios', function (Blueprint $t) {
            $t->increments('id');
            $t->string('usuario', 100);
            $t->integer('rol_id')->nullable();
            $t->integer('sucursal_id')->nullable();
        });
        Schema::create('roles', function (Blueprint $t) {
            $t->increments('id');
            $t->string('nombre', 100);
        });
        Schema::create('categorias', function (Blueprint $t) {
            $t->increments('id');
            $t->string('nombre', 100);
        });
        Schema::create('proveedor', function (Blueprint $t) {
            $t->increments('id');
            $t->string('nombre', 100)->nullable();
            $t->string('apellido', 100)->nullable();
        });

        // Mismos nombres que en producción (hay "ADMINISTRADOR" repetido en mayúsculas).
        DB::table('roles')->insert([
            ['id' => 1, 'nombre' => 'Venta'],
            ['id' => 3, 'nombre' => 'Carga'],
            ['id' => 4, 'nombre' => 'Sucursal'],
            ['id' => 5, 'nombre' => 'Administrador'],
            ['id' => 7, 'nombre' => 'ADMINISTRADOR'],
        ]);
        DB::table('productos')->insert([
            ['id' => 7, 'codigo_barras' => 'A1', 'nombre' => 'Mate', 'precio_unidad' => '60.000', 'venta_online' => 0],
            ['id' => 8, 'codigo_barras' => 'A2', 'nombre' => 'Poncho', 'precio_unidad' => '999', 'venta_online' => 1],
            ['id' => 9, 'codigo_barras' => 'A3', 'nombre' => 'Raro', 'precio_unidad' => 'consultar', 'venta_online' => 1],
        ]);
        config(['ecommerce.recargo_online_pct' => 10]);
    }

    protected function tearDown(): void
    {
        unset($_COOKIE['kiosco'], $_COOKIE['rol'], $_COOKIE['sucursal']);
        parent::tearDown();
    }

    /** Sesión Laravel + cookies legacy firmadas para el rol pedido. */
    private function logueado(int $rolId): self
    {
        config(['app.legacy_semilla' => 'semilla-de-test']);
        DB::table('usuarios')->updateOrInsert(['usuario' => 'operador'], ['rol_id' => $rolId, 'sucursal_id' => 1]);
        $_COOKIE['kiosco'] = 'operador';
        $_COOKIE['rol'] = sha1('semilla-de-test' . $rolId . 'semilla-de-test');
        $_COOKIE['sucursal'] = sha1('semilla-de-test1semilla-de-test');
        $sesion = User::firstOrCreate(['email' => 'operador@legacy.local'], ['name' => 'op', 'password' => bcrypt('x')]);

        return $this->actingAs($sesion);
    }

    private function online(int $id): int
    {
        return (int) DB::table('productos')->where('id', $id)->value('venta_online');
    }

    /** @test */
    public function carga_y_administradores_pueden_marcar_y_desmarcar()
    {
        foreach ([3, 5, 7] as $rol) {
            $this->logueado($rol)->postJson('/productos/7/venta-online', ['venta_online' => 1])
                ->assertOk()->assertExactJson(['ok' => true, 'venta_online' => 1]);
            $this->assertSame(1, $this->online(7), "rol $rol");

            $this->logueado($rol)->postJson('/productos/7/venta-online', ['venta_online' => 0])
                ->assertOk()->assertExactJson(['ok' => true, 'venta_online' => 0]);
            $this->assertSame(0, $this->online(7), "rol $rol");
        }
    }

    /** @test */
    public function mandar_el_mismo_estado_dos_veces_no_lo_invierte()
    {
        $this->logueado(5)->postJson('/productos/7/venta-online', ['venta_online' => 1])->assertOk();
        $this->logueado(5)->postJson('/productos/7/venta-online', ['venta_online' => 1])->assertOk();

        $this->assertSame(1, $this->online(7));
    }

    /** @test */
    public function otros_roles_reciben_403_y_no_cambia_nada()
    {
        foreach ([1, 4] as $rol) {
            $this->logueado($rol)->postJson('/productos/7/venta-online', ['venta_online' => 1])->assertStatus(403);
        }
        $this->assertSame(0, $this->online(7));
    }

    /** @test */
    public function sin_cookies_legacy_validas_es_403()
    {
        $this->logueado(5);
        $_COOKIE['rol'] = sha1('otra-cosa');

        $this->postJson('/productos/7/venta-online', ['venta_online' => 1])->assertStatus(403);
        $this->assertSame(0, $this->online(7));
    }

    /** @test */
    public function valor_invalido_es_422_y_producto_inexistente_404()
    {
        $this->logueado(5)->postJson('/productos/7/venta-online', ['venta_online' => 'si'])->assertStatus(422);
        $this->logueado(5)->postJson('/productos/7/venta-online', [])->assertStatus(422);
        $this->logueado(5)->postJson('/productos/999/venta-online', ['venta_online' => 1])->assertStatus(404);
        $this->assertSame(0, $this->online(7));
    }

    /** @test */
    public function por_get_no_se_puede_cambiar()
    {
        $this->logueado(5)->get('/productos/7/venta-online?venta_online=1')->assertStatus(405);
        $this->assertSame(0, $this->online(7));
    }

    /** @test */
    public function la_grilla_marca_los_productos_online_con_el_precio_web_y_el_aviso()
    {
        $json = $this->logueado(5)->get('/productos/datatable?sucursal=1&length=25')->assertOk()->json();
        $filas = collect($json['data'])->keyBy('DT_RowId');

        // Columna 6: precio al público. El de mostrador no cambia; el online lleva el precio web.
        $this->assertStringContainsString('999', $filas['8'][6]);
        $this->assertStringContainsString('Online', $filas['8'][6]);
        $this->assertStringContainsString('Web $1.099', $filas['8'][6]);
        $this->assertStringContainsString('10%', $filas['8'][6]);

        $this->assertStringNotContainsString('Online', $filas['7'][6]);

        // Marcado pero con precio que no sirve: se avisa que no se publica.
        $this->assertStringContainsString('no se publica', $filas['9'][6]);

        // Botón con el estado actual, para que el JS mande el opuesto explícito.
        $this->assertStringContainsString('id="ventaonline_8"', $filas['8'][9]);
        $this->assertStringContainsString('data-online="1"', $filas['8'][9]);
        $this->assertStringContainsString('data-online="0"', $filas['7'][9]);
    }

    /** @test */
    public function la_grilla_no_muestra_el_boton_a_quien_no_puede_usarlo()
    {
        $json = $this->logueado(1)->get('/productos/datatable?sucursal=1&length=25')->assertOk()->json();
        $fila = collect($json['data'])->firstWhere('DT_RowId', '8');

        $this->assertStringNotContainsString('ventaonline_', $fila[9]);
        $this->assertStringContainsString('Online', $fila[6], 'la marca se ve igual');
    }

    /** @test */
    public function el_filtro_solo_online_deja_los_marcados()
    {
        $json = $this->logueado(5)->get('/productos/datatable?sucursal=1&length=25&solo_online=1')->assertOk()->json();

        $this->assertSame(['8', '9'], collect($json['data'])->pluck('DT_RowId')->sort()->values()->all());
        $this->assertSame(2, $json['recordsFiltered']);
    }

    /** @test */
    public function un_recargo_mal_configurado_no_tumba_la_pantalla_de_carga()
    {
        config(['ecommerce.recargo_online_pct' => '10.5']);

        $json = $this->logueado(5)->get('/productos/datatable?sucursal=1&length=25')->assertOk()->json();
        $fila = collect($json['data'])->firstWhere('DT_RowId', '8');

        $this->assertStringContainsString('999', $fila[6], 'el precio de mostrador se ve igual');
        $this->assertStringContainsString('recargo mal configurado', $fila[6]);
    }
}
