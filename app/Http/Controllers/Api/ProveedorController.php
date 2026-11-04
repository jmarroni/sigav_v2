<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Imagen_producto;
use App\Support\Utf8;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProveedorController extends Controller
{
    /**
     * Artesanos (proveedores) con sus categorías de producción.
     * El usuario interno que los cargó no se expone.
     */
    public function proveedores(Request $request)
    {
        $proveedores = DB::table('proveedor')
            ->orderBy('nombre')
            ->select('id', 'nombre', 'apellido', 'direccion', 'ciudad', 'provincia', 'telefono', 'mail', 'sitio_web', 'titulo', 'descripcion')
            ->get();

        $categoriasPorProveedor = DB::table('relacion_categoria_proveedor')
            ->join('categorias', 'categorias.id', '=', 'relacion_categoria_proveedor.categoria_id')
            ->orderBy('categorias.nombre')
            ->select('relacion_categoria_proveedor.proveedor_id', 'categorias.nombre')
            ->get()
            ->groupBy('proveedor_id');

        $imagenesPorProveedor = DB::table('proveedor_imagen')
            ->orderBy('orden')
            ->select('proveedor_id', 'imagen_url')
            ->get()
            ->groupBy('proveedor_id');

        $salida = $proveedores->map(function ($proveedor) use ($categoriasPorProveedor, $imagenesPorProveedor) {
            return [
                'id' => (int) $proveedor->id,
                'nombre' => $proveedor->nombre,
                'apellido' => $proveedor->apellido,
                'direccion' => $proveedor->direccion,
                'ciudad' => $proveedor->ciudad,
                'provincia' => $proveedor->provincia,
                'telefono' => $proveedor->telefono,
                'mail' => $proveedor->mail,
                'sitio_web' => $proveedor->sitio_web,
                'titulo' => $proveedor->titulo,
                'descripcion' => $proveedor->descripcion,
                'categorias' => ($categoriasPorProveedor->get($proveedor->id) ?? collect())
                    ->pluck('nombre')
                    ->values()
                    ->all(),
                // Hasta 3, en el orden del slot; URL absoluta para consumidores externos.
                'imagenes' => ($imagenesPorProveedor->get($proveedor->id) ?? collect())
                    ->map(function ($imagen) { return Imagen_producto::absoluta($imagen->imagen_url); })
                    ->values()
                    ->all(),
            ];
        });

        // Hay celdas en latin1 real en la base: sin esto json_encode falla y la API da 500.
        return response()->json(Utf8::sanear($salida->values()->all()));
    }
}
