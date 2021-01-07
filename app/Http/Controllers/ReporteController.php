<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Factura;
use DB;
class ReporteController extends Controller
{
    public function __construct(){
        if (!isset($_COOKIE["kiosco"]) || !isset($_COOKIE["sucursal"])) {
            redirect('/');
            exit();
        }
    }

    public function factura(Request $request,$reporte_desde = null,$reporte_hasta = null){
        \DB::connection()->enableQueryLog();
        
        if ((isset($request->reporte_desde))&&(isset($request->reporte_hasta)))
            $facturas = Factura::where("presupuesto","=",0)->where(\DB::raw("SUBSTRING(fecha,0,10)"), ">=",$request->reporte_desde)->where(\DB::raw("SUBSTRING(fecha,0,10)"), "<=",$request->reporte_hasta)->where("cae","<>","''")->where("cae","<>","1111")->get();
        elseif (isset($request->reporte_hasta))
            $facturas = Factura::where("presupuesto","=",0)->where(\DB::raw("SUBSTRING(fecha,0,10)"), "<=",$request->reporte_hasta)->where("cae","<>","''")->where("cae","<>","1111")->get();
        elseif (isset($request->reporte_desde))
            $facturas = Factura::where("presupuesto","=",0)->where(\DB::raw("SUBSTRING(fecha,0,10)"),">=",$request->reporte_desde)->where("cae","<>","''")->where("cae","<>","1111")->get();
        else $facturas = Factura::where("presupuesto","=",0)->where("cae","<>","''")->where("cae","<>","1111")->get();
        $queries = DB::getQueryLog();
        $last_query = end($queries);
        dd($last_query);
        return view("reportes.factura",compact("facturas","reporte_desde","reporte_hasta"));
    }
}
