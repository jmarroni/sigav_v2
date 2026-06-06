<?php
/**
 * Puente transicional entre la facturación legacy (.php) y la configuración
 * AFIP administrada por Laravel.
 *
 * Lee el entorno activo y los datos escalares desde la MISMA tabla `afip_config`
 * que administra Laravel (vía el mysqli de conection.php) y las credenciales
 * cert/key desde storage/app/afip/{entorno}/ (fuera del webroot).
 *
 * NO contiene credenciales. Cuando la facturación migre a Laravel, se elimina.
 */

require_once __DIR__.'/vendor/afipsdk/afip.php/src/Afip.php';
require_once __DIR__.'/conection.php'; // provee $conn (mysqli)

/** Fila del entorno activo (cacheada por request). */
function afip_config_row()
{
    static $row = null;
    static $loaded = false;
    if ($loaded) {
        return $row;
    }
    global $conn;
    $res = mysqli_query($conn, "SELECT * FROM afip_config WHERE activo = 1 LIMIT 1");
    $row = ($res && mysqli_num_rows($res) > 0) ? mysqli_fetch_assoc($res) : null;
    $loaded = true;
    return $row;
}

/** 'homo' | 'prod' (default 'prod' si no hay config, para no romper comportamiento previo). */
function afip_modo()
{
    $r = afip_config_row();
    return $r ? $r['entorno'] : 'prod';
}

/** Valor escalar del entorno activo (cuit, ptovta, comprobante, ...). */
function afip_valor($clave)
{
    $r = afip_config_row();
    return $r && isset($r[$clave]) ? $r[$clave] : null;
}

/**
 * Actualiza un valor escalar del entorno ACTIVO en la tabla afip_config.
 * `$clave` se valida contra una allowlist (no se puede parametrizar el nombre
 * de columna en SQL). `$valor` va por prepared statement.
 */
function afip_set_valor($clave, $valor)
{
    $permitidas = ['cuit', 'ptovta', 'comprobante', 'condicion_iva', 'inicio_actividades', 'ingresos_brutos', 'emitir', 'solicitar_datos'];
    if (! in_array($clave, $permitidas, true)) {
        throw new Exception('Clave AFIP inválida: '.$clave);
    }
    global $conn;
    $stmt = mysqli_prepare($conn, "UPDATE afip_config SET `$clave` = ? WHERE activo = 1");
    mysqli_stmt_bind_param($stmt, 's', $valor);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/** Instancia del SDK configurada para el entorno activo. */
function afip_instance()
{
    $r = afip_config_row();
    if (! $r) {
        throw new Exception('No hay entorno AFIP activo configurado');
    }
    $dir = dirname(__DIR__).'/storage/app/afip/'.$r['entorno'].'/';

    return new Afip(array(
        'CUIT'       => floatval($r['cuit']),
        'production' => $r['entorno'] === 'prod',
        'res_folder' => $dir,
        'ta_folder'  => $dir,
    ));
}
