<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Lcobucci\JWT\Parser;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Cliente;

class ClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function consultarClientes(Request $request)
    {

        $keyword=$request->keyword;
        $clientes =    Cliente::where("clientes.razon_social","like", "%" . $keyword. "%","OR")
        ->orWhere("clientes.cuit","like", "%" . $keyword. "%")
        ->select("clientes.*")
        ->OrderBy("clientes.razon_social")
        ->get();
        return response()->json($clientes, 201);
    }

    public function altaCliente(Request $request)
    {
        $clientes = json_decode($request->cliente, true);
        $error="";
        foreach($clientes as $cliente)
        {
            $consultaCliente = Cliente::where("clientes.cuit","=",$cliente['cuit'])->first();
            if ($consultaCliente!=null )
            {
                        $error="Ya existe un cliente registrado con ese CUIT";//Cliente ya existe
                    }
                    else
                    {
                        $objcliente = new CLiente();              
                        $objcliente->razon_social = $cliente['razon_social'];
                        $objcliente->domicilio_legal = $cliente['domicilio_legal'];
                        $objcliente->codigo_postal = $cliente['codigo_postal'];  
                        $objcliente->telefono = $cliente['telefono']; 
                        $objcliente->provincia = $cliente['provincia']; 
                        $objcliente->localidad= $cliente['localidad'];   
                        $objcliente->cuit = $cliente['cuit'];   
                        $objcliente->condicion_iva = $cliente['condicion_iva'];   
                        $objcliente->representante = $cliente['representante']; 
                        $objcliente->email_representante = $cliente['email_representante'];    
                        $objcliente->responsable_contratacion = $cliente['responsable_contratacion'];    
                        $objcliente->email_constratacion    = $cliente['email_constratacion'];  
                        $objcliente->responsable_pagos = $cliente['responsable_pagos'];    
                        $objcliente->email_pagos = $cliente['email_pagos'];   
                        $objcliente->consulta_proveedores = $cliente['consulta_proveedores'];   
                        $objcliente->entrega_retiros= $cliente['entrega_retiros'];    
                        $objcliente->fecha_alta = date("Y-m-d H:i:s");  
                        $objcliente->deshabilitado = 0;     
                        $objcliente->save();
                    }
                }
                if ($error=="")
                    return response()->json(array("resultado" => "OK"));
                else
                    return response()->json(array("resultado" => $error));
            }

        }
