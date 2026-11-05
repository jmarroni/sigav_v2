<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;
use Illuminate\Http\Request;
use App\Support\Utf8;
use App\Models\Imagen_producto;
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
            // Solo lo que el operador marcó para la web (botón "Venta online" en /carga).
            ->where('productos.venta_online', 1)
            ->get();

        // Precio web = precio_unidad + recargo, al entero. Sin precio vendible no se publica.
        $recargo = Precio::recargoOnline();
        $productos = $productos->map(function ($producto) use ($recargo) {
            $producto->precio_online = Precio::online($producto->precio_unidad, $recargo);
            return $producto;
        })->filter(function ($producto) {
            return $producto->precio_online !== null;
        });

        $imagenes = DB::table('imagen_producto')->
                select('imagen_producto.imagen_url', 'imagen_producto.productos_id')
                ->get()->groupBy('productos_id');

        // Stock que ve el e-commerce: fila de `stock` de la sucursal configurada.
        $sucursalEcommerce = (int) config('ecommerce.sucursal_id');
        $stockEcommerce = $sucursalEcommerce > 0
            ? DB::table('stock')->where('sucursal_id', $sucursalEcommerce)->pluck('stock', 'productos_id')
            : collect();

        // Sucursales desde donde se vende online y que tienen stock del producto.
        // Por ahora es solo la sucursal del e-commerce; el formato (lista) admite más a futuro.
        $sucursalesConStock = $this->sucursalesConStock($sucursalEcommerce > 0 ? [$sucursalEcommerce] : []);

        // Delegación = sucursal donde se dio de alta el producto (la primera fila de stock).
        $delegaciones = DB::table('stock')->
            join('sucursales', 'sucursales.id', 'stock.sucursal_id')->
            orderBy('stock.id')->
            select('stock.productos_id', 'sucursales.nombre', 'sucursales.codigo_postal')
            ->get()->unique('productos_id')->keyBy('productos_id');

        $salida = $productos->map(function ($producto) use ($imagenes, $delegaciones, $stockEcommerce, $sucursalesConStock) {
            $delegacion = $delegaciones->get($producto->id);
            $imagenesProducto = ($imagenes->get($producto->id) ?? collect())
                ->map(function ($imagen) { return Imagen_producto::absoluta($imagen->imagen_url); })
                ->values()->all();

            return array_merge([
                'id' => null,
                'codigo_barras' => $producto->codigo_barras,
                'nombre' => $producto->nombre,
                'descripcion' => $producto->descripcion,
                // Precio de venta online con el recargo, formato "66000.00" (ver Precio::online).
                'precio_unidad' => $producto->precio_online,
                'precio_mayorista' => Precio::normalizar($producto->precio_mayorista),
                'stock' => (int) ($stockEcommerce->get($producto->id) ?? 0),
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
                'sucursales' => ($sucursalesConStock->get($producto->id) ?? collect())->values()->all(),
            ]);
        });

        // Hay celdas en latin1 real en la base: sin esto json_encode falla y la API da 500.
        return response()->json(Utf8::sanear($salida->values()->all()), 201);
    }

    /**
     * Por producto, las sucursales (de las indicadas) con stock > 0 y sus datos de contacto
     * / origen de envío. Devuelve collection productos_id => [ {id, nombre, ...}, ... ].
     */
    private function sucursalesConStock(array $sucursalIds)
    {
        if (count($sucursalIds) === 0) {
            return collect();
        }

        return DB::table('stock')
            ->join('sucursales', 'sucursales.id', 'stock.sucursal_id')
            ->whereIn('stock.sucursal_id', $sucursalIds)
            ->where('stock.stock', '>', 0)
            ->orderBy('sucursales.id')
            ->select('stock.productos_id', 'stock.stock', 'sucursales.id', 'sucursales.nombre',
                'sucursales.direccion', 'sucursales.provincia', 'sucursales.codigo_postal')
            ->get()
            ->groupBy('productos_id')
            ->map(function ($filas) {
                return $filas->map(function ($fila) {
                    return [
                        'id' => (int) $fila->id,
                        'nombre' => $fila->nombre,
                        'direccion' => $fila->direccion,
                        'provincia' => $fila->provincia,
                        'codigo_postal' => $fila->codigo_postal,
                        'stock' => (int) $fila->stock,
                    ];
                });
            });
    }
}
