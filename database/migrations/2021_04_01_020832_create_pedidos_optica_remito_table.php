<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePedidosOpticaRemitoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
     public function up()
    {
        Schema::create('pedidos_optica_remito', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_pedido'); 
            $table->datetime('fecha');
            $table->string('usuario');
            $table->string('archivo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pedidos_optica_remito');
    }
}
