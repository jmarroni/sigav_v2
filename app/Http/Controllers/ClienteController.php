<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Seccion;
use App\Models\Producto;
use App\Models\Sucursales;
use App\Models\Cliente;
use Illuminate\Support\Facades\Storage;
use Image;


class ClienteController extends Controller
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
        $clientes=Cliente::orderBy('razon_social')->get();
        return view("clientes.accion",compact("sucursal","clientes","mensaje"));
    }


    public function save(Request $request)
    {
        $accion=0;
        if ($request->id != "")
        {
            $cliente = CLiente::find($request->id);
            $mensaje = "Modificación realizada exitosamente";     
        }
        else
        {
            $cliente = new CLiente();
            $mensaje = "Alta realizada exitosamente";              
        }
        $cliente->razon_social = $request->razon_social;
        $cliente->domicilio_legal = $request->domicilio_legal;
        $cliente->codigo_postal = $request->codigo_postal;  
        $cliente->telefono = $request->telefono; 
        $cliente->provincia = $request->provincia; 
        $cliente->localidad= $request->localidad;   
        $cliente->cuit = $request->cuit;   
        $cliente->condicion_iva = $request->condicion_iva;   
        $cliente->representante = $request->representante; 
        $cliente->email_representante = $request->email_representante;    
        $cliente->responsable_contratacion = $request->responsable_contratacion;    
        $cliente->email_constratacion    = $request->email_constratacion;  
        $cliente->responsable_pagos = $request->responsable_pagos;    
        $cliente->email_pagos = $request->email_pagos;   
        $cliente->consulta_proveedores = $request->consulta_proveedores;   
        $cliente->entrega_retiros= $request->entrega_retiros;  
        $cliente->localidad = $request->localidad;   
        $cliente->fecha_alta = date("Y-m-d H:i:s");  
        $cliente->deshabilitado = $request->localidad;     
        $cliente->save();
       
       return redirect('cliente/mensaje/'.base64_encode($mensaje));
 }

 public function delete($id)
 {
    // Verificar autenticación
    if (!isset($_COOKIE["kiosco"])) {
        $apiKey = env('API_KEY_INTERNAL');
        if (!isset($_GET["apiKey"]) || !$apiKey || $_GET["apiKey"] !== $apiKey) {
            return response()->json(['error' => 'No autorizado'], 401);
        }
    }

    Cliente::where('id', '=', $id)->delete();
    return response()->json(array("proceso" => "OK"));
}

public function getCliente($id)
{ 
  return response()->json(Cliente::find($id));
}

 public function pedidos(Request $request)
    {
        $mensaje = $request->mensaje;
        $sucursal = (isset($request->sucursal)?$request->sucursal:Sucursales::getSucursal());
        $clientes=Cliente::all();
        return view("clientes.pedidos",compact("sucursal","clientes","mensaje"));
    }

}
