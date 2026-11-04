<?php
// database/migrations/2026_09_14_000000_archivar_pedidos_legacy.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * En instancias que nacieron de un dump legacy, `pedidos` tiene el esquema
 * viejo (nro_pedido, productos_id, ...) y choca con el esquema Laravel que
 * usan App\Models\Pedido y PedidoController (/pedido). Si la tabla existente
 * no tiene `id_sucursal`, se archiva como `pedidos_legacy` y se crea la
 * tabla Laravel. Idempotente: correrla dos veces no cambia nada.
 */
class ArchivarPedidosLegacy extends Migration
{
    public function up()
    {
        if (Schema::hasTable('pedidos') && ! $this->tieneEsquemaLaravel()) {
            if (Schema::hasTable('pedidos_legacy')) {
                throw new RuntimeException('pedidos_legacy ya existe; resolver a mano antes de migrar');
            }
            Schema::rename('pedidos', 'pedidos_legacy');
        }

        if (! Schema::hasTable('pedidos')) {
            $this->crearEsquemaLaravel();
        }
    }

    public function down()
    {
        // No se revierte: perderíamos la distinción entre ambas tablas.
    }

    /**
     * true solo si `pedidos` tiene exactamente la firma Laravel
     * (id_sucursal + monto, sin la columna legacy nro_pedido). Cualquier otra
     * cosa (incluido el esquema legacy con id_sucursal pero también
     * nro_pedido) se trata como legacy y se archiva.
     */
    private function tieneEsquemaLaravel()
    {
        return Schema::hasColumn('pedidos', 'id_sucursal')
            && Schema::hasColumn('pedidos', 'monto')
            && ! Schema::hasColumn('pedidos', 'nro_pedido');
    }

    private function crearEsquemaLaravel()
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_sucursal');
            $table->datetime('fecha');
            $table->double('monto');
            $table->integer('id_usuario');
            $table->integer('id_cliente');
            $table->integer('estado');
        });
    }
}
