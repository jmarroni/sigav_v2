<?php
/**
 * Devuelve TRUE si la facturacion debe operar contra produccion AFIP,
 * FALSE si debe operar contra homologacion (testing).
 *
 * Orden de resolucion:
 *   1. Variable de entorno AFIP_PRODUCTION (Apache SetEnv, fpm pool, etc.)
 *   2. Archivo Afip_res/production con contenido "1" o "true"
 *   3. Por defecto FALSE (homologacion) - failsafe para no facturar real por error
 */
function afip_is_production() {
    $env = getenv('AFIP_PRODUCTION');
    if ($env === false && isset($_SERVER['AFIP_PRODUCTION'])) {
        $env = $_SERVER['AFIP_PRODUCTION'];
    }
    if ($env !== false && $env !== '') {
        $val = strtolower(trim($env));
        return in_array($val, array('1', 'true', 'yes', 'on'), true);
    }

    $flagFile = __DIR__ . '/vendor/afipsdk/afip.php/src/Afip_res/production';
    if (is_readable($flagFile)) {
        $val = strtolower(trim(file_get_contents($flagFile)));
        if ($val !== '') {
            return in_array($val, array('1', 'true', 'yes', 'on'), true);
        }
    }

    return false;
}
