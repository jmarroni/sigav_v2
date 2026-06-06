<?php

return [

    // Carpeta base (FUERA del webroot) donde viven las credenciales por entorno:
    // storage/app/afip/homo/{cert,key} y storage/app/afip/prod/{cert,key}.
    // Override por env (p. ej. en tests) con AFIP_STORAGE_PATH.
    //
    // ⚠️ En el deploy híbrido NO setear AFIP_STORAGE_PATH a un valor no-default:
    // el puente legacy (public/afip_bridge.php) usa la ruta por defecto hardcodeada
    // (dirname(public)/storage/app/afip). Si se cambia acá sin actualizar el puente,
    // Laravel y la facturación legacy leerían/escribirían en carpetas distintas.
    'storage_path' => env('AFIP_STORAGE_PATH', storage_path('app/afip')),

    // Ruta al SDK AFIP vendado. Transicional: cuando el SDK pase a composer,
    // esta entrada se elimina. Overrideable por env (p. ej. en tests con un mock).
    'sdk_path' => env('AFIP_SDK_PATH', base_path('public/vendor/afipsdk/afip.php/src/Afip.php')),

];
