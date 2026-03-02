<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCategoriasLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categorias_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('usuario');
            $table->integer('categoria_id');
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
          Schema::dropIfExists('categorias_logs');
    }
}
