<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Seccion;
use App\Models\Producto;
use App\Models\Sucursales;
use App\Models\Rol;
use App\Models\RelacionSeccionRol;
use Illuminate\Support\Facades\Storage;
use Image;


class RolController extends Controller
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
        $roles=Rol::orderBy('nombre')->get();
        $secciones=Seccion::all();
        $seccion_id='';
        $relacionesSeccionRol=RelacionSeccionRol::join("seccion","seccion.id", "=", "relacion_seccion_rol.secciones_id")
        ->select("seccion.id","seccion.nombre","relacion_seccion_rol.roles_id")
        ->get();
        return view("roles.accion",compact("sucursal","roles","mensaje","secciones","relacionesSeccionRol","seccion_id"));
    }


    public function save(Request $request)
    {
        $accion=0;
        if ($request->id != "")
        {
            $accion=1;
            $rol = Rol::find($request->id);
            RelacionSeccionRol::where('roles_id', '=', $request->id)->delete();   
            $mensaje = "Modificación realizada exitosamente";     
        }
        else
        {
            $rol = new Rol();
            $mensaje = "Alta realizada exitosamente";              
        }
        $rol->nombre = $request->rol;
        $rol->fecha=date("Y-m-d H:i:s");
        $rol->habilitado = 1;  
        if ($request->habilitado==NULL)
        {
         $rol->habilitado = 0;   
        }
        $rol->save();
        if (isset($request->secciones))
             {
            foreach ($request->secciones as $seccion)
            {
                $relacionSeccionRol= new RelacionSeccionRol();
                if ($accion==1)
                    {
                        $relacionSeccionRol->roles_id=$request->id;
                    } 
                else
                    {
                        $relacionSeccionRol->roles_id= $rol->id;
                    } 
            $relacionSeccionRol->secciones_id=$seccion;
            $relacionSeccionRol->save();
            }  //endforeach
          } //endif    
       return redirect('rol/mensaje/'.base64_encode($mensaje));
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
    RelacionSeccionRol::where('roles_id', '=', $request->id)->delete();
    Rol::destroy($id);
    return response()->json(array("proceso" => "OK"));
}

}
