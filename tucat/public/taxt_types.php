<?php

if (!isset($_COOKIE["kiosco"])) {
    exit();
}
require_once ("conection.php");
require 'vendor/autoload.php';
$cuit = file_get_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/cuit");
$ptovta = file_get_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/ptovta");
$comprobante = file_get_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/comprobante");
try{
    $afip = new Afip(array('CUIT' => floatval($cuit), "production" => TRUE));
    $server_status = $afip->ElectronicBilling->GetTaxTypes();
    echo "<pre>";print_r($server_status);exit();
} catch (Exception $e) {
    echo "Error, los parametros indicados no son correctos, indique el siguiente error al webmaster para guiarlo<br />";
    print_r($e);
}
?>