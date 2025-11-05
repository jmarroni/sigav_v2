<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*'],

    // SEGURIDAD: Métodos HTTP permitidos
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    // SEGURIDAD: Origenes permitidos - CAMBIAR '*' por dominios específicos en producción
    // Ejemplo: ['https://tudominio.com', 'https://app.tudominio.com']
    'allowed_origins' => [env('FRONTEND_URL', '*')],

    'allowed_origins_patterns' => [],

    // SEGURIDAD: Headers permitidos
    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept'],

    'exposed_headers' => false,

    'max_age' => 0,

    'supports_credentials' => true,

];
