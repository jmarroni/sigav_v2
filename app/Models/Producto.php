<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{

	

	// SEGURIDAD: Removido 'id' de fillable para prevenir mass assignment
	protected $fillable = ['codigo_barras', 'nombre', 'precio_unidad', 'costo', 'stock', 'stock_minimo',
		'proveedores_id', 'categorias_id', 'precio_mayorista', 'es_comodato', 'descripcion',
		'descripcion_pr', 'descripcion_en', 'material', 'precio_reposicion'];

	// Proteger campos sensibles de asignación masiva
	protected $guarded = ['id', 'usuario', 'fecha'];


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
