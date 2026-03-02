<?php
/**
 * Configuración centralizada para AFIP SDK
 * Los certificados se almacenan fuera del directorio público por seguridad
 */

// Directorio seguro donde están los certificados (fuera de public/)
define('AFIP_CREDENTIALS_PATH', dirname(__DIR__) . '/storage/afip_credentials/');

// Directorio de recursos del SDK (WSDLs, etc.)
define('AFIP_RESOURCES_PATH', __DIR__ . '/vendor/afipsdk/afip.php/src/Afip_res/');

/**
 * Verifica si el sistema está en modo producción o homologación
 *
 * @return bool True si está en producción, False si está en homologación
 */
function isAfipProduction() {
    $modoFile = AFIP_CREDENTIALS_PATH . 'modo';
    if (file_exists($modoFile)) {
        $modo = trim(file_get_contents($modoFile));
        return ($modo === 'produccion');
    }
    // Por defecto, homologación para seguridad
    return false;
}

/**
 * Obtiene el modo actual de AFIP como texto
 *
 * @return string 'produccion' o 'homologacion'
 */
function getAfipMode() {
    return isAfipProduction() ? 'produccion' : 'homologacion';
}

/**
 * Establece el modo de AFIP
 *
 * @param string $modo 'produccion' o 'homologacion'
 * @return bool True si se guardó correctamente
 */
function setAfipMode($modo) {
    if (!in_array($modo, ['produccion', 'homologacion'])) {
        return false;
    }
    return setAfipValue('modo', $modo);
}

/**
 * Obtiene la configuración para instanciar el objeto Afip
 *
 * @param float|null $cuit CUIT a usar (si es null, lo lee del archivo)
 * @param bool|null $production Modo producción (si es null, lo lee del archivo de configuración)
 * @return array Opciones para new Afip()
 */
function getAfipConfig($cuit = null, $production = null) {
    // Si no se pasa CUIT, leerlo del archivo
    if ($cuit === null) {
        $cuitFile = AFIP_CREDENTIALS_PATH . 'cuit';
        if (file_exists($cuitFile)) {
            $cuit = floatval(trim(file_get_contents($cuitFile)));
        } else {
            throw new Exception("No se encontró el archivo de CUIT");
        }
    }

    // Si no se especifica modo, leerlo de la configuración
    if ($production === null) {
        $production = isAfipProduction();
    }

    // Usar credenciales según el modo
    $suffix = $production ? '_produccion' : '_homologacion';

    // SDK v1.0+: Leer el contenido de los certificados en lugar de pasar nombres de archivo
    $certFile = AFIP_CREDENTIALS_PATH . 'cert' . $suffix;
    $keyFile = AFIP_CREDENTIALS_PATH . 'key' . $suffix;

    $certContent = file_exists($certFile) ? file_get_contents($certFile) : '';
    $keyContent = file_exists($keyFile) ? file_get_contents($keyFile) : '';

    // SDK v1.0+: Obtener access_token (requerido para la nueva versión)
    $accessToken = getAfipValue('access_token');

    $config = array(
        'CUIT' => floatval($cuit),
        'production' => $production,
        'cert' => $certContent,
        'key' => $keyContent
    );

    // Solo agregar access_token si está configurado
    if (!empty($accessToken)) {
        $config['access_token'] = $accessToken;
    }

    return $config;
}

/**
 * Verifica si existen las credenciales para un modo específico
 *
 * @param bool $production True para producción, False para homologación
 * @return bool True si existen ambas credenciales (cert y key)
 */
function hasAfipCredentials($production = null) {
    if ($production === null) {
        $production = isAfipProduction();
    }
    $suffix = $production ? '_produccion' : '_homologacion';
    $certFile = AFIP_CREDENTIALS_PATH . 'cert' . $suffix;
    $keyFile = AFIP_CREDENTIALS_PATH . 'key' . $suffix;
    return file_exists($certFile) && file_exists($keyFile);
}

/**
 * Obtiene las credenciales para un modo específico
 *
 * @param bool $production True para producción, False para homologación
 * @return array ['cert' => contenido, 'key' => contenido]
 */
function getAfipCredentials($production) {
    $suffix = $production ? '_produccion' : '_homologacion';
    $certFile = AFIP_CREDENTIALS_PATH . 'cert' . $suffix;
    $keyFile = AFIP_CREDENTIALS_PATH . 'key' . $suffix;

    return array(
        'cert' => file_exists($certFile) ? file_get_contents($certFile) : '',
        'key' => file_exists($keyFile) ? file_get_contents($keyFile) : ''
    );
}

/**
 * Guarda las credenciales para un modo específico
 *
 * @param bool $production True para producción, False para homologación
 * @param string $cert Contenido del certificado
 * @param string $key Contenido de la clave privada
 * @return bool True si se guardaron correctamente
 */
function setAfipCredentials($production, $cert, $key) {
    $suffix = $production ? '_produccion' : '_homologacion';
    $certOk = setAfipValue('cert' . $suffix, $cert);
    $keyOk = setAfipValue('key' . $suffix, $key);
    return $certOk && $keyOk;
}

/**
 * Lee un valor de configuración de AFIP
 *
 * @param string $name Nombre del archivo (cuit, ptovta, comprobante, etc.)
 * @return string|null Contenido del archivo o null si no existe
 */
function getAfipValue($name) {
    $file = AFIP_CREDENTIALS_PATH . $name;
    if (file_exists($file)) {
        return trim(file_get_contents($file));
    }
    return null;
}

/**
 * Guarda un valor de configuración de AFIP
 *
 * @param string $name Nombre del archivo
 * @param string $value Valor a guardar
 * @return bool True si se guardó correctamente
 */
function setAfipValue($name, $value) {
    $file = AFIP_CREDENTIALS_PATH . $name;
    return file_put_contents($file, $value) !== false;
}
