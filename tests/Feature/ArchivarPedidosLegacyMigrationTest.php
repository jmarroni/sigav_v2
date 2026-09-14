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
        require_once base_path('database/migrations/2026_09_14_000000_archivar_pedidos_legacy.php');
        return new \ArchivarPedidosLegacy();
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
            $t->dateTime('fecha');
            $t->double('monto');
            $t->integer('id_usuario');
            $t->integer('id_cliente');
            $t->integer('estado');
        });
        \DB::table('pedidos')->insert(['id_sucursal' => 1, 'fecha' => '2026-01-01 00:00:00', 'monto' => 5, 'id_usuario' => 1, 'id_cliente' => 1, 'estado' => 0]);

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

    /** @test */
    public function archiva_una_tabla_con_id_sucursal_que_tambien_tiene_nro_pedido()
    {
        // Caso híbrido: tiene la columna Laravel pero también la legacy —
        // no es la firma Laravel exacta, así que debe tratarse como legacy.
        Schema::create('pedidos', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('id_sucursal');
            $t->integer('nro_pedido');
            $t->double('monto');
            $t->integer('estado');
        });
        \DB::table('pedidos')->insert(['id_sucursal' => 1, 'nro_pedido' => 9, 'monto' => 5, 'estado' => 0]);

        $this->migracion()->up();

        $this->assertTrue(Schema::hasTable('pedidos_legacy'));
        $this->assertSame(1, \DB::table('pedidos_legacy')->count());
        $this->assertTrue(Schema::hasColumn('pedidos', 'id_sucursal'));
        $this->assertFalse(Schema::hasColumn('pedidos', 'nro_pedido'));
        $this->assertSame(0, \DB::table('pedidos')->count());
    }

    /** @test */
    public function aborta_si_pedidos_legacy_ya_existe_y_no_toca_ninguna_tabla()
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

        Schema::create('pedidos_legacy', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('nro_pedido');
        });
        \DB::table('pedidos_legacy')->insert(['nro_pedido' => 99]);

        $this->assertSame(1, \DB::table('pedidos')->count());
        $this->assertSame(1, \DB::table('pedidos_legacy')->count());

        $this->expectException(\RuntimeException::class);

        $this->migracion()->up();
    }
}
