<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;
use Illuminate\Http\Request;
use App\Producto;

class ProductoController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $productos = DB::table('productos')->
                join('categorias', 'categorias.id', 'productos.categorias_id')->
                join('proveedor', 'proveedor.id', 'productos.proveedores_id')->
                join('imagen_producto', 'imagen_producto.productos_id', 'productos.id')->
                select('productos.codigo_barras', 'productos.nombre', 'productos.precio_unidad', 'productos.costo', 'productos.stock', 'productos.stock_minimo', 'productos.usuario', 'productos.fecha', 'productos.precio_mayorista', 'productos.es_comodato', 'categorias.nombre AS categoria', 'imagen_producto.imagen_url AS imagen', 'proveedor.nombre AS nombre_proveedor', 'proveedor.apellido AS apellido_proveedor')
                ->get();

        return response()->json($productos, 201);
    }
}
