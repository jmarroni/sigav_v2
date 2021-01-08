<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
        if (!isset($_COOKIE["kiosco"]) || !isset($_COOKIE["sucursal"])) {
            redirect('/');
            exit();
        }
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $mensaje = $request->mensaje;
        $sucursal = (isset($request->sucursal)?$request->sucursal:Sucursales::getSucursal());
        $productos = Producto::with(['stock_' => 
            function ($query) use ($sucursal)
            {
                $query->where("sucursal_id",$sucursal);
            }])->get();
        $total=count($productos);
        $categorias=Categoria::all();

        return view("categorias.accion",compact("categorias","total","mensaje","productos"));
    }


    public function save(Request $request)
    {
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
       if (!isset($_COOKIE["kiosco"])) {
        if (!isset($_GET["apiKey"]) || $_GET["apiKey"] != "a0a035dc5213448bb1a130c27f2494c5")
            header('Location: /');
        else{
            header('Access-Control-Allow-Origin: *');
            header('Content-Type: application/json');
        }
    }
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
  return response()->json(Categoria::find($id));
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
