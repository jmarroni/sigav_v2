<?php

header('Content-Type: application/json');
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
// error_reporting(E_ALL);
require_once ("conection.php");
require 'vendor/autoload.php';
use Spipu\Html2Pdf\Html2Pdf;
$arrSucursal = array(); 

if (isset($_COOKIE["sucursal"])){
   $datos_sucursal = "SELECT * FROM sucursales where id = '".getSucursal($_COOKIE["sucursal"])."'";
   // echo $datos_sucursal;exit();
   $resultado_perfil = $conn->query($datos_sucursal) or die("Error: " . $sql . "<br>" . $conn->error);
   if ($resultado_perfil->num_rows > 0) {
      $arrSucursal = $resultado_perfil->fetch_assoc();
   }else{exit();}
} else exit();

 if (strlen($arrSucursal["direccion"]) > 25)
      $arrDireccion = substr($arrSucursal["direccion"],0,strpos($arrSucursal["direccion"]," ",20))."<br />".substr($arrSucursal["direccion"],strpos($arrSucursal["direccion"]," ",20)); 
   else $arrDireccion = $arrSucursal["direccion"];

$sql = "Select * FROM perfil";
$resultado_perfil = $conn->query($sql) or die("Error: " . $sql . "<br>" . $conn->error);
if ($resultado_perfil->num_rows > 0) {
   if ($row_perfil = $resultado_perfil->fetch_assoc()) {
      $logo = "http://".$_SERVER['HTTP_HOST'].$row_perfil["logo"];
        //$logo = (file_exists($logo))?$logo:"http://".$_SERVER['HTTP_HOST']."/assets/img/photos/no-image-featured-image.png";
      $nombre_fantasia = $row_perfil["nombre"];
      $datos_factura = $row_perfil;
   }
}else{
   $logo = "http://".$_SERVER['HTTP_HOST']."/assets/img/photos/no-image-featured-image.png";
   $nombre_fantasia = "SIGAV";
}
if (strpos($logo,"127.0.0.1") > 0) $logo = "http://".$_SERVER['HTTP_HOST']."/assets/img/photos/no-image-featured-image.png";

$nombre_producto="47 ESCUDOS DE PLATA DE LA PROVINCIA DE RIO NEGRO 20MM DE ALTO, 18,7MM DE ANCHO Y 0,6MM DE GROSOR. ESCUDOS DE PLATA 900";
$precio=271760;
$cantidad=1;
$tipo_comprobante="C";
$total=271760;
$nombre_factura = "/facturas/correccion.pdf";
$nombre="LEGISLATURA DE LA PROVINCIA DE RIO NEGRO";
$direccion="SAN MARTIN118";
$documento="30643169742";
$texto_iva="Cons. Final";
$texto_tipo="Transferencia";
$numComprobante="000020 - 000206
";
$facturanro = "FACTURA NRO.";
$cae="71508461978907";
$fechaptoventa="26-12-2021";
$fechaemision="16-12-2021";
$cuitempresa="30715251988
";

   $valido_o_no = "Comprobante Electronico";
   $html_datos_cliente = "<tr>
   <td colspan='3' style='border-bottom: 1px solid #000;'><b>Nombre y Apellido</b> $nombre</td>
   </tr><tr>
   <td colspan='3' style='border-bottom: 1px solid #000;'><b>Direcci&oacute;n</b> $direccion</td>
   </tr><tr>
   <td colspan='3' style='border-bottom: 1px solid #000;'><b>CUIT, CUIL &oacute; CDI </b> $documento</td>
   </tr>
   <tr>
   <td colspan='3' style='border-bottom: 1px solid #000;'><b>IVA </b> $texto_iva</td>
   </tr>
   <tr>
   <td colspan='3' style='border-bottom: 1px solid #000;'><b>Forma de pago </b> $texto_tipo</td>
   </tr>
   <tr>
   <td colspan='3' style='height:30px;'>&nbsp;</td>
   </tr>";

$html = utf8_encode("
      <style>
      h3{
         font-size:1em;
      }
      </style>
      <table>
      <tr>
      <td colspan='3' style='border: 2px solid #000;text-align:center;'>@@COMPROBANTE@@</td>
      </tr>
      <tr>
      <td style='padding-left:10px;border: 2px solid #000;height: 100px;font-size: 14px;width: 320px;text-align: left;'>
      <table>
      <tr>
      <td>
      <img = src='$logo' style='height:80px;width:120px;'/>
      </td>
      <td>  
      <br />{$arrSucursal["nombre"]}
      <br />$arrDireccion<br />
      {$arrSucursal["codigo_postal"]} - {$arrSucursal["provincia"]}<br />
      <b>$valido_o_no</b>
      </td>
      </tr>
      </table>
      </td>
      <td style='border: 2px solid #000;height: 100px;font-size: 60px;width: 70px;text-align: center;'>$tipo_comprobante</td>
      <td style='border: 2px solid #000;height: 100px;font-size: 14px;width: 280px;text-align: left;'>
       <b>@@FACTURANRO@@</b>&nbsp;".$numComprobante."<br />
      <b>CUIT</b>&nbsp;".$cuitempresa."<br />
      <b>Fecha de Emisi&oacute;n</b>&nbsp;".$fechaemision."<br />
      <b>Ing.&nbsp;Bruto</b>&nbsp;46161295<br />
      <b>IVA</b>&nbsp;IVA EXCENTO<br />

      </td>
      </tr>
      $html_datos_cliente
      <tr>
      <td style='border-bottom: 1px solid #000;word-wrap: break-word;width:230px'><b>Descripci&oacute;n</b></td>
      <td style='border-bottom: 1px solid #000;'><b>Cantidad</b></td>
      <td style='border-bottom: 1px solid #000;'><b>Precio</b></td>
      </tr>
      ");

      $html .= utf8_encode("<tr>
         <td style='border-bottom: 1px solid #000;word-wrap: break-word;width:230px;text-align:justify'><i>".$nombre_producto."</i></td>
         <td style='border-bottom: 1px solid #000;'>".$cantidad."</td>
         <td style='border-bottom: 1px solid #000;'>".number_format(floatval($precio),2,",",".")."</td>
         </tr>
         ");
   

   $html .= utf8_encode("<tr>
      <td></td>
      <td style='border-bottom: 1px solid #000;'>Total</td>
      <td style='border-bottom: 1px solid #000;'>".number_format($total,2,",",".")."</td>
      </tr>");
   if ($comprobante == 1){
      $html .= utf8_encode("
         <tr>
         <td></td>
         <td style='border-bottom: 1px solid #000;'>Importe Neto</td>
         <td style='border-bottom: 1px solid #000;'>".number_format($ImpNeto,2,",",".")."</td>
         </tr>
         <tr>
         <td></td>
         <td style='border-bottom: 1px solid #000;'>IVA (21%)</td>
         <td style='border-bottom: 1px solid #000;'>".number_format($impuestoIVA,2,",",".")."</td>
         </tr>");
   }
   $html .= utf8_encode("</table>");
      $html .= utf8_encode("<p style='text-align:right'><b>CAE Nro.:</b> ".$cae."<br />
         <b>Fecha de Vto. CAE: </b>".$fechaptoventa."<br /></p>");
   

   //if ($tipo == 4){ // Si es transferencia coloco la leyenda
   // $html .= utf8_encode("<p style='text-align:left'> *P&aacute;guese a la cuenta oficial Tesorer&iacute;a General Mercado Artesanal Provincial-Recaudadora. <br/><b>N° Cta Bco.</b> - 900001194 <br/><b>CBU</b> - 0340250600900001194004 <br/><b>CUIT</b> - Tesorer&iacute;a General Nro. 30-63945328-2 </p>");
   //}

   $html2pdf = new HTML2PDF('P', 'A4', 'pt', true, 'UTF-8');
   $html2pdf->setDefaultFont('Arial');
   $html = str_replace("@@FACTURANRO@@",$facturanro,$html);

   

   $html2pdf->writeHTML("<page>".str_replace("@@COMPROBANTE@@","ORIGINAL",$html)."<br><br><hr style='border-style: dotted;' /><br><br></page><page>".str_replace("@@COMPROBANTE@@","DUPLICADO",$html)."<br><br><hr style='border-style: dotted;' /><br><br></page>");

   
   $html2pdf->Output(dirname(__FILE__).$nombre_factura, "F");
   $devolucion["factura"] = $nombre_factura;
   echo json_encode($devolucion);


exit();
?>
