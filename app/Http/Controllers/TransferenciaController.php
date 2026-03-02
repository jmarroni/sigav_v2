<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\Stock;
use App\Models\Sucursales;
use App\Models\Transferencia;
use App\Models\EstadoTransferencia;
use App\Models\Transferencia_log;
use App\Models\Imagen_producto;
use App\Models\RelacionTransferenciaProductos;
use Illuminate\Support\Facades\Storage;
use App\Models\RemitoTransferencias;
use App\Models\Stock_log;
use Spipu\Html2Pdf\Html2Pdf;
use Image;


class TransferenciaController extends Controller
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
        $sucursales =Sucursales::OrderBy('nombre', 'desc')->get();
        return view("transferencias.accion",compact("mensaje","sucursales"));
    }


    public function saving(Request $request)
    {
        $numProductos=0;
        $numRemito=0;
        $htmlProductos="";
        $fecha=date("Y-m-d H:i:s");
        $usuario=strtoupper($_COOKIE["kiosco"]);
        $sucursalOrigen=Sucursales::where("id",$request->sucursal_origen)->first();
        $sucursalDestino=Sucursales::where("id",$request->sucursal_destino)->first();
        $nombreOrigen=strtoupper(trim($sucursalOrigen->nombre));
        $nombreDestino=strtoupper(trim($sucursalDestino->nombre));
        $mensaje = "Transferencia guardada exitosamente";  
        $transferencia= new Transferencia();
        $transferencia->sucursal_origen_id=$request->sucursal_origen;
        $transferencia->sucursal_destino_id=$request->sucursal_destino;
        $transferencia->fecha=date("Y-m-d H:i:s");
        $transferencia->estado_id=1;
        //$transferencia->comentario=$request->comentario;
        $transferencia->usuario=$_COOKIE["kiosco"];
        $transferencia->save();
        $arrayproductos = explode("||", $request->arrayproductos);
        foreach ($arrayproductos as $producto)
            {

                $numProductos=$numProductos+1;
                $producto_stock = explode(",", $producto);
                $RelacionTransferenciaProductos= new RelacionTransferenciaProductos();
                $RelacionTransferenciaProductos->tranferencia_id=$transferencia->id;
                $RelacionTransferenciaProductos->producto_id=$producto_stock[0];
                $RelacionTransferenciaProductos->cantidad=$producto_stock[1];
                $RelacionTransferenciaProductos->usuario=$_COOKIE["kiosco"];
                $RelacionTransferenciaProductos->save();
                $productos=Producto::where("id",$producto_stock[0])->first();

                 $htmlProductos .= utf8_encode("<tr>
      <td style='border-bottom: 1px solid #000;width: 50px'><i>$numProductos</i></td>
      <td style='border-bottom: 1px solid #000;width: 250px'><i>$productos->codigo_barras</i></td>
      <td style='border-bottom: 1px solid #000;width: 300px'><i>$productos->nombre</i></td>
      <td style='border-bottom: 1px solid #000;width: 100px'>".$producto_stock[1]."</td>
      </tr>
      ");
            }  //endforeach
        $transferenciaLog= new Transferencia_log();
        $transferenciaLog->sucursal_origen_id=$request->sucursal_origen;
        $transferenciaLog->sucursal_destino_id=$request->sucursal_destino;
        $transferenciaLog->transferencia_id=$transferencia->id;
        $transferenciaLog->usuario=$_COOKIE["kiosco"];
        $transferenciaLog->tipo_operacion="ALTA";
        $transferenciaLog->created_at=date("Y-m-d H:i:s");
        $transferenciaLog->updated_at=date("Y-m-d H:i:s");
        $transferenciaLog->save();

        $remito = new RemitoTransferencias();
        $remito->id_transferencia =$transferencia->id;
        $remito->fecha_generacion = date('Y-m-d-h-s');
        $remito->usuario = $_COOKIE["kiosco"];
        $remito->save();
        $numRemito=$remito->id;
        $html = utf8_encode("
              <style>
              h3{
                font-size:1em;
              }
              </style>
              <table>
              <tr>
              <td>  
              <b></b>
              </td>
              </tr>
              <tr>
              <td style='border: 2px solid #000;height: 100px;font-size: 14px;width: 700px;text-align: left;'>
              <b style='margin-left:420'>COMPROBANTE NRO:</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$numRemito<br />
              <b>DOCUMENTO</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br />
              <br/>
              <b>SUCURSAL ORIGEN</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$nombreOrigen<br />
              <b>SUCURSAL DESTINO</b>&nbsp;&nbsp;&nbsp;$nombreDestino<br />
              <b>USUARIO</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$usuario<br />
              <b>FECHA DE EMISI&Oacute;N</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$fecha<br />
              <b>N&Uacute;MERO DE ITEMS</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$numProductos<br />

              </td>
              </tr> </table>");
            
            $html.="<table><tr>
            <td style='border-bottom: 1px solid #000;width: 50px'><b>Nº</b></td>
            <td style='border-bottom: 1px solid #000;width: 250px'><b>Barra</b></td>
            <td style='border-bottom: 1px solid #000;width: 300px'><b>Nombre</b></td>
            <td style='border-bottom: 1px solid #000;width: 100px'><b>Cantidad</b></td>
            </tr>
            ";

            $html .=$htmlProductos;

            $html .= utf8_encode("</table>");

            $html2pdf = new HTML2PDF('P', 'A4', 'pt', true, 'UTF-8');
            $html2pdf->setDefaultFont('Arial');

            $html2pdf->writeHTML("<page>".str_replace("DOCUMENTO","ORIGINAL",$html)."<br><br><hr style='border-style: dotted;' /><br><br></page><page>".str_replace("DOCUMENTO","DUPLICADO",$html)."<br><br><hr style='border-style: dotted;' /><br><br></page>");

            $nombre_remito="/comprobantesTransferencias/comprobante".$numRemito.".pdf";
            $html2pdf->Output(__DIR__.'/../../../public'.$nombre_remito, "F");

            $remito->archivo=$nombre_remito;
            $remito->save();
   
         return response()->json(array("proceso" => "OK","comprobante" => $nombre_remito));
    }

    public function listar(Request $request)
    {
        $sucursal_activa = (Sucursales::getSucursal($_COOKIE["sucursal"]) !== null)?Sucursales::getSucursal($_COOKIE["sucursal"]):"";
        $transferenciasRealizadas=Transferencia::join("estado_transferencia as e","e.id","=","transferencias.estado_id")
        ->join("sucursales as so","so.id","=","transferencias.sucursal_origen_id")
        ->join("sucursales as sd","sd.id","=","transferencias.sucursal_destino_id")
        ->where("transferencias.sucursal_origen_id","=",$sucursal_activa,"OR")
        ->ORwhere("transferencias.sucursal_destino_id","=", $sucursal_activa)
        ->addselect("transferencias.*","e.nombre AS nombre_estado","e.id AS id_estado","so.nombre AS sucursal_origen_nombre","sd.nombre AS sucursal_destino_nombre","transferencias.fecha","transferencias.usuario")
        ->OrderBy("transferencias.fecha","desc")->limit(200)
        ->get();
        $productos=RelacionTransferenciaProductos::join("productos as p","p.id","=","relacion_transferencias_productos.producto_id")
        ->select("relacion_transferencias_productos.*","p.*")
        ->OrderBy("p.nombre","asc")
        ->get();
        $imagenes=Imagen_producto::all();
        $estados=EstadoTransferencia::where("estado_transferencia.id","<>","4")->get();
        return view("transferencias.transferenciasRealizadas",compact("sucursal_activa","transferenciasRealizadas","productos","imagenes","estados"));
    }

public function getTransferencia(Request $request, $id)
{ 
  return response()->json(Transferencia::find($id));
}
public function changeStatus(Request $request)
{ 
    $estadoAnterior=0;
    $transferencia= Transferencia::find($request->id_transferencia);
    $estadoAnterior=$transferencia->estado_id;
    $transferencia->estado_id=$request->id_estado;
    if (isset($request->comentario) && $request->comentario!=null && $request->comentario!="")
       { 
    $transferencia->comentario=$request->comentario;
        }
    $transferencia->save();
    $transferenciaLog= new Transferencia_log();
    $transferenciaLog->sucursal_origen_id=$transferencia->sucursal_origen_id;
    $transferenciaLog->sucursal_destino_id=$transferencia->sucursal_destino_id;
    $transferenciaLog->transferencia_id=$transferencia->id;
    $transferenciaLog->usuario=$_COOKIE["kiosco"];
    $nombreEstado=EstadoTransferencia::find($request->id_estado);
    $transferenciaLog->tipo_operacion="Cambio estatus a ".$nombreEstado->nombre;
    $transferenciaLog->created_at=date("Y-m-d H:i:s");
    $transferenciaLog->updated_at=date("Y-m-d H:i:s");
    $transferenciaLog->save();
    //Se modifica el stock solo si la transferencia es recibida
    if ($request->id_estado==3 && $estadoAnterior!=3)
         { 
    $productos=Transferencia::join("relacion_transferencias_productos as rtp","rtp.tranferencia_id","=","transferencias.id")
    ->join("productos as p","p.id","=","rtp.producto_id")
    ->where("transferencias.id","=",$request->id_transferencia)
    ->select("transferencias.sucursal_origen_id", "transferencias.sucursal_destino_id", "rtp.cantidad", "p.id", "p.nombre")
    ->get();
    if (count($productos)>0)
        { 
        foreach ($productos as $producto)
            { 
                //Actualizar stock en origen
                $stockorigen= Stock::where('productos_id',$producto->id)
                        ->where("sucursal_id",$producto->sucursal_origen_id)
                        ->first();
                //echo $stockorigen;
                //exit();
                $stockOrigenAnterior=0;
                $stockOrigenAnterior=$stockorigen->stock;
                $stockDestinoAnterior=0;
                $stockorigen->stock=$stockorigen->stock-$producto->cantidad;
                $stockorigen->save();
                $stock_logsorigen = new Stock_log();
                $stock_logsorigen->productos_id   = $producto->id;
                $stock_logsorigen->sucursal_id    = $producto->sucursal_origen_id;
                $stock_logsorigen->stock_anterior = $stockOrigenAnterior;
                $stock_logsorigen->stock_minimo = $stockorigen->stock_minimo;
                $stock_logsorigen->stock_minimo_anterior = $stockorigen->stock_minimo;
                $stock_logsorigen->stock   = $stockorigen->stock;
                $stock_logsorigen->usuario        = $_COOKIE["kiosco"];
                $stock_logsorigen->tipo_operacion = 'Envío transferencia';
                $stock_logsorigen->created_at=date("Y-m-d H:i:s");
                $stock_logsorigen->updated_at=date("Y-m-d H:i:s");
                $stock_logsorigen->save();
                 //Actualizar stock en destino
                $stockdestino=Stock::where('productos_id', '=', $producto->id)
                        ->where("sucursal_id","=",$producto->sucursal_destino_id)
                        ->first();
                    if($stockdestino!="" && $stockdestino!=null)
                        {
                            $stockDestinoAnterior=$stockdestino->stock;
                            $stockdestino->stock=$stockdestino->stock+$producto->cantidad;
                            $stockdestino->save();
                            $stock_logsDestino = new Stock_log();
                            $stock_logsDestino->productos_id   = $producto->id;
                            $stock_logsDestino->sucursal_id    = $producto->sucursal_destino_id;
                            $stock_logsDestino->stock_anterior = $stockDestinoAnterior;
                            $stock_logsDestino->stock_minimo = $stockdestino->stock_minimo;
                            $stock_logsDestino->stock_minimo_anterior = $stockdestino->stock_minimo;
                            $stock_logsDestino->stock   = $stockdestino->stock;
                            $stock_logsDestino->usuario        = $_COOKIE["kiosco"];
                            $stock_logsDestino->tipo_operacion = 'Recep. transferencia';
                            $stock_logsDestino->created_at=date("Y-m-d H:i:s");
                            $stock_logsDestino->updated_at=date("Y-m-d H:i:s");
                            $stock_logsDestino->save();
                        }
                    else
                        {
                            $stockdestino=new Stock();
                            $stockdestino->sucursal_id     = $producto->sucursal_destino_id;
                            $stockdestino->productos_id    = $producto->id;
                            $stockdestino->stock           = $producto->cantidad;
                            $stockdestino->stock_minimo    = 1;
                            $stockdestino->usuario         = $_COOKIE["kiosco"]; 
                            $stockdestino->created_at      = date("Y-m-d H:i:s");    
                            $stockdestino->updated_at      = date("Y-m-d H:i:s");           
                            $stockdestino->save();

                            $stock_logsDestino = new Stock_log();
                            $stock_logsDestino->productos_id   = $producto->id;
                            $stock_logsDestino->sucursal_id    = $producto->sucursal_destino_id;
                            $stock_logsDestino->stock_anterior = 0;
                            $stock_logsDestino->stock_minimo = 1;
                            $stock_logsDestino->stock_minimo_anterior = 0;
                            $stock_logsDestino->stock   = $producto->cantidad;
                            $stock_logsDestino->usuario        = $_COOKIE["kiosco"];
                            $stock_logsDestino->tipo_operacion = 'Recep. transferencia';
                            $stock_logsDestino->created_at=date("Y-m-d H:i:s");
                            $stock_logsDestino->updated_at=date("Y-m-d H:i:s");
                            $stock_logsDestino->save();
                        }
            }
    } 

}
$mensaje = 'Estatus cambiado con éxito';
return response()->json(array("proceso" => "OK"));
}

}
