<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAfipConfigTable extends Migration
{
    public function up()
    {
        Schema::create('afip_config', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->enum('entorno', ['homo', 'prod'])->unique();
            $table->string('cuit')->nullable();
            $table->string('ptovta')->nullable();
            $table->string('comprobante')->nullable();
            $table->string('condicion_iva')->nullable();
            $table->string('inicio_actividades')->nullable();
            $table->string('ingresos_brutos')->nullable();
            $table->boolean('emitir')->default(false);
            $table->boolean('solicitar_datos')->default(false);
            $table->boolean('activo')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('afip_config');
    }
}
