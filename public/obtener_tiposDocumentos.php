<?php
//Procedimiento para guardar las alícuotas del Iva, obtenidas del webservice de AFIP a la base de datos local de sigav
header('Content-Type: application/json');
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
require_once ("conection.php");
require_once ("afip_config.php");
require 'vendor/autoload.php';
use Spipu\Html2Pdf\Html2Pdf;

// DATOS PARA EL COMPROBANTE (desde directorio seguro)
$comprobante 		= getAfipValue('comprobante');
$ptovta 			= getAfipValue('ptovta');
$cuit 				= getAfipValue('cuit');
$condicion_iva 		= getAfipValue('condicion_iva');
$inicio_actividades = getAfipValue('inicio_actividades');
$ingresos_brutos 	= getAfipValue('ingresos_brutos');

$afip = new Afip(getAfipConfig(floatval($cuit)));
//$res = json_encode($afip->ElectronicBilling->GetAliquotTypes());
$tiposdocumentos= $afip->ElectronicBilling->GetDocumentTypes();
echo $tipodocumentos;
// $alicuotas=json_decode($res,true);
// if ($alicuotas!=null && $alicuotas!="")
// $truncartabla="TRUNCATE TABLE `alicuotas_iva`";
// $conn->query($truncartabla);
// foreach($alicuotas as $alicuota){
//     $porcentaje=rtrim($alicuota["Desc"],"%");
//     $insertar="INSERT into alicuotas_iva (id_afip,porcentaje,fecha_desde,fecha_hasta)".
//     "values(".$alicuota["Id"].",".$porcentaje.",".$alicuota["FchDesde"].",".$alicuota["FchHasta"].")";
//     $conn->query($insertar) or die("Error: " . $insertar . "<br>" . $conn->error);
    //echo "exito";
 //}


?> 





