<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFacturasProveedoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('facturas_proveedores', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_proveedor');
            $table->integer('id_sucursal');
            $table->datetime('fecha');
            $table->string('numero_factura');
            $table->double('monto');
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
        Schema::dropIfExists('facturas_proveedores');
    }
}
