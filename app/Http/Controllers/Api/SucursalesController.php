<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Sucursales;

class SucursalesController extends Controller
{
    public function sucursales(Request $request)
    {
        if (!$request->user_id) {
            return response()->json('Debe de ingresar su id de usuario.');
        }

        $sucursales = DB::table('sucursales')->
                join('relacion_users_sucursales', 'sucursales.id', 'relacion_users_sucursales.sucursal_id')->
                select('sucursales.nombre')->
                where('relacion_users_sucursales.user_id', $request->user_id)->
                get();

        return response()->json($sucursales, 201);
    }

    public function productosPorSucursal(Request $request)
    {
        if (!$request->nombre_sucursal) {
            return response()->json('Debe de ingresar el nombre de la sucursal.');
        }

        if (!$request->user_id) {
            return response()->json('Debe de ingresar su id de usuario.');
        }

        $productos = DB::table('productos')->
        		join('stock', 'stock.productos_id', 'productos.id')->
        		join('sucursales', 'stock.sucursal_id', 'sucursales.id')->
                join('relacion_users_sucursales', 'relacion_users_sucursales.sucursal_id','sucursales.id')->
                join('categorias', 'categorias.id', 'productos.categorias_id')->
                join('proveedor', 'proveedor.id', 'productos.proveedores_id')->
                select('productos.codigo_barras','productos.id as id', 'productos.nombre', 'productos.precio_unidad as precio', 'productos.costo', 'productos.usuario', 'productos.fecha','productos.descripcion','productos.descripcion_en','productos.descripcion_pr','productos.material','productos.precio_mayorista', 'proveedor.nombre AS nombre_proveedor', 'proveedor.apellido AS apellido_proveedor')->
                where('sucursales.nombre', $request->nombre_sucursal)->
                where('relacion_users_sucursales.user_id', $request->user_id)->
                get();


        

        foreach ($productos as $producto) {
            $array_imagenes = array();
            $imagenes = DB::table('imagen_producto')->
                select('imagen_producto.imagen_url', 'imagen_producto.productos_id')->where("productos_id",$producto->id)
                ->get();
            foreach ($imagenes as $imagen) {
                if ( $producto->id == $imagen->productos_id ) {
                   // if (file_exists('http://www.mercado-artesanal.com.ar'.$imagen->imagen_url)){
                    if ($imagen->imagen_url != "assets/img/photos/no-image-featured-image.png"){
                        array_push($array_imagenes, "http://www.mercado-artesanal.com.ar".$imagen->imagen_url);
                    }
                   //s }
                }
                $producto->material = 'Generico';
                $producto->categoria = 3;
                $producto->cantidad = 1;
                $producto->condicion = 0;

            }
            $producto->id = null;
            $producto->imagenes = $array_imagenes;
        }
        return response()->json($productos, 201);
    }
}
