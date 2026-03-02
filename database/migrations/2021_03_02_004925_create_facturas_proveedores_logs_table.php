<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFacturasProveedoresLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('facturas_proveedores_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_proveedor'); 
            $table->integer('id_sucursal'); 
            $table->string('numero_factura');   
            $table->datetime('fecha');
            $table->string('usuario');
            $table->double('monto');
           
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('facturas_proveedores_logs');
    }
}
