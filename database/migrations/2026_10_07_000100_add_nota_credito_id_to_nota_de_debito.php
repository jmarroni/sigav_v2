<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * nota_de_debito es legacy. Agrega nota_credito_id UNIQUE: una nota de
 * crédito no puede tener más de una nota de débito (misma reserva atómica
 * que nota_de_credito.factura_id). Guarded e idempotente.
 */
class AddNotaCreditoIdToNotaDeDebito extends Migration
{
    const TABLA = 'nota_de_debito';
    const INDICE = 'nota_de_debito_nota_credito_id_unique';

    public function up()
    {
        if (! Schema::hasTable(self::TABLA) || Schema::hasColumn(self::TABLA, 'nota_credito_id')) {
            return;
        }
        Schema::table(self::TABLA, function (Blueprint $t) {
            $t->integer('nota_credito_id')->nullable()->after('sucursal_id');
            $t->unique('nota_credito_id', self::INDICE);
        });
    }

    public function down()
    {
        if (! Schema::hasTable(self::TABLA) || ! Schema::hasColumn(self::TABLA, 'nota_credito_id')) {
            return;
        }
        $sqlite = Schema::getConnection()->getDriverName() === 'sqlite';
        Schema::table(self::TABLA, function (Blueprint $t) use ($sqlite) {
            $t->dropUnique(self::INDICE);
            if (! $sqlite) {
                $t->dropColumn('nota_credito_id');
            }
        });
    }
}
