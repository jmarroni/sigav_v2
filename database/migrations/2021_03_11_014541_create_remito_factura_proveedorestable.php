<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRemitoFacturaProveedorestable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('remito_facturas_proveedores', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_factura_proveedor'); 
            $table->datetime('fecha_generacion');
            $table->string('usuario');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         Schema::dropIfExists('remito_facturas_proveedores');
    }
}
