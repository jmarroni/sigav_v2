<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDescuentosLogsTable extends Migration
{
    public function up()
    {
        Schema::create('descuentos_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('usuario', 200)->nullable();
            $table->integer('sucursal_id')->nullable();
            $table->string('tipo_operacion', 50);
            $table->integer('factura_id')->nullable();
            $table->integer('productos_id')->nullable();
            $table->decimal('descuento_anterior', 5, 2)->nullable();
            $table->decimal('descuento_nuevo', 5, 2)->default(0);
            $table->decimal('monto_descontado', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('descuentos_logs');
    }
}
