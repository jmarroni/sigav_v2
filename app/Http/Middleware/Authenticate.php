<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            // Esta app usa el login legacy (public/login.php); no existe una
            // ruta Laravel llamada 'login', así que redirigimos directo al script.
            return url('/login.php');
        }
    }
}
