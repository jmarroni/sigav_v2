<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotaDebito extends Model
{
    protected $table = 'nota_de_debito';

    // Tabla legacy: usa columna 'fecha' (string), sin created_at/updated_at.
    public $timestamps = false;
}
