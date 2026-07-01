<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMercadopagoConfigTable extends Migration
{
    public function up()
    {
        Schema::create('mercadopago_config', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('sucursal_id')->unique();
            $table->text('access_token')->nullable();
            $table->string('public_key')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mercadopago_config');
    }
}
