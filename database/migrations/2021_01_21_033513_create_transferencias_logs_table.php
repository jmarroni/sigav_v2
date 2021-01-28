<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransferenciasLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::create('transferencias_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('usuario');
            $table->integer('sucursal_origen_id');
            $table->integer('sucursal_destino_id');
            $table->integer('transferencia_id');
            $table->datetime('updated_at');
            $table->datetime('created_at');
            $table->string('tipo_operacion');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         Schema::dropIfExists('transferencias_logs');
    }
}
