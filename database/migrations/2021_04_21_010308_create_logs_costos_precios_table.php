<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogsCostosPreciosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('logs_costos_precios', function (Blueprint $table) {
            $table->increments('id');
            $table->double('costo_anterior');
            $table->double('costo');
            $table->double('precio_anterior');
            $table->double('precio');
            $table->integer('sucursal_id');
            $table->string('usuario');
            $table->integer('productos_id');
            $table->datetime('updated_at');
            $table->datetime('created_at');
            $table->string('operacion');
     });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
