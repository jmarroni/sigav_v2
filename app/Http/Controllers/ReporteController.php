<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Factura;
use App\Models\Caja;
use App\Models\Sucursales;
use App\Models\Venta;
use App\Models\Stock_log;
use App\Models\Categoria_log;
use App\Models\Rol;
use App\Models\Transferencia;
use App\Models\Transferencia_log;
use App\Models\Producto;
use App\Models\PedidosOptica;
use App\Models\NotaCredito;
use App\Models\FacturasProveedores;
use App\Models\DetalleFacturasProveedores;
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

    $facturas="";
    //Para usuarios administradores y de sucursal se muestran las facturas de todas las sucursales
    if (Rol::getRol()!=1)
    {
      //Se valida si se seleccionó un rango de fecha
      if(isset($request->reporte_desde) && $request->reporte_desde!="" && isset($request->reporte_hasta) && $request->reporte_hasta!="")
      {
        $facturas=Factura::join("sucursales","sucursales.id","=","factura.sucursal_id")
        ->where("factura.cae","<>","")
        ->where("factura.fechacae","<>","")
        ->whereBetween('factura.fecha', [$request->reporte_desde, $request->reporte_hasta])
        ->where("sucursales.id","=",Sucursales::getSucursal($_COOKIE["sucursal"]))
        ->select("factura.*","sucursales.nombre as nombre_sucursal")
        ->OrderBy("fecha","desc")
        ->get();
      }
      else
      {   
        //Sino se muestran todas las facturas creadas
       $facturas=Factura::join("sucursales","sucursales.id","=","factura.sucursal_id")
       ->where("factura.cae","<>","")
       ->where("factura.fechacae","<>","")
       ->select("factura.*","sucursales.nombre as nombre_sucursal")
       ->OrderBy("fecha","desc")
       ->get();

     }
   }
   else //Si el rol de usuario es igual a ventas
   {//Se valida si se seleccionó un rango de fecha
    if(isset($request->reporte_desde) && $request->reporte_desde!="" && isset($request->reporte_hasta) && $request->reporte_hasta!="")
    {
      $facturas=Factura::join("sucursales","sucursales.id","=","factura.sucursal_id")
      ->where("factura.cae","<>","")
      ->where("factura.fechacae","<>","")
      ->whereBetween('factura.fecha', [$request->reporte_desde, $request->reporte_hasta])
      ->where("sucursales.id","=",Sucursales::getSucursal($_COOKIE["sucursal"]))
      ->select("factura.*","sucursales.nombre as nombre_sucursal")
      ->OrderBy("fecha","desc")
      ->get();
    }
    else
    {//Sino se muestran todas las facturas creadas en la sucursal a la que corresponde ese usuario
      $facturas=Factura::join("sucursales","sucursales.id","=","factura.sucursal_id")
      ->where("factura.cae","<>","")
      ->where("factura.fechacae","<>","")
      ->where("sucursales.id","=",Sucursales::getSucursal($_COOKIE["sucursal"]))
      ->select("factura.*","sucursales.nombre as nombre_sucursal")
      ->OrderBy("fecha","desc")
      ->get();
    }
  }

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
  ->join("productos","productos.id","=","stock_logs.productos_id")
  ->select("stock_logs.*","sucursales.nombre as sucursal","productos.nombre","productos.codigo_barras")
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

public function reportePresupuesto(request $request)
{
  if(isset($request->reporte_desde) && $request->reporte_desde!="" && isset($request->reporte_hasta) && $request->reporte_hasta!="" )
  {
    $presupuestos=Factura::join("sucursales","sucursales.id","=","factura.sucursal_id")
    ->where("factura.cae","=","")
    ->where("factura.fechacae","=","")
    ->whereBetween('factura.fecha', [$request->reporte_desde, $request->reporte_hasta])
    ->where("sucursales.id","=",Sucursales::getSucursal($_COOKIE["sucursal"]))
    ->select("factura.*","sucursales.nombre as nombre_sucursal")
    ->OrderBy("fecha","desc")
    ->get();
  }
  else
  {   
   $presupuestos=Factura::join("sucursales","sucursales.id","=","factura.sucursal_id")
   ->where("factura.cae","=","")
   ->where("factura.fechacae","=","")
   ->select("factura.*","sucursales.nombre as nombre_sucursal")
   ->OrderBy("fecha","desc")
   ->get();

   return view("reportes.reportePresupuestos",compact("presupuestos"));
 }
}

public function reporteNotasCredito(request $request)
{
  if(isset($request->reporte_desde) && $request->reporte_desde!="" && isset($request->reporte_hasta) && $request->reporte_hasta!="" )
  {
    $notasCredito=NotaCredito::join("sucursales","sucursales.id","=","nota_de_credito.sucursal_id")
    ->whereBetween('nota_de_credito.fecha', [$request->reporte_desde, $request->reporte_hasta])
    ->where("sucursales.id","=",Sucursales::getSucursal($_COOKIE["sucursal"]))
    ->select("nota_de_credito.*","sucursales.nombre as nombre_sucursal")
    ->OrderBy("fecha","desc")
    ->get();
  }
  else
  {   
   $notasCredito=NotaCredito::join("sucursales","sucursales.id","=","nota_de_credito.sucursal_id")
   ->select("nota_de_credito.*","sucursales.nombre as nombre_sucursal")
   ->OrderBy("fecha","desc")
   ->get();

   return view("reportes.notasCredito",compact("notasCredito"));
 }
}

public function reporteStock(request $request)
{
 $sucursales = Sucursales::all();
 $sucursal = (isset($request->sucursal)?$request->sucursal:Sucursales::getSucursal());
 $productos="";
 if ($sucursal!=0)
 {
   $productos=Producto::with(['stock_' => 
    function ($query) use ($sucursal)
    {
        //$query->where("sucursal_id","<>",0);
      $query->where("sucursal_id",$sucursal);
    }])
   ->leftjoin("stock","stock.productos_id", "=", "productos.id")
   ->join("sucursales","sucursales.id", "=", "stock.sucursal_id")
   ->leftjoin("categorias","categorias.id", "=", "productos.categorias_id")
   ->leftjoin("proveedor","proveedor.id", "=", "productos.proveedores_id")
   ->where("stock.sucursal_id","=",$sucursal)
   ->select("productos.*","proveedor.nombre as nombreProveedor","proveedor.apellido as apellidoProveedor","sucursales.nombre as sucursal","stock.stock as stockactual","stock.stock_minimo as stockminimoActual")
   ->OrderBy("sucursales.nombre","asc")
   ->get();
 }
 else
     { //Muestra los productos de todas las sucursales
      $productos=Producto::with(['stock_' => 
        function ($query) use ($sucursal)
        {
        //$query->where("sucursal_id","<>",0);
            //$query->where("sucursal_id",$sucursal);
        }])
      ->leftjoin("stock","stock.productos_id", "=", "productos.id")
      ->join("sucursales","sucursales.id", "=", "stock.sucursal_id")
      ->leftjoin("categorias","categorias.id", "=", "productos.categorias_id")
      ->leftjoin("proveedor","proveedor.id", "=", "productos.proveedores_id")
        //->where("stock.sucursal_id","=",$sucursal)
      ->select("productos.*","proveedor.nombre as nombreProveedor","proveedor.apellido as apellidoProveedor","sucursales.nombre as sucursal","stock.stock as stockactual","stock.stock_minimo as stockminimoActual")
      ->OrderBy("sucursales.nombre","asc")
      ->get();
    }
    return view("reportes.stocks",compact("productos","sucursales","sucursal"));
 //return response()->json($productos);

  }

  public function reportePagoProveedores(request $request)
  {
   $sucursales = Sucursales::all();
   $sucursal = (isset($request->sucursal)?$request->sucursal:Sucursales::getSucursal());
   $facturas="";
   if ($sucursal!=0)
   {
     $facturas=FacturasProveedores::join("sucursales","sucursales.id", "=", "facturas_proveedores.id_sucursal")
     ->join("proveedor","proveedor.id", "=", "facturas_proveedores.id_proveedor")
     ->join("remito_facturas_proveedores","remito_facturas_proveedores.id_factura_proveedor", "=", "facturas_proveedores.id")
     ->select("facturas_proveedores.*","proveedor.nombre as nombreProveedor","proveedor.apellido as apellidoProveedor", "sucursales.nombre as sucursal","remito_facturas_proveedores.archivo")
     ->where("facturas_proveedores.id_sucursal","=",$sucursal)
     ->OrderBy("sucursales.nombre","asc")
     ->get();
   }
   else
     { //Muestra los productos de todas las sucursales
       $facturas=FacturasProveedores::join("sucursales","sucursales.id", "=", "facturas_proveedores.id_sucursal")
       ->join("proveedor","proveedor.id", "=", "facturas_proveedores.id_proveedor")
       ->join("remito_facturas_proveedores","remito_facturas_proveedores.id_factura_proveedor", "=", "facturas_proveedores.id")
       ->select("facturas_proveedores.*","proveedor.nombre as nombreProveedor","proveedor.apellido as apellidoProveedor", "sucursales.nombre as sucursal","remito_facturas_proveedores.archivo")
       ->OrderBy("sucursales.nombre","asc")
       ->get();
     }
     return view("reportes.pagoProveedores",compact("facturas","sucursales","sucursal"));
 //return response()->json($productos);

   }
   public function reporteTransferencias(request $request)
   {
     $sucursales = Sucursales::all();
     $sucursal = (isset($request->sucursal)?$request->sucursal:Sucursales::getSucursal());
     $transferencias="";
     if ($sucursal!=0)
     {
       $transferencias=Transferencia::join("sucursales as so","so.id","=","transferencias.sucursal_origen_id")
       ->join("sucursales as sd","sd.id","=","transferencias.sucursal_destino_id")
       ->join("estado_transferencia","estado_transferencia.id","=","transferencias.estado_id")
       ->leftjoin("remito_transferencias","remito_transferencias.id_transferencia", "=", "transferencias.id")
       ->select("transferencias.*","so.nombre as origen","sd.nombre as destino","remito_transferencias.archivo","estado_transferencia.nombre as estado")
       ->where("transferencias.sucursal_origen_id","=",$sucursal)
       ->OrderBy("transferencias.fecha","desc")
       ->get();
     }
     else
     { //Muestra las transferencias de todas las sucursales
      $transferencias=Transferencia::join("sucursales as so","so.id","=","transferencias.sucursal_origen_id")
      ->join("sucursales as sd","sd.id","=","transferencias.sucursal_destino_id")
      ->join("estado_transferencia","estado_transferencia.id","=","transferencias.estado_id")
      ->leftjoin("remito_transferencias","remito_transferencias.id_transferencia", "=", "transferencias.id")
      ->select("transferencias.*","so.nombre as origen","sd.nombre as destino","remito_transferencias.archivo","estado_transferencia.nombre as estado")
      ->OrderBy("transferencias.fecha","desc")
      ->get();
    }
    return view("reportes.transferencias",compact("transferencias","sucursales","sucursal"));
 //return response()->json($productos);

  }
  public function reportePedidos(request $request)
  {
   $sucursales = Sucursales::all();
   $sucursal = (isset($request->sucursal)?$request->sucursal:Sucursales::getSucursal());
   $transferencias="";
   if ($sucursal!=0)
   {
     $pedidos=PedidosOptica::join("sucursales","sucursales.id","=","pedidos_optica.id_sucursal")
     ->join("pedidos_optica_remito","pedidos_optica_remito.id_pedido","=","pedidos_optica.id")
     ->select("pedidos_optica.*","sucursales.nombre as sucursal","pedidos_optica_remito.*")
     ->where("pedidos_optica.id_sucursal","=",$sucursal)
     ->OrderBy("pedidos_optica.fecha","desc")
     ->get();
   }
   else
     { //Muestra los pedidos de todas las sucursales
      $pedidos=PedidosOptica::join("sucursales","sucursales.id","=","pedidos_optica.id_sucursal")
      ->join("pedidos_optica_remito","pedidos_optica_remito.id_pedido","=","pedidos_optica.id")
      ->select("pedidos_optica.*","sucursales.nombre as sucursal","pedidos_optica_remito.*")
      ->OrderBy("pedidos_optica.fecha","desc")
      ->get();
    }
    return view("reportes.pedidos",compact("pedidos","sucursales","sucursal"));
 //return response()->json($productos);

  }
}
