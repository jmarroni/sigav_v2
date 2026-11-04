<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ficha pública del artesano para el Mercado Artesanal:
 *  1) título y descripción en `proveedor`;
 *  2) `proveedor_imagen`: hasta 3 imágenes por artesano, identificadas por `orden` (1..3).
 * Guarded con hasTable/hasColumn: proveedor viene del dump legacy, no de migraciones.
 */
class AddContenidoYImagenesAProveedor extends Migration
{
    public function up()
    {
        if (Schema::hasTable('proveedor')) {
            Schema::table('proveedor', function (Blueprint $t) {
                if (!Schema::hasColumn('proveedor', 'titulo')) {
                    $t->string('titulo', 150)->nullable();
                }
                if (!Schema::hasColumn('proveedor', 'descripcion')) {
                    $t->text('descripcion')->nullable();
                }
            });
        }

        if (!Schema::hasTable('proveedor_imagen')) {
            Schema::create('proveedor_imagen', function (Blueprint $t) {
                $t->increments('id');
                $t->unsignedInteger('proveedor_id');
                $t->unsignedTinyInteger('orden');
                $t->string('imagen_url', 255);
                $t->unique(['proveedor_id', 'orden']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('proveedor_imagen');
        if (Schema::hasTable('proveedor')) {
            Schema::table('proveedor', function (Blueprint $t) {
                $columnas = array_filter(['titulo', 'descripcion'], function ($c) {
                    return Schema::hasColumn('proveedor', $c);
                });
                if ($columnas) {
                    $t->dropColumn($columnas);
                }
            });
        }
    }
}
