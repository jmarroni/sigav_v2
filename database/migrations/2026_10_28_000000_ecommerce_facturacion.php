<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Conecta las ventas del e-commerce con la facturación.
 *
 * - ventas.referencia_api: el pedido del e-commerce al que pertenece cada renglón.
 * - api_ventas_referencias: datos del comprador (opcional), factura emitida y un
 *   bloqueo (facturando_at) para que un doble clic no emita dos comprobantes.
 * Guarded con hasTable/hasColumn: ventas viene del dump legacy, no de migraciones.
 */
class EcommerceFacturacion extends Migration
{
    public function up()
    {
        if (Schema::hasTable('ventas') && !Schema::hasColumn('ventas', 'referencia_api')) {
            Schema::table('ventas', function (Blueprint $t) {
                $t->string('referencia_api', 100)->nullable()->index();
            });
        }

        if (Schema::hasTable('api_ventas_referencias')) {
            Schema::table('api_ventas_referencias', function (Blueprint $t) {
                if (!Schema::hasColumn('api_ventas_referencias', 'cliente_tipo_documento')) {
                    $t->string('cliente_tipo_documento', 10)->nullable();
                    $t->string('cliente_documento', 20)->nullable();
                    $t->string('cliente_nombre', 200)->nullable();
                    $t->unsignedTinyInteger('cliente_condicion_iva')->nullable();
                    $t->unsignedInteger('factura_id')->nullable()->index();
                    $t->timestamp('facturando_at')->nullable();
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('api_ventas_referencias') && Schema::hasColumn('api_ventas_referencias', 'factura_id')) {
            Schema::table('api_ventas_referencias', function (Blueprint $t) {
                $t->dropColumn(['cliente_tipo_documento', 'cliente_documento', 'cliente_nombre',
                    'cliente_condicion_iva', 'factura_id', 'facturando_at']);
            });
        }
        if (Schema::hasTable('ventas') && Schema::hasColumn('ventas', 'referencia_api')) {
            Schema::table('ventas', function (Blueprint $t) {
                $t->dropColumn('referencia_api');
            });
        }
    }
}
