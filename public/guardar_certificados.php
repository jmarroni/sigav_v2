<?php
require_once __DIR__ . '/afip_env.php';

$AFIP_RES = __DIR__ . '/vendor/afipsdk/afip.php/src/Afip_res/';

if (!is_dir($AFIP_RES)) {
    @mkdir($AFIP_RES, 0750, true);
}

$campos = array(
    'certificado'        => 'cert',
    'key'                => 'key',
    'comprobante'        => 'comprobante',
    'ptovta'             => 'ptovta',
    'cuit'               => 'cuit',
    'condicion_iva'      => 'condicion_iva',
    'inicio_actividades' => 'inicio_actividades',
    'ingresos_brutos'    => 'ingresos_brutos',
);

foreach ($campos as $postKey => $fileName) {
    if (isset($_POST[$postKey]) && $_POST[$postKey] !== '') {
        file_put_contents($AFIP_RES . $fileName, $_POST[$postKey]);
        @chmod($AFIP_RES . $fileName, 0640);
    }
}

if (!isset($_COOKIE["kiosco"])) {
    exit();
}
require_once ("conection.php");
require 'vendor/autoload.php';
$cuit = file_get_contents($AFIP_RES . 'cuit');
$ptovta = file_get_contents($AFIP_RES . 'ptovta');
$comprobante = file_get_contents($AFIP_RES . 'comprobante');
try{
    $afip = new Afip(array('CUIT' => floatval($cuit), "production" => afip_is_production()));
    $server_status = $afip->ElectronicBilling->GetLastVoucher($ptovta,$comprobante);
    echo "Alta exitosa, ya tiene configurado el sistema de facturacion electronica";
} catch (Exception $e) {
    echo "Error, los parametros indicados no son correctos, indique el siguiente error al webmaster para guiarlo<br />";
    print_r($e);
}
?>
