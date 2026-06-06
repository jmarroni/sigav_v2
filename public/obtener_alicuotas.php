<?php

header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
require_once ("conection.php");
require 'vendor/autoload.php';
require_once __DIR__.'/afip_bridge.php';
use Spipu\Html2Pdf\Html2Pdf;


$comprobante 		= afip_valor('comprobante');
 $ptovta 			= afip_valor('ptovta');

$cuit 				= afip_valor('cuit');
$condicion_iva 		= afip_valor('condicion_iva');
$inicio_actividades = afip_valor('inicio_actividades');
$ingresos_brutos 	= afip_valor('ingresos_brutos');

// $documento 			= ($_GET["documento"] != "")?$_GET["documento"]:"";
// $nombre 			= ($_GET["nombre"] != "")?$_GET["nombre"]:"";
// $tipoDocumento 		= ($_GET["tipo-documento"] != "")?$_GET["tipo-documento"]:"";
// $tipo		 		= ($_GET["tipo"] != "")?$_GET["tipo"]:""; // forma de pago
// $iva 				= ($_GET["iva"] != "")?$_GET["iva"]:"";
// $direccion			= ($_GET["direccion"] != "")?$_GET["direccion"]:"";
// $descontar_stock    = 0;



$afip = afip_instance();
$res = $afip->ElectronicBilling->GetAliquotTypes();
print_r($res);

$server_status = $afip->ElectronicBilling->GetServerStatus();

echo 'Este es el estado del servidor:';
echo '<pre>';
print_r($server_status);
echo '</pre>';

?>
