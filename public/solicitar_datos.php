<?php
// Verificar autenticación
if (!isset($_COOKIE["kiosco"])) {
    http_response_code(401);
    exit('No autorizado');
}

require_once("conection.php");
require_once("afip_config.php");

// Verificar rol administrador
if (getRol() < 2) {
    http_response_code(403);
    exit('Acceso denegado');
}

if (isset($_POST["solicitar"]) && (intval($_POST["solicitar"]) === 1 || intval($_POST["solicitar"]) === 0)) {
    setAfipValue('solicitar_datos', $_POST["solicitar"]);
    echo "OK";
}
?>