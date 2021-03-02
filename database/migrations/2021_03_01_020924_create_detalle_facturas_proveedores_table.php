<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDetalleFacturasProveedoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::create('detalle_facturas_proveedores', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_factura');
            $table->integer('id_producto');
            $table->integer('cantidad');
            $table->double('precio');
     });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('detalle_facturas_proveedores');
    }
}
