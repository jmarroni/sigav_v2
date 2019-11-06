<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Sucursales extends Model
{
    protected $fillable = ['id', 'nombre', 'fecha_alta', 'usuario', 'fecha_baja', 'direccion', 'imagen', 
		'provincia', 'codigo_postal', 'pto_vta'];
}
