<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sucursales extends Model
{
    protected $fillable = ['id', 'nombre', 'fecha_alta', 'usuario', 'fecha_baja', 'direccion', 'imagen', 
    'provincia', 'codigo_postal', 'pto_vta'];
    
    	//Esto se debe llevar a un servicio
    public static function getSucursal($sucursal = null){
      $sucursal = (isset($sucursal))?$sucursal:$_COOKIE["sucursal"];
      if (isset($sucursal)){
        for ($i=0; $i < 99; $i++) { 
          if (sha1("$%Reset20122017AnnaLuca#^".$i."$%Reset20122017AnnaLuca#^") == $_COOKIE["sucursal"]) return $i;
        }
      }else exit();
    }
}
