<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sucursales;
use App\Models\Cliente;
use App\Models\PedidosOptica;
use App\Models\PedidosOpticaItems;
use Illuminate\Support\Facades\Storage;
use App\Models\PedidosOpticaRemito;
use Spipu\Html2Pdf\Html2Pdf;
use Image;


class PedidoController extends Controller
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
        $clientes=Cliente::all();
        return view("pedidos.pedidos",compact("sucursal","clientes","mensaje"));
    }


    public function save(Request $request)
    {
        $htmlProductos="";
        $numProductos=0;
        $accion=0;
        $arrayproductos=array();
        $sucursal=(Sucursales::getSucursal($_COOKIE["sucursal"]) !== null)?Sucursales::getSucursal($_COOKIE["sucursal"]):0;
        if ($request->id != "")
        {
            $pedido = PedidosOptica::find($request->id);
            $mensaje = "Modificación realizada exitosamente";     
        }
        else
        {
            $pedido = new PedidosOptica();
            $mensaje = "Pedido registrado exitosamente";              
        }

        $pedido->nota_pedido = $request->tipodocumento==1?1:0;
        $pedido->presupuesto = $request->tipodocumento==2?1:0;
        $pedido->paciente = $request->paciente;  
        $pedido->domicilio = $request->domicilio; 
        $pedido->telefono = $request->telefono; 
        $pedido->doctor= $request->doctor;   
        $pedido->obra_social = $request->obra_social;   
        $pedido->numero_asociado = $request->numero_asociado;   
        $pedido->fecha = date("Y-m-d H:i:s"); 
        $pedido->fecha_recepcion = $request->fecha_recepcion;    
        $pedido->pedido = $request->pedido;    
        $pedido->retira    = $request->retira;  
        $pedido->l_d_esf = $request->l_d_esf;    
        $pedido->l_d_cil = $request->l_d_cil;   
        $pedido->l_d_eje = $request->l_d_eje;   
        $pedido->l_d_dip= $request->l_d_dip;  
        $pedido->l_producto = $request->l_producto;   
        $pedido->l_armazon = $request->l_armazon; 
        $pedido->c_producto = $request->c_producto;   
        $pedido->c_armazon = $request->c_armazon;  
        $pedido->l_i_esf = $request->l_i_esf;   
        $pedido->l_i_cil=$request->l_i_cil;
        $pedido->l_i_eje=$request->l_i_eje;
        $pedido->l_i_dip=$request->l_i_dip;
        $pedido->c_d_esf=$request->c_d_esf;
        $pedido->c_d_cil=$request->c_d_cil;
        $pedido->c_d_eje=$request->c_d_eje;
        $pedido->c_d_dip=$request->c_d_dip;
        $pedido->c_i_esf=$request->c_i_esf;
        $pedido->c_i_cil=$request->c_i_cil;
        $pedido->c_i_eje=$request->c_i_eje;
        $pedido->c_i_dip=$request->c_i_dip;
        $pedido->observacion=$request->observacion;
        $pedido->usuario=$_COOKIE["kiosco"];
        $pedido->id_sucursal=$sucursal;
        $pedido->monto=$request->montoTotal;
        $pedido->estado=1;
        $pedido->save();


        $arrayproductos = explode("||", $request->detalleProductos);
        if (count($arrayproductos)>1)
        {
          foreach ($arrayproductos as $producto)
          {
            $numProductos=$numProductos+1;
            $datosProducto= explode(",", $producto);
            $item=new PedidosOpticaItems();
            $item->nombre=$datosProducto[1];
            $item->costo=$datosProducto[2];
            $item->id_pedido=$pedido->id;
            $item->save();
            $htmlProductos .= utf8_encode("<tr>
              <td style='width: 50px'><i>$numProductos</i></td>
              <td style='width: 200px'><i>$item->nombre</i></td>
              <td style='width: 100px'><i>$item->costo</i></td>
              </tr>
              ");
        }
    }
    $remito = new PedidosOpticaRemito();
    $remito->id_pedido = $pedido->id;
    $remito->fecha = date('Y-m-d-h-s');
    $remito->usuario = $_COOKIE["kiosco"];
    $remito->save();
    $numRemito=$remito->id;
    $logo= __DIR__.'/../../../public/assets/images/logobogaOptica.jpg';
    $logo = (file_exists($logo))?$logo:__DIR__.'/../../../public/assets/img/photos/no-image-featured-image.png';
    $paciente=$request->paciente!=""?$request->paciente:'';
    $tipodocumento=$request->tipodocumento==1?"Nota de pedido":"Presupuesto";
    $domicilio=$request->domicilio!=""?$request->domicilio:'';
    $telefono = $request->telefono!=""?$request->telefono:'';
    $doctor= $request->doctor!=""?$request->doctor:'';  
    $obra_social = $request->obra_social!=""?$request->obra_social:'';  
    $numero_asociado = $request->numero_asociado!=""?$request->numero_asociado:'';   
    $fecha = date("Y-m-d H:i:s"); 
    $fecha_recepcion = date("Y-m-d H:i:s");    
    $pedido = $request->pedido!=""?$request->pedido:'';     
    $retira = $request->retira!=""?$request->retira:'';  
    $l_d_esf = $request->l_d_esf!=""?$request->l_d_esf:"";    
    $l_d_cil = $request->l_d_cil!=""?$request->l_d_cil:"";   
    $l_d_eje = $request->l_d_eje!=""?$request->l_d_eje:"";   
    $l_d_dip= $request->l_d_dip!=""?$request->l_d_dip:"";  
    $l_producto = $request->l_producto!=""?$request->l_producto:"";   
    $l_armazon = $request->l_armazon!=""? $request->l_armazon:""; 
    $c_producto = $request->c_producto!=""?$request->c_producto:"";   
    $c_armazon = $request->c_armazon!=""?$request->c_armazon:"";  
    $l_i_esf = $request->l_i_esf!=""?$request->l_i_esf:"";   
    $l_i_cil=$request->l_i_cil!=""?$request->l_i_cil:"";
    $l_i_eje=$request->l_i_eje!=""?$request->l_i_eje:"";
    $l_i_dip=$request->l_i_dip!=""?$request->l_i_dip:"";
    $c_d_esf=$request->c_d_esf!=""?$request->c_d_esf:"";
    $c_d_cil=$request->c_d_cil!=""?$request->c_d_cil:"";
    $c_d_eje=$request->c_d_eje!=""?$request->c_d_eje:"";
    $c_d_dip=$request->c_d_dip!=""?$request->c_d_dip:"";
    $c_i_esf=$request->c_i_esf!=""?$request->c_i_esf:"";
    $c_i_cil=$request->c_i_cil!=""?$request->c_i_cil:"";
    $c_i_eje=$request->c_i_eje!=""?$request->c_i_eje:"";
    $c_i_dip=$request->c_i_dip!=""?$request->c_i_dip:"";
    $observacion=$request->observacion!=""?$request->observacion:"";
    $montoTotal=$request->montoTotal!=0?$request->montoTotal:0;






    $html = utf8_encode("
      <style>
      h3{
        font-size:1em;
    }
    </style>
    <table>
    <tr style='border: 2px solid #000;height: 100px;font-size: 14px;width: 250px;text-align: left;'>
    <td style='width: 400px;text-align: center;'>
    <img = src='$logo' style='height:150px;width:200px;'/>
    </td>
    <td style='font-size: 14px;width: 300px;text-align: left;'>
    <span style='font-weight: bold;'>$tipodocumento: </span><b></b>
    <span style='font-size:20px'>$numRemito</span>
    <br>
    <span style='font-weight: bold;'>Paciente: $paciente</span>
    <br>
    <span style='font-weight: bold;'>Domicilio: $domicilio</span>
    <br>
    <span style='font-weight: bold;'>Tel&eacute;fono: $telefono</span>
    <br>
    <span style='font-weight: bold;'>Obra social: $obra_social</span>
    <br>
    <span style='font-weight: bold;'>N&deg; de asociado: $numero_asociado</span>
    <br>
    <span style='font-weight: bold;'>Doctor: $doctor</span>
    <br>
    <span style='font-weight: bold;'>Fecha R/P: $fecha</span>
    <br>
    <span style='font-weight: bold;'>Pedido: $pedido</span>
    <br>
    <span style='font-weight: bold;'>Recibe: $retira</span>
    </td>
    </tr> 
    <tr>
    <td style='font-size: 8px;width: 300px;text-align: left;font-weight: bold;'>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Laprida 361 -(8500) Viedma - R&iacute;o Negro - Tel: (02920)-422084
    </td>
    </tr>
    </table>");

    $html.="<table border='1' style='margin:auto;' class='table table-hover table-vcenter'>
    <thead>

    <tr>
    <th style='width: 40px;'></th>
    <th style='width: 40px;'></th>
    <th style='width:100px;'>ESF.</th>
    <th style='width:100px;'>CIL.</th>
    <th style='width: 100px;'>EJE</th>
    <th style='width: 100px;'>D.I.P</th>
    <th style='width: 100px;'>PRODUCTO</th>
    <th style='width: 100px;'>ARMAZON</th>
    </tr>
    </thead> 
    <tbody> 
    <tr>
    <td rowspan='2'>L</td>
    <td>D</td>
    <td id='l_d_esf' name='l_d_esf'>$l_d_esf</td>
    <td id='l_d_cil' name='l_d_cil'>$l_d_cil</td>
    <td id='l_d_eje' name='l_d_eje'>$l_d_eje</td>
    <td id='l_d_dip' name='l_d_dip'>$l_d_dip</td>
    <td rowspan='2' id='l_producto' name='l_producto'>$l_producto</td>
    <td rowspan='2' id='l_armazon' name='l_armazon'>$l_armazon</td>
    </tr>
    <tr>
    <td>I</td>
    <td id='l_i_esf' name='l_i_esf'>$l_i_esf</td>
    <td id='l_i_cil' name='l_i_cil'>$l_i_cil</td>
    <td id='l_i_eje' name='l_i_eje'>$l_i_eje</td>
    <td id='l_i_dip' name='l_i_dip'>$l_i_dip</td>
    </tr>
    <tr>
    <td rowspan='2'>C</td>
    <td>D</td>
    <td id='c_d_esf' name='c_d_esf'>$c_d_esf</td>
    <td id='c_d_cil' name='c_d_cil'>$c_d_cil</td>
    <td id='c_d_eje' name='c_d_eje'>$c_d_eje</td>
    <td id='c_d_dip' name='c_d_dip'>$c_d_dip</td>
    <td rowspan='2' id='c_producto' name='c_producto'>$c_producto</td>
    <td rowspan='2' id='c_armazon' name='c_armazon'>$c_armazon</td>
    </tr>
    <tr>
    <td>I</td>
    <td id='c_i_esf' name='c_i_esf'>$c_i_esf</td>
    <td id='c_i_cil' name='c_i_cil'>$c_i_cil</td>
    <td id='c_i_eje' name='c_i_eje'>$c_i_eje</td>
    <td id='c_i_dip' name='c_i_dip'>$c_i_dip</td>
    </tr>

    </tbody> 
    </table>";




    $html.="<table>
    <thead>
    <tr>
    <th style='width: 350px;'>Items</th>
    <th style='width: 350px;'>Observaciones</th>
    </tr>
    </thead>
    <tr>
    <td style='border: 2px solid #000;height: 100px;font-size: 10px;width: 350px;text-align: left;'>
    <table><tr>
    <td style='border-bottom: 1px solid #000;width: 50px'><b>Nº</b></td>
    <td style='border-bottom: 1px solid #000;width: 200px'><b>Item</b></td>
    <td style='border-bottom: 1px solid #000;width: 100px'><b>Costo</b></td>
    </tr>

    ";
    $html .=$htmlProductos;
    $html.="<tr>
    <td style='width: 50px; border-bottom: 1px solid #000;font-weight: bold'><i>Total</i></td>
    <td style='width: 200px;border-bottom: 1px solid #000;font-weight: bold'><i></i></td>
    <td style='width: 100px;border-bottom: 1px solid #000;font-weight: bold'><i>$montoTotal</i></td>
    </tr>";
    $html.="</table></td>
    <td style='border: 2px solid #000;height: 100px;font-size: 10px;width: 350px;text-align: left;'>
    $observacion
    </td>
    </tr>
    </table>";


    $html2pdf = new HTML2PDF('P', 'A4', 'pt', true, 'UTF-8');
    $html2pdf->setDefaultFont('Arial');

    $html2pdf->writeHTML("<page>".str_replace("DOCUMENTO","ORIGINAL",$html)."<br><br><hr style='border-style: dotted;' /><br><br></page><page>".str_replace("DOCUMENTO","DUPLICADO",$html)."<br><br><hr style='border-style: dotted;' /><br><br></page>");

    $nombre_remito="/pedidos/comprobante".$numRemito.".pdf";
    $html2pdf->Output(__DIR__.'/../../../public'.$nombre_remito, "F");

    $remito->archivo=$nombre_remito;
    $remito->save();

    return response()->json(array("proceso" => "OK","comprobante" => $nombre_remito));
}

public function delete($id)
{
   if (!isset($_COOKIE["kiosco"])) {
    if (!isset($_GET["apiKey"]) || $_GET["apiKey"] != "a0a035dc5213448bb1a130c27f2494c5")
        header('Location: /');
    else{
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');
    }
}
Pedido::where('id', '=', $id)->delete();
return response()->json(array("proceso" => "OK"));
}

public function getPedido($id)
{ 
  return response()->json(Pedido::find($id));
}


}
