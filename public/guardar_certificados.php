<?php
if ($_POST["certificado"]){
    if (file_exists(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/cert")){
    file_put_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/cert",$_POST["certificado"]);
    };
}
if ($_POST["key"]){
    if (file_exists(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/key")){
        file_put_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/key",$_POST["key"]);
    };
}
if ($_POST["comprobante"]){
    if (file_exists(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/comprobante")){
        file_put_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/comprobante",$_POST["comprobante"]);
    };
}
if ($_POST["ptovta"]){
    if (file_exists(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/ptovta")){
        file_put_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/ptovta",$_POST["ptovta"]);
    };
}
if ($_POST["cuit"]){
    if (file_exists(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/cuit")){
        file_put_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/cuit",$_POST["cuit"]);
    };
}

if ($_POST["condicion_iva"]){
    if (file_exists(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/condicion_iva")){
        file_put_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/condicion_iva",$_POST["condicion_iva"]);
    };
}
if ($_POST["inicio_actividades"]){
    if (file_exists(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/inicio_actividades")){
        file_put_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/inicio_actividades",$_POST["inicio_actividades"]);
    };
}
if ($_POST["ingresos_brutos"]){
    if (file_exists(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/ingresos_brutos")){
        file_put_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/ingresos_brutos",$_POST["ingresos_brutos"]);
    };
}

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
    $server_status = $afip->ElectronicBilling->GetLastVoucher($ptovta,$comprobante);
    echo "Alta exitosa, ya tiene configurado el sistema de facturacion electronica";
} catch (Exception $e) {
    echo "Error, los parametros indicados no son correctos, indique el siguiente error al webmaster para guiarlo<br />";
    print_r($e);
}
?>