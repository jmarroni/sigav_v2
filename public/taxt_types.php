<?php

if (!isset($_COOKIE["kiosco"])) {
    exit();
}
require_once ("conection.php");
require 'vendor/autoload.php';
require_once __DIR__.'/afip_bridge.php';
$cuit = afip_valor('cuit');
$ptovta = afip_valor('ptovta');
$comprobante = afip_valor('comprobante');
try{
    $afip = afip_instance();
    $server_status = $afip->ElectronicBilling->GetTaxTypes();
    echo "<pre>";print_r($server_status);exit();
} catch (Exception $e) {
    echo "Error, los parametros indicados no son correctos, indique el siguiente error al webmaster para guiarlo<br />";
    print_r($e);
}
?>