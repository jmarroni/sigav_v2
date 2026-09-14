<?php
/**
 * Helpers de configuración para el lado legacy (public/*.php).
 * Solo funciones puras sobre getenv(): sin conexiones, sin side effects,
 * para poder testearlas con PHPUnit sin Laravel.
 *
 * Bajo Apache/mod_php las variables del contenedor llegan a getenv() solo si
 * están listadas en `PassEnv` (deploy/afip-protect.conf).
 */

if (! function_exists('legacy_env')) {
    function legacy_env($name)
    {
        $v = getenv($name);
        if ($v === false || $v === '') {
            return null;
        }
        return $v;
    }
}

if (! function_exists('legacy_env_requerida')) {
    function legacy_env_requerida(array $names)
    {
        $out = [];
        $faltan = [];
        foreach ($names as $n) {
            $v = legacy_env($n);
            if ($v === null) {
                $faltan[] = $n;
            } else {
                $out[$n] = $v;
            }
        }
        if ($faltan) {
            throw new RuntimeException('Faltan variables de entorno: '.implode(', ', $faltan));
        }
        return $out;
    }
}

if (! function_exists('legacy_db_config')) {
    function legacy_db_config()
    {
        $e = legacy_env_requerida(['LEGACY_DB_HOST', 'LEGACY_DB_USER', 'LEGACY_DB_PASS', 'LEGACY_DB_NAME']);
        return [
            'host' => $e['LEGACY_DB_HOST'],
            'user' => $e['LEGACY_DB_USER'],
            'pass' => $e['LEGACY_DB_PASS'],
            'name' => $e['LEGACY_DB_NAME'],
        ];
    }
}

if (! function_exists('legacy_mail_config')) {
    function legacy_mail_config()
    {
        $e = legacy_env_requerida(['MAIL_HOST', 'MAIL_USERNAME', 'MAIL_PASSWORD']);
        $from = legacy_env('MAIL_FROM_ADDRESS');
        return [
            'host'         => $e['MAIL_HOST'],
            'port'         => legacy_env('MAIL_PORT') ?: '587',
            'username'     => $e['MAIL_USERNAME'],
            'password'     => $e['MAIL_PASSWORD'],
            'encryption'   => legacy_env('MAIL_ENCRYPTION') ?: 'tls',
            'from_address' => $from ?: $e['MAIL_USERNAME'],
            'from_name'    => legacy_env('MAIL_FROM_NAME') ?: 'SIGAV',
        ];
    }
}

if (! function_exists('legacy_imagen_default')) {
    function legacy_imagen_default($host)
    {
        return '//'.$host.'/assets/img/photos/no-image-featured-image.png';
    }
}

if (! function_exists('legacy_smartsupp_key')) {
    function legacy_smartsupp_key()
    {
        return legacy_env('SMARTSUPP_KEY');
    }
}
