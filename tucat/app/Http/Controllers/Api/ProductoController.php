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
    public function productos(Request $request)
    {
        /* $productos = DB::table('productos')->
                join('categorias', 'categorias.id', 'productos.categorias_id')->
                join('proveedor', 'proveedor.id', 'productos.proveedores_id')->
                join('imagen_producto', 'imagen_producto.productos_id', 'productos.id')->
                select('productos.codigo_barras', 'productos.nombre', 'productos.precio_unidad', 'productos.costo', 'productos.stock', 'productos.stock_minimo', 'productos.usuario', 'productos.fecha', 'productos.precio_mayorista', 'productos.es_comodato', 'categorias.nombre AS categoria', 'imagen_producto.imagen_url AS imagen', 'proveedor.nombre AS nombre_proveedor', 'proveedor.apellido AS apellido_proveedor')
                ->get(); */

        $productos = DB::table('productos')->
            join('categorias', 'categorias.id', 'productos.categorias_id')->
            join('proveedor', 'proveedor.id', 'productos.proveedores_id')->
            select('productos.id', 'productos.codigo_barras', 'productos.nombre', 'producto.descripcion','productos.precio_unidad', 'productos.costo', 'productos.stock', 'productos.stock_minimo', 'productos.usuario', 'productos.fecha', 'productos.precio_mayorista', 'productos.es_comodato', 'categorias.nombre AS categoria', 'proveedor.nombre AS nombre_proveedor', 'proveedor.apellido AS apellido_proveedor')
            ->get();

        $imagenes = DB::table('imagen_producto')->
                select('imagen_producto.imagen_url', 'imagen_producto.productos_id')
                ->get();

        foreach ($productos as $producto) {
            $array_imagenes = array();
            foreach ($imagenes as $imagen) {
                if ( $producto->id == $imagen->productos_id ) {
                    array_push($array_imagenes, $imagen->imagen_url);
                }
            }
            $producto->id = null;
            $producto->imagenes = $array_imagenes;
        }

        return response()->json($productos, 201);
    }
}
