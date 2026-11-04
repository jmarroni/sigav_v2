<?php
// tests/Feature/AddFacturaIdNotaCreditoMigrationTest.php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * nota_de_credito es una tabla legacy (sin migración de creación). La
 * migración agrega factura_id con índice único para que una factura no pueda
 * tener más de una nota de crédito (incidente 2026-09-23: 20 NC repetidas).
 */
class AddFacturaIdNotaCreditoMigrationTest extends TestCase
{
    private function migracion()
    {
        require_once base_path('database/migrations/2026_10_07_000000_add_factura_id_to_nota_de_credito.php');
        return new \AddFacturaIdToNotaDeCredito();
    }

    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropIfExists('nota_de_credito');
    }

    private function tablaLegacy(): void
    {
        Schema::create('nota_de_credito', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('sucursal_id')->nullable();
            $t->string('fecha', 20)->nullable();
            $t->string('usuario', 100)->nullable();
            $t->integer('numero')->nullable();
            $t->string('cae', 50)->nullable();
            $t->string('fechacae', 20)->default('');
            $t->string('total', 20)->nullable();
        });
    }

    private function fila(array $extra = []): void
    {
        DB::table('nota_de_credito')->insert($extra + [
            'sucursal_id' => 2, 'fecha' => '2026-10-07 10:00:00', 'usuario' => 'op',
            'numero' => 1, 'cae' => '1', 'fechacae' => '', 'total' => '10',
        ]);
    }

    /** @test */
    public function agrega_factura_id_y_no_permite_dos_notas_para_la_misma_factura()
    {
        $this->tablaLegacy();

        $this->migracion()->up();

        $this->assertTrue(Schema::hasColumn('nota_de_credito', 'factura_id'));
        $this->fila(['factura_id' => 7]);
        $this->expectException(QueryException::class);
        $this->fila(['factura_id' => 7]);
    }

    /** @test */
    public function las_notas_historicas_sin_factura_id_siguen_conviviendo()
    {
        $this->tablaLegacy();
        $this->fila();
        $this->fila();

        $this->migracion()->up();
        $this->fila(['factura_id' => null]);

        $this->assertSame(3, DB::table('nota_de_credito')->whereNull('factura_id')->count());
    }

    /** @test */
    public function es_idempotente_y_reversible()
    {
        $this->tablaLegacy();

        $this->migracion()->up();
        $this->migracion()->up();
        $this->assertTrue(Schema::hasColumn('nota_de_credito', 'factura_id'));

        $this->migracion()->down();
        // En SQLite no se puede soltar la columna sin doctrine/dbal: down() solo quita el índice.
        $this->fila(['factura_id' => 7]);
        $this->fila(['factura_id' => 7]);
        $this->assertSame(2, DB::table('nota_de_credito')->where('factura_id', 7)->count());
    }

    /** @test */
    public function no_falla_si_la_tabla_legacy_no_existe()
    {
        $this->migracion()->up();
        $this->migracion()->down();

        $this->assertFalse(Schema::hasTable('nota_de_credito'));
    }
}
