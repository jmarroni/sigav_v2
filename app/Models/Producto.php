<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\ProductosStock;
use App\Traits\Usuarios;

class Producto extends Model
{
	use ProductosStock;
	use Usuarios;
	

	protected $fillable = ['id', 'codigo_barras', 'nombre', 'precio_unidad', 'costo', 'stock', 'stock_minimo', 
		'proveedores_id', 'categorias_id', 'usuario', 'fecha', 'precio_mayorista', 'es_comodato'];


	public function stock_(){
		return $this->hasOne('App\Models\Stock','productos_id','id');
	}

	public function imagenes(){
		return $this->hasMany('App\Models\Imagen_producto','productos_id','id');
	}
	public function save_(){
		$this->save();
		if ($this->codigo_barrar == ""){
			$codigo_barras = "";
    		$identificador = substr("0000000000".$this->id,-10);
			$this->save();
		}
		
	}



}
