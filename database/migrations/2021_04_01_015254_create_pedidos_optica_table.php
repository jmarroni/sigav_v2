<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePedidosOpticaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pedidos_optica', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('nota_pedido');
            $table->integer('presupuesto');
            $table->string('paciente');
            $table->string('domicilio');
            $table->string('telefono');
            $table->string('doctor');
            $table->string('obra_social');
            $table->string('numero_asociado');
            $table->datetime('fecha');
            $table->datetime('fecha_recepcion');
            $table->string('pedido');
            $table->string('retira');
            $table->double('monto');
            $table->string('l_d_esf')->nullable();
            $table->string('l_d_cil')->nullable();
            $table->string('l_d_eje')->nullable();
            $table->string('l_d_dip')->nullable();
            $table->string('l_producto')->nullable();
            $table->string('l_armazon')->nullable();
            $table->string('c_producto')->nullable();
            $table->string('c_armazon')->nullable();
            $table->string('l_i_esf')->nullable();
            $table->string('l_i_cil')->nullable();
            $table->string('l_i_eje')->nullable();
            $table->string('l_i_dip')->nullable();
            $table->string('c_d_esf')->nullable();
            $table->string('c_d_cil')->nullable();
            $table->string('c_d_eje')->nullable();
            $table->string('c_d_dip')->nullable();
            $table->string('c_i_esf')->nullable();
            $table->string('c_i_cil')->nullable();
            $table->string('c_i_eje')->nullable();
            $table->string('c_i_dip')->nullable();
            $table->string('observacion')->nullable();
            $table->string('usuario');
            $table->integer('id_sucursal');
            $table->integer('estado');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         Schema::dropIfExists('pedidos_optica');
    }
}
