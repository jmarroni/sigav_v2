<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Factura;
use App\Models\Caja;
use App\Models\Sucursales;
use App\Models\Venta;
use App\Models\Stock_log;
use App\Models\Categoria_log;
use App\Models\Transferencia_log;
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

    public function cierreCajaReporte(Request $request)
        {
             if (!isset($_COOKIE["kiosco"])) 
            {
                 header('Location: /');
            }
            $mensaje = $request->mensaje;
            $fechaActual=date("Y-m-d 00:00:01");
            $cierres=Caja::join("sucursales","sucursales.id", "=", "caja.sucursal_id")
            //->where("caja.fecha",">",$fechaActual)
            ->select("caja.*","sucursales.nombre as sucursal_nombre")
            ->get();
        return view("reportes.cierreCaja",compact("cierres","mensaje"));
        }

    public function cierreCajaAccion (Request $request)
        {
           if (!isset($_COOKIE["kiosco"])) 
            {
                 header('Location: /');
            }
        $fechaActual=date("Y-m-d 00:00:01");
        $caja_total = intval($request->cien) * 100 +
                        intval($request->cincuenta) * 50 +
                        intval($request->veinte) * 20 +
                        intval($request->diez) * 10 +
                        intval($request->cinco) * 5;
        $caja=new Caja();
        $caja->cincuenta=$request->cincuenta;
        $caja->cien=$request->cien;
        $caja->veinte=$request->veinte;
        $caja->diez=$request->diez;
        $caja->cinco=$request->cinco;
        $caja->fecha=date("Y-m-d H:i:s");
        $caja->usuario=$_COOKIE["kiosco"];
        $caja->operacion=$request->operacion;
        $caja->observacion=$request->observacion;
        $caja->total=$caja_total;
        $caja->sucursal_id=Sucursales::getSucursal($_COOKIE["sucursal"]);
        $caja->save();

        $ventas=Venta::where("fecha",">",$fechaActual)
        ->where("usuario","=",$_COOKIE["kiosco"])
        ->select("ventas.*")
        ->get();
        $total=0;
        if (count($ventas)>0)
        {
            foreach($ventas as $venta)
            {
                 $total += $venta->precio * $venta->cantidad;
            }
        }
        $cajasAperturadas=Caja::where("fecha",">",$fechaActual)
        ->where("usuario","=",$_COOKIE["kiosco"])
        ->where("sucursal_id","=",Sucursales::getSucursal($_COOKIE["sucursal"]))
        ->where("operacion","=",1)
        ->OrderBy("caja.id","desc")
        ->get();

        $totalcaja=0;
        if (count($cajasAperturadas)>0)
        {
            foreach($cajasAperturadas as $cajaAperturada)
            {
                    $totalcaja= $cajaAperturada->cien * 100 +
                    $cajaAperturada->cincuenta * 50 +
                    $cajaAperturada->veinte * 20 +
                    $cajaAperturada->diez * 10 +
                    $cajaAperturada->cinco * 5;
            }
        }

        $cabeceras = 'From: jmarroni@fidegroup.com.ar' . "\r\n" .
                        'Reply-To: jmarroni@fidegroup.com.ar' . "\r\n" .
                        'X-Mailer: PHP/' . phpversion();
            mail("jmarroni@gmail.com", "Cierre de caja fecha ".date("Y-m-d H:i:s"), "Cierre de caja por ".$_COOKIE["kiosco"].", \n\r Billeter: \n\r- Cien: ".$request->cien." \n\r- Cincuenta: ".$request->cincuenta." \n\r- Veinte: ".$request->veinte." \n\r- Diez: ".$request->diez." \n\r- Cinco: ".$request->cinco." \n\r Operacion \n\r {$request->operacion} \n\r Observacion \n\r {$request->observacion} \n\r Total: $caja_total Total marcado en venta: ".($total + $totalcaja),$cabeceras);

        $mensaje="Caja ingresada correctamente";   
        return redirect('cierreCajaReporte/mensaje/'.base64_encode($mensaje));       
                                       
    }

    public function logProductos(request $request)
    {
        $productos=Stock_log::leftjoin("sucursales","sucursales.id","=","stock_logs.sucursal_id")
        ->select("stock_logs.*","sucursales.nombre")
        ->OrderBy("stock_logs.id","desc")
        ->get();
        return view("reportes.logsProductos",compact("productos"));
        
    }

     public function logCategorias(request $request)
    {
        $categorias=Categoria_log::all();
        return view("reportes.logsCategorias",compact("categorias"));
        
    }

     public function logTransferencias(request $request)
    {
        $transferencias=Transferencia_log::leftjoin("transferencias","transferencias.id","=","transferencias_logs.transferencia_id")
        ->leftjoin("sucursales as so","so.id","=","transferencias_logs.sucursal_origen_id")
        ->leftjoin("sucursales as sd","sd.id","=","transferencias_logs.sucursal_origen_id")
        ->select("transferencias_logs.*","so.nombre as origen","sd.nombre as destino")
        ->get();
        return view("reportes.logsTransferencias",compact("transferencias"));

    }



}
