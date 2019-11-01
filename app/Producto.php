<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
	protected $fillable = ['id', 'codigo_barras', 'nombre', 'precio_unidad', 'costo', 'stock', 'stock_minimo', 
		'proveedores_id', 'categorias_id', 'usuario', 'fecha', 'precio_mayorista', 'es_comodato'];
}
