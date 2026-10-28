<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 1) Tamaño de envío del producto (medidas y peso para el e-commerce).
 * 2) Referencias de ventas de la API: la idempotencia de POST /api/auth/productos/venta.
 * Guarded con hasTable/hasColumn: productos viene del dump legacy, no de migraciones.
 */
class AddEnvioYReferenciasVentasApi extends Migration
{
    public function up()
    {
        if (Schema::hasTable('productos') && !Schema::hasColumn('productos', 'envio_tamano')) {
            Schema::table('productos', function (Blueprint $t) {
                $t->unsignedTinyInteger('envio_tamano')->nullable();
            });
        }

        if (!Schema::hasTable('api_ventas_referencias')) {
            Schema::create('api_ventas_referencias', function (Blueprint $t) {
                $t->increments('id');
                $t->unsignedInteger('user_id');
                $t->string('referencia', 100);
                $t->timestamp('created_at')->nullable();
                $t->unique(['user_id', 'referencia']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('api_ventas_referencias');
        if (Schema::hasTable('productos') && Schema::hasColumn('productos', 'envio_tamano')) {
            Schema::table('productos', function (Blueprint $t) {
                $t->dropColumn('envio_tamano');
            });
        }
    }
}
