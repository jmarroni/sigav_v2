<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MercadoPagoPago extends Model
{
    protected $table = 'mercadopago_pagos';

    protected $fillable = [
        'sucursal_id', 'mp_payment_id', 'fecha', 'monto', 'monto_neto',
        'estado', 'medio_pago', 'comprador', 'payload_raw',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'monto' => 'decimal:2',
        'monto_neto' => 'decimal:2',
        'payload_raw' => 'array',
    ];
}
