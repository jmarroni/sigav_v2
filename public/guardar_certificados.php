<?php
// SEGURIDAD: Verificar autenticación ANTES de cualquier operación
if (!isset($_COOKIE["kiosco"])) {
    http_response_code(401);
    exit('No autorizado');
}

require_once("conection.php");
require_once("afip_config.php");

// Verificar que el usuario tenga rol de administrador (rol 5)
if (getRol() != 5) {
    http_response_code(403);
    exit('Acceso denegado');
}

// Guardar modo AFIP (produccion/homologacion)
if (isset($_POST["modo_afip"])) {
    setAfipMode($_POST["modo_afip"]);
    echo "Modo cambiado correctamente";
    exit();
}

// Guardar Access Token (SDK v1.0+)
if (isset($_POST["guardar_access_token"])) {
    $access_token = isset($_POST["access_token"]) ? trim($_POST["access_token"]) : '';
    if ($access_token) {
        setAfipValue('access_token', $access_token);
        echo "Access Token guardado correctamente";
    } else {
        echo "Error: Debe ingresar un Access Token valido";
    }
    exit();
}

// Guardar credenciales de HOMOLOGACION
if (isset($_POST["guardar_homologacion"])) {
    $cert = isset($_POST["cert_homologacion"]) ? $_POST["cert_homologacion"] : '';
    $key = isset($_POST["key_homologacion"]) ? $_POST["key_homologacion"] : '';
    if ($cert && $key) {
        setAfipCredentials(false, $cert, $key);
        echo "Credenciales de homologacion guardadas correctamente";
    } else {
        echo "Error: Debe ingresar certificado y clave";
    }
    exit();
}

// Guardar credenciales de PRODUCCION
if (isset($_POST["guardar_produccion"])) {
    $cert = isset($_POST["cert_produccion"]) ? $_POST["cert_produccion"] : '';
    $key = isset($_POST["key_produccion"]) ? $_POST["key_produccion"] : '';
    if ($cert && $key) {
        setAfipCredentials(true, $cert, $key);
        echo "Credenciales de produccion guardadas correctamente";
    } else {
        echo "Error: Debe ingresar certificado y clave";
    }
    exit();
}

// Probar conexion con modo especifico
if (isset($_POST["probar_conexion"])) {
    require 'vendor/autoload.php';
    $modo = $_POST["probar_conexion"]; // 'homologacion' o 'produccion'
    $production = ($modo === 'produccion');
    $cuit = getAfipValue('cuit');
    $ptovta = getAfipValue('ptovta');
    $comprobante = getAfipValue('comprobante');

    if (!hasAfipCredentials($production)) {
        echo "Error: No hay credenciales configuradas para " . $modo;
        exit();
    }

    try {
        $afip = new Afip(getAfipConfig(floatval($cuit), $production));
        $last_voucher = $afip->ElectronicBilling->GetLastVoucher($ptovta, $comprobante);
        echo "Conexion exitosa con AFIP " . strtoupper($modo) . ". Ultimo comprobante: " . $last_voucher;
    } catch (Exception $e) {
        echo "Error de conexion: " . $e->getMessage();
    }
    exit();
}

// Guardar configuracion general
if (isset($_POST["guardar_config"])) {
    if (isset($_POST["comprobante"]) && $_POST["comprobante"]){
        setAfipValue('comprobante', $_POST["comprobante"]);
    }
    if (isset($_POST["ptovta"]) && $_POST["ptovta"]){
        setAfipValue('ptovta', $_POST["ptovta"]);
    }
    if (isset($_POST["cuit"]) && $_POST["cuit"]){
        setAfipValue('cuit', $_POST["cuit"]);
    }
    if (isset($_POST["condicion_iva"]) && $_POST["condicion_iva"]){
        setAfipValue('condicion_iva', $_POST["condicion_iva"]);
    }
    if (isset($_POST["inicio_actividades"]) && $_POST["inicio_actividades"]){
        setAfipValue('inicio_actividades', $_POST["inicio_actividades"]);
    }
    if (isset($_POST["ingresos_brutos"]) && $_POST["ingresos_brutos"]){
        setAfipValue('ingresos_brutos', $_POST["ingresos_brutos"]);
    }
    echo "Configuracion general guardada correctamente";
    exit();
}

// Compatibilidad con formato antiguo (si se envian credenciales sin especificar modo)
if (isset($_POST["certificado"]) && $_POST["certificado"]){
    setAfipValue('cert', $_POST["certificado"]);
}
if (isset($_POST["key"]) && $_POST["key"]){
    setAfipValue('key', $_POST["key"]);
}
if (isset($_POST["comprobante"]) && $_POST["comprobante"]){
    setAfipValue('comprobante', $_POST["comprobante"]);
}
if (isset($_POST["ptovta"]) && $_POST["ptovta"]){
    setAfipValue('ptovta', $_POST["ptovta"]);
}
if (isset($_POST["cuit"]) && $_POST["cuit"]){
    setAfipValue('cuit', $_POST["cuit"]);
}
if (isset($_POST["condicion_iva"]) && $_POST["condicion_iva"]){
    setAfipValue('condicion_iva', $_POST["condicion_iva"]);
}
if (isset($_POST["inicio_actividades"]) && $_POST["inicio_actividades"]){
    setAfipValue('inicio_actividades', $_POST["inicio_actividades"]);
}
if (isset($_POST["ingresos_brutos"]) && $_POST["ingresos_brutos"]){
    setAfipValue('ingresos_brutos', $_POST["ingresos_brutos"]);
}

require 'vendor/autoload.php';
$cuit = getAfipValue('cuit');
$ptovta = getAfipValue('ptovta');
$comprobante = getAfipValue('comprobante');
try{
    $afip = new Afip(getAfipConfig(floatval($cuit)));
    $server_status = $afip->ElectronicBilling->GetLastVoucher($ptovta, $comprobante);
    echo "Alta exitosa, ya tiene configurado el sistema de facturacion electronica";
} catch (Exception $e) {
    echo "Error, los parametros indicados no son correctos, indique el siguiente error al webmaster para guiarlo<br />";
    print_r($e);
}
?>