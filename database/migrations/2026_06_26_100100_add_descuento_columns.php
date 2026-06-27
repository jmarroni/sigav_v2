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
