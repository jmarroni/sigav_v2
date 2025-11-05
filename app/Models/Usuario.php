<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $table ='usuarios';
    public $timestamps = false;

    // SEGURIDAD: Protección contra mass assignment
    protected $fillable = ['usuario', 'nombre', 'apellido', 'telefono', 'rol_id', 'sucursal_id'];

    // Campos protegidos de asignación masiva
    protected $guarded = ['id', 'clave'];

    // Ocultar campos sensibles en JSON
    protected $hidden = ['clave'];
}