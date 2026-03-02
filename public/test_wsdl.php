<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>";
try {
    $wsdl = __DIR__ . '/vendor/afipsdk/afip.php/src/Afip_res/wsfe.wsdl';
    echo "Cargando WSDL: $wsdl\n";
    echo "Existe: " . (file_exists($wsdl) ? "SI" : "NO") . "\n\n";

    $client = new SoapClient($wsdl, [
        'soap_version' => SOAP_1_2,
        'trace' => 1,
        'exceptions' => 1,
        'cache_wsdl' => WSDL_CACHE_NONE
    ]);
    echo "SoapClient creado OK\n";
    echo "Funciones disponibles:\n";
    print_r($client->__getFunctions());
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString();
} catch (Error $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString();
}
echo "</pre>";
