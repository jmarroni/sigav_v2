<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotaCredito extends Model
{
    protected $table ='nota_de_credito';

    // Tabla legacy: usa columna 'fecha' (string), sin created_at/updated_at.
    public $timestamps = false;

    /**
     * Excluye los placeholders de reserva (factura_id seteado y sin CAE) que
     * nota_de_credito.php inserta antes de pedir el CAE. Las históricas
     * (factura_id NULL) se listan siempre.
     */
    public function scopeSinReservas($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('nota_de_credito.factura_id')->orWhere('nota_de_credito.cae', '<>', '');
        });
    }
}
