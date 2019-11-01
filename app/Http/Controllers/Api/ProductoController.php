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
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        //Sentencia obtener token
        $credentials = request(['email', 'password']);

        if(!Auth::attempt($credentials))
            return response()->json([
                'mensaje' => 'Usuario o password incorrectas'
            ], 401);

        $tokens = DB::table('oauth_access_tokens')->
                        where('user_id', $request->user()->id)->
                        where('revoked', 0)->
                        get();

        if($tokens != '[]') {
            $productos = DB::table('productos')->
                    join('categorias', 'categorias.id', 'productos.categorias_id')->
                    join('proveedor', 'proveedor.id', 'productos.proveedores_id')->
                    join('imagen_producto', 'imagen_producto.productos_id', 'productos.id')->
                    select('productos.codigo_barras', 'productos.nombre', 'productos.precio_unidad', 'productos.costo', 'productos.stock', 'productos.stock_minimo', 'productos.usuario', 'productos.fecha', 'productos.precio_mayorista', 'productos.es_comodato', 'categorias.nombre AS categoria', 'imagen_producto.imagen_url AS imagen', 'proveedor.nombre AS nombre_proveedor', 'proveedor.apellido AS apellido_proveedor')
                    ->get();

            return response()->json($productos, 201);
        } else {
            return response()->json('Debe autenticarse', 201);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
