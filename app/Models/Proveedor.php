<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    protected $table ='proveedor';
    public $timestamps = false;

    /** El usuario interno que cargó el artesano no sale en JSON (getProveedor, show). */
    protected $hidden = ['usuario'];

    public function imagenes()
    {
        return $this->hasMany(ProveedorImagen::class, 'proveedor_id')->orderBy('orden');
    }
}
