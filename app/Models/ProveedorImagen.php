<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProveedorImagen extends Model
{
    const MAX_IMAGENES = 3;

    protected $table = 'proveedor_imagen';
    public $timestamps = false;
    protected $guarded = ['id'];
}
