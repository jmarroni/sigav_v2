<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Support\Utf8;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Sucursales;
use App\Models\Categoria;
use App\Models\Categoria_log;
use App\Models\RelacionCategoriaProveedor;
use Illuminate\Support\Facades\Storage;
use Image;


class CategoriaController extends Controller
{
    public function __construct(){
        // Protección mediante middleware de autenticación de Laravel
        $this->middleware('auth');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // El aviso se muestra en la vista (escapado); no se pasa como $mensaje para que el layout no lo repita.
        $aviso = $request->mensaje ? (string) base64_decode($request->mensaje, true) : null;
        $categorias = Utf8::sanearModelos(Categoria::orderBy('nombre')->get());

        return view("categorias.accion", compact("categorias", "aviso"));
    }


    public function save(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'abreviatura' => 'required|string|max:20',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'abreviatura.required' => 'La abreviatura es obligatoria.',
        ]);
         $accion=0;
         $categoria_logs = new Categoria_log();
        if ($request->id_categoria != "")
        {
            $accion=1;
            $categoria = Categoria::find($request->id_categoria);
            $mensaje = "Modificación realizada exitosamente";   
            $categoria_logs->tipo_operacion = 'Actualización';     
        }
        else
        {
            $categoria = new Categoria();
            $mensaje = "Alta realizada exitosamente";
            $categoria->habilitada=1;
            $categoria_logs->tipo_operacion = 'Alta';    
        }
        $categoria->nombre = $request->nombre;
        $categoria->usuario = $_COOKIE["kiosco"];
        $categoria->abreviatura = $request->abreviatura;
        $categoria->save();
        if ($accion==1)
             {
                $categoria_logs->categoria_id=$request->id_categoria;
            } 
            else
            {
                $categoria_logs->categoria_id= $categoria->id;
            } 
        $categoria_logs->usuario = $_COOKIE["kiosco"];
        $categoria_logs->save();

        return redirect('categoria/mensaje/'.base64_encode($mensaje));
    }

    public function delete(Request $request,$id)
    {
    Categoria::destroy($id);
    $categoria_logs = new Categoria_log();
    $categoria_logs->usuario = $_COOKIE["kiosco"];
    $categoria_logs->tipo_operacion = 'Baja';
    $categoria_logs->categoria_id = $id;
    $categoria_logs->save();

    return response()->json(array("proceso" => "OK"));
}

public function getCategoria(Request $request, $id)
{ 
  $categoria = Categoria::find($id);
  // Celdas en latin1 real (datos de Mercado) rompen json_encode → 500 al tocar "Modificar".
  return response()->json($categoria ? Utf8::sanear($categoria->toArray()) : null);
}

public function checkProducts(Request $request, $id)
{
    $productos=Producto::select('id')->where('categorias_id', '=', $id)->get();
    $categorias=RelacionCategoriaProveedor::select('id')->where('categoria_id', '=', $id)->get();
    if (count($productos)>0 || count($categorias)>0 )
        return response()->json(array("proceso" => "FAIL"));
    else 
        return response()->json(array("proceso" => "OK"));
}
public function changeStatus(Request $request, $id)
{ 
    $categoria_logs = new Categoria_log();
    $categoria_logs->usuario = $_COOKIE["kiosco"];
    $categoria_logs->categoria_id = $id;
    $categoria = Categoria::find($id);
    if ($categoria->habilitada == 0) 
    {
        $categoria->habilitada=1;
        $categoria_logs->tipo_operacion = 'Habilitar';
    }
    else
    {
        $categoria->habilitada=0;
        $categoria_logs->tipo_operacion = 'Desabilitar';
    }
    $categoria->save();
    $categoria_logs->save();

    return response()->json(array("proceso" => "OK","status"=>$categoria->habilitada));
}

}
