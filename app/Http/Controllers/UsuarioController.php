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
        // SEGURIDAD: Validación de inputs
        $validated = $request->validate([
            'usuario' => 'required|string|max:100',
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'telefono' => 'nullable|string|max:20',
            'rol' => 'required|integer|exists:roles,id',
            'sucursales' => 'required|integer|exists:sucursales,id',
            'clave' => 'nullable|string|min:6'
        ]);

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
        $usuario->usuario = $validated['usuario'];
        //Si la clave esta vacía se mantiene la actual al modificar un usuario
        if(isset($validated['clave']) && $validated['clave'] !== null && $validated['clave'] !== "")
             {
        // Usar bcrypt en lugar de SHA1 para mayor seguridad
        $usuario->clave = bcrypt($validated['clave']);
             }
        $usuario->nombre = $validated['nombre'];
        $usuario->apellido = $validated['apellido'];
        $usuario->telefono = $validated['telefono'];
        $usuario->rol_id = $validated['rol'];
        $usuario->sucursal_id = $validated['sucursales'];
        $usuario->save();

        return redirect('usuario/mensaje/'.base64_encode($mensaje));
    }

    public function delete(Request $request,$id)
    {
       // Verificación de autenticación mediante API key o sesión de usuario
       if (!auth()->check()) {
        $apiKey = $request->header('Authorization') ? str_replace('Bearer ', '', $request->header('Authorization')) : $request->input('apiKey');

        if (!$apiKey || $apiKey !== config('app.api_secret_key')) {
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
