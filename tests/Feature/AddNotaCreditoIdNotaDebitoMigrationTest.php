<?php
// tests/Feature/AddNotaCreditoIdNotaDebitoMigrationTest.php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AddNotaCreditoIdNotaDebitoMigrationTest extends TestCase
{
    private function migracion()
    {
        require_once base_path('database/migrations/2026_10_07_000100_add_nota_credito_id_to_nota_de_debito.php');
        return new \AddNotaCreditoIdToNotaDeDebito();
    }

    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropIfExists('nota_de_debito');
        Schema::create('nota_de_debito', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('sucursal_id')->nullable();
            $t->string('fecha', 20)->nullable();
            $t->integer('numero')->nullable();
            $t->string('cae', 50)->nullable();
            $t->string('fechacae', 20)->default('');
        });
    }

    /** @test */
    public function una_nota_de_credito_no_puede_tener_dos_notas_de_debito()
    {
        $this->migracion()->up();
        $this->migracion()->up();

        $this->assertTrue(Schema::hasColumn('nota_de_debito', 'nota_credito_id'));
        DB::table('nota_de_debito')->insert(['nota_credito_id' => 4, 'fecha' => 'x', 'numero' => 1, 'cae' => '1']);
        DB::table('nota_de_debito')->insert(['nota_credito_id' => null, 'fecha' => 'x', 'numero' => 2, 'cae' => '1']);
        DB::table('nota_de_debito')->insert(['nota_credito_id' => null, 'fecha' => 'x', 'numero' => 3, 'cae' => '1']);
        $this->expectException(QueryException::class);
        DB::table('nota_de_debito')->insert(['nota_credito_id' => 4, 'fecha' => 'x', 'numero' => 4, 'cae' => '1']);
    }

    /** @test */
    public function down_quita_el_indice_sin_romper_en_sqlite()
    {
        $this->migracion()->up();
        $this->migracion()->down();

        DB::table('nota_de_debito')->insert(['nota_credito_id' => 4, 'fecha' => 'x', 'numero' => 1, 'cae' => '1']);
        DB::table('nota_de_debito')->insert(['nota_credito_id' => 4, 'fecha' => 'x', 'numero' => 2, 'cae' => '1']);
        $this->assertSame(2, DB::table('nota_de_debito')->where('nota_credito_id', 4)->count());
    }
}
