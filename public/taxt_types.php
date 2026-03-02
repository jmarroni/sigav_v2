<?php

if (!isset($_COOKIE["kiosco"])) {
    exit();
}
require_once ("conection.php");
require_once ("afip_config.php");
require 'vendor/autoload.php';

$cuit = getAfipValue('cuit');
$ptovta = getAfipValue('ptovta');
$comprobante = getAfipValue('comprobante');
try{
    $afip = new Afip(getAfipConfig(floatval($cuit)));
    $server_status = $afip->ElectronicBilling->GetTaxTypes();
    echo "<pre>";print_r($server_status);exit();
} catch (Exception $e) {
    echo "Error, los parametros indicados no son correctos, indique el siguiente error al webmaster para guiarlo<br />";
    print_r($e);
}
?>