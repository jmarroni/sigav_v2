<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca "venta online" por producto: solo los marcados salen por la API del e-commerce
 * (con el recargo de ECOMMERCE_RECARGO_PCT). Arranca en 0 para todos: el operador los
 * marca a mano desde /carga. Guarded con hasTable/hasColumn: productos viene del dump legacy.
 */
class AddVentaOnlineAProductos extends Migration
{
    public function up()
    {
        if (Schema::hasTable('productos') && !Schema::hasColumn('productos', 'venta_online')) {
            Schema::table('productos', function (Blueprint $t) {
                $t->boolean('venta_online')->default(false);
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('productos') && Schema::hasColumn('productos', 'venta_online')) {
            Schema::table('productos', function (Blueprint $t) {
                $t->dropColumn('venta_online');
            });
        }
    }
}
