<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Rol;
use App\Models\Sucursales;
use App\Models\Usuario;
use Illuminate\Support\Facades\Storage;
use Image;


class UsuarioController extends Controller
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
        $sucursales = Sucursales::all();
        $usuarios=  Usuario::join("sucursales","sucursales.id", "=", "usuarios.sucursal_id")
        ->leftjoin("roles","roles.id","=","usuarios.rol_id")
        ->select("usuarios.id", "usuarios.nombre","usuarios.apellido","usuarios.clave","usuarios.rol_id","usuarios.telefono","sucursales.nombre as nombre_sucursal","roles.nombre as nombre_rol")
        ->orderBy('nombre')
        ->get();
        $roles=Rol::all();
        return view("usuarios.accion",compact("mensaje","sucursales","usuarios","roles"));
    }


    public function save(Request $request)
    {
         $accion=0;
        if ($request->id_usuario != "")
        {
            $accion=1;
            $usuario = Usuario::find($request->id_usuario);
            $mensaje = "Modificación realizada exitosamente";     
        }
        else
        {
            $usuario = new Usuario();
            $mensaje = "Alta realizada exitosamente";  
        }
        $usuario->usuario = $request->usuario;
        //Si la clave esta vacía se mantiene la actual al modificar un usuario
        if($request->clave!="")
        {
            // NOTA: Usar password_hash con bcrypt es más seguro que SHA1
            // Para migración gradual, mantenemos compatibilidad con sistema legacy
            $semilla = env('LEGACY_HASH_SEED', '$%Reset20122017AnnaLuca#^');
            $usuario->clave = sha1($request->clave . $semilla);
        }
        $usuario->nombre = $request->nombre;
        $usuario->apellido = $request->apellido;
        $usuario->telefono = $request->telefono;
        $usuario->rol_id = $request->rol;
        $usuario->sucursal_id = $request->sucursales;
        $usuario->save();

        return redirect('usuario/mensaje/'.base64_encode($mensaje));
    }

    public function delete(Request $request,$id)
    {
        // Verificar autenticación
        if (!isset($_COOKIE["kiosco"])) {
            $apiKey = env('API_KEY_INTERNAL');
            if (!isset($_GET["apiKey"]) || !$apiKey || $_GET["apiKey"] !== $apiKey) {
                return response()->json(['error' => 'No autorizado'], 401);
            }
        }

        Usuario::destroy($id);
        return response()->json(array("proceso" => "OK"));
    }

public function getUsuario(Request $request, $id)
{ 
  return response()->json(Usuario::find($id));
}

}
