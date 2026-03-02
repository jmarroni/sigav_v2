<?php

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
$res = $afip->ElectronicBilling->GetAliquotTypes();
print_r($res);

$server_status = $afip->ElectronicBilling->GetServerStatus();

echo 'Este es el estado del servidor:';
echo '<pre>';
print_r($server_status);
echo '</pre>';

?>
