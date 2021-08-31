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

   
}
