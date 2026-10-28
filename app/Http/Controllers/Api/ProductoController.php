<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;
use Illuminate\Http\Request;
use App\Support\Utf8;
use App\Models\Imagen_producto;
use App\Producto;
use App\Support\Envio;
use App\Support\Precio;

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
        $productos = DB::table('productos')->
            join('categorias', 'categorias.id', 'productos.categorias_id')->
            join('proveedor', 'proveedor.id', 'productos.proveedores_id')->
            select('productos.id', 'productos.codigo_barras', 'productos.nombre', 'productos.descripcion', 'productos.precio_unidad', 'productos.precio_mayorista', 'productos.stock', 'productos.stock_minimo', 'productos.usuario', 'productos.fecha', 'productos.es_comodato', 'productos.envio_tamano', 'productos.proveedores_id AS proveedor_id', 'categorias.nombre AS categoria', 'proveedor.nombre AS nombre_proveedor', 'proveedor.apellido AS apellido_proveedor')
            ->get();

        $imagenes = DB::table('imagen_producto')->
                select('imagen_producto.imagen_url', 'imagen_producto.productos_id')
                ->get()->groupBy('productos_id');

        // Delegación = sucursal donde se dio de alta el producto (la primera fila de stock).
        $delegaciones = DB::table('stock')->
            join('sucursales', 'sucursales.id', 'stock.sucursal_id')->
            orderBy('stock.id')->
            select('stock.productos_id', 'sucursales.nombre', 'sucursales.codigo_postal')
            ->get()->unique('productos_id')->keyBy('productos_id');

        $salida = $productos->map(function ($producto) use ($imagenes, $delegaciones) {
            $delegacion = $delegaciones->get($producto->id);
            $imagenesProducto = ($imagenes->get($producto->id) ?? collect())
                ->map(function ($imagen) { return Imagen_producto::absoluta($imagen->imagen_url); })
                ->values()->all();

            return array_merge([
                'id' => null,
                'codigo_barras' => $producto->codigo_barras,
                'nombre' => $producto->nombre,
                'descripcion' => $producto->descripcion,
                // Formato único: "60000.00" o null (ver App\Support\Precio).
                'precio_unidad' => Precio::normalizar($producto->precio_unidad),
                'precio_mayorista' => Precio::normalizar($producto->precio_mayorista),
                'stock' => (int) $producto->stock,
                'stock_minimo' => (int) $producto->stock_minimo,
                'categoria' => $producto->categoria,
                'es_comodato' => (int) $producto->es_comodato,
                'fecha' => $producto->fecha,
                'usuario' => $producto->usuario,
                'proveedor_id' => (int) $producto->proveedor_id,
                'nombre_proveedor' => $producto->nombre_proveedor,
                'apellido_proveedor' => $producto->apellido_proveedor,
                'delegacion' => $delegacion->nombre ?? null,
                'delegacion_codigo_postal' => $delegacion->codigo_postal ?? null,
            ], Envio::datos($producto->envio_tamano), [
                'imagenes' => $imagenesProducto,
            ]);
        });

        // Hay celdas en latin1 real en la base: sin esto json_encode falla y la API da 500.
        return response()->json(Utf8::sanear($salida->values()->all()), 201);
    }
}
