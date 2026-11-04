<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * nota_de_credito es legacy (no la crea ninguna migración). Agrega factura_id
 * con índice único: una factura no puede tener más de una nota de crédito.
 * Las notas históricas quedan con NULL (MySQL y SQLite permiten varios NULL
 * bajo un UNIQUE). Guarded para ser idempotente y no fallar en tests.
 */
class AddFacturaIdToNotaDeCredito extends Migration
{
    const TABLA = 'nota_de_credito';
    const INDICE = 'nota_de_credito_factura_id_unique';

    public function up()
    {
        if (! Schema::hasTable(self::TABLA) || Schema::hasColumn(self::TABLA, 'factura_id')) {
            return;
        }
        Schema::table(self::TABLA, function (Blueprint $t) {
            $t->integer('factura_id')->nullable()->after('sucursal_id');
            $t->unique('factura_id', self::INDICE);
        });
    }

    public function down()
    {
        if (! Schema::hasTable(self::TABLA) || ! Schema::hasColumn(self::TABLA, 'factura_id')) {
            return;
        }
        // SQLite (tests) necesita doctrine/dbal para dropColumn; ahí solo se quita el índice.
        $sqlite = Schema::getConnection()->getDriverName() === 'sqlite';
        Schema::table(self::TABLA, function (Blueprint $t) use ($sqlite) {
            $t->dropUnique(self::INDICE);
            if (! $sqlite) {
                $t->dropColumn('factura_id');
            }
        });
    }
}
