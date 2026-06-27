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
