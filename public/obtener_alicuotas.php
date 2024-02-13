<?php

header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
require_once ("conection.php");
require 'vendor/autoload.php';
use Spipu\Html2Pdf\Html2Pdf;


$comprobante 		= file_get_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/comprobante");
 $ptovta 			= file_get_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/ptovta");

$cuit 				= file_get_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/cuit");
$condicion_iva 		= file_get_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/condicion_iva");
$inicio_actividades = file_get_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/inicio_actividades");
$ingresos_brutos 	= file_get_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/ingresos_brutos");

// $documento 			= ($_GET["documento"] != "")?$_GET["documento"]:"";
// $nombre 			= ($_GET["nombre"] != "")?$_GET["nombre"]:"";
// $tipoDocumento 		= ($_GET["tipo-documento"] != "")?$_GET["tipo-documento"]:"";
// $tipo		 		= ($_GET["tipo"] != "")?$_GET["tipo"]:""; // forma de pago
// $iva 				= ($_GET["iva"] != "")?$_GET["iva"]:"";
// $direccion			= ($_GET["direccion"] != "")?$_GET["direccion"]:"";
// $descontar_stock    = 0;



$afip = new Afip(array('CUIT' => floatval($cuit), "production" => TRUE));
$res = $afip->ElectronicBilling->GetAliquotTypes();
print_r($res);

$server_status = $afip->ElectronicBilling->GetServerStatus();

echo 'Este es el estado del servidor:';
echo '<pre>';
print_r($server_status);
echo '</pre>';

?>
