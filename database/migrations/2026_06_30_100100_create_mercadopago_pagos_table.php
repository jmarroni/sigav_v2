<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMercadopagoPagosTable extends Migration
{
    public function up()
    {
        Schema::create('mercadopago_pagos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('sucursal_id');
            $table->string('mp_payment_id');
            $table->dateTime('fecha');
            $table->decimal('monto', 12, 2);
            $table->decimal('monto_neto', 12, 2)->nullable();
            $table->string('estado');
            $table->string('medio_pago')->nullable();
            $table->string('comprador')->nullable();
            $table->json('payload_raw')->nullable();
            $table->string('estado_facturacion')->default('pendiente');
            $table->timestamps();

            $table->unique(['sucursal_id', 'mp_payment_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('mercadopago_pagos');
    }
}
