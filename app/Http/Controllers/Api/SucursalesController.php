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
                join('imagen_producto', 'imagen_producto.productos_id', 'productos.id')->
                select('productos.codigo_barras', 'productos.nombre', 'productos.precio_unidad', 'productos.costo', 'productos.stock', 'productos.stock_minimo', 'productos.usuario', 'productos.fecha', 'productos.precio_mayorista', 'productos.es_comodato', 'categorias.nombre AS categoria', 'imagen_producto.imagen_url AS imagen', 'proveedor.nombre AS nombre_proveedor', 'proveedor.apellido AS apellido_proveedor')->
                where('sucursales.nombre', $request->nombre_sucursal)->
                where('relacion_users_sucursales.user_id', $request->user_id)->
                get();

        return response()->json($productos, 201);
    }
}
