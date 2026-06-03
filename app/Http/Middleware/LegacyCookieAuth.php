<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Models\Usuario;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Puente entre el login legacy (public/login.php, basado en cookies) y el
 * guard de sesión `web` de Laravel.
 *
 * El login legacy valida contra la tabla `usuarios` (clave sha1) y setea las
 * cookies `kiosco` (= usuarios.usuario, en texto plano), `rol` y `sucursal`
 * (estas dos como sha1(SEMILLA.valor.SEMILLA)). Los controllers Blade están
 * protegidos con middleware('auth'), pero nada iniciaba la sesión de Laravel.
 *
 * Este middleware, si detecta una cookie legacy válida y todavía no hay sesión
 * Laravel activa, mapea el usuario legacy a un registro de `users` (creándolo
 * si hace falta) e inicia la sesión vía Auth::loginUsingId. La confianza se ata
 * a la SEMILLA secreta: solo se acepta el `kiosco` si los hashes `rol` y
 * `sucursal` coinciden con los del usuario legacy.
 */
class LegacyCookieAuth
{
    public function handle($request, Closure $next)
    {
        // Si ya hay sesión Laravel, no hay nada que puentear.
        if (Auth::check()) {
            return $next($request);
        }

        // Las cookies legacy las setea PHP plano (sin cifrado de Laravel),
        // por eso se leen desde $_COOKIE, igual que el resto del código legacy.
        $kiosco   = $_COOKIE['kiosco']   ?? null;
        $rolCk    = $_COOKIE['rol']      ?? null;
        $sucCk    = $_COOKIE['sucursal'] ?? null;

        if (empty($kiosco) || empty($rolCk) || empty($sucCk)) {
            return $next($request);
        }

        $legacy = Usuario::where('usuario', $kiosco)->first();
        if (! $legacy) {
            return $next($request);
        }

        // Verificación de integridad: las cookies rol/sucursal deben coincidir
        // con los valores del usuario legacy hasheados con la SEMILLA secreta.
        $semilla = (string) config('app.legacy_semilla');
        if ($semilla === '') {
            return $next($request);
        }

        $rolEsperado = sha1($semilla . $legacy->rol_id . $semilla);
        $sucEsperado = sha1($semilla . $legacy->sucursal_id . $semilla);

        if (! hash_equals($rolEsperado, $rolCk) || ! hash_equals($sucEsperado, $sucCk)) {
            return $next($request);
        }

        // Mapeo legacy -> users (find-or-create). El usuario de Laravel se usa
        // solo para satisfacer el guard de sesión; la identidad de negocio sigue
        // saliendo de las cookies legacy en los controllers.
        $appUser = User::firstOrCreate(
            ['email' => $legacy->usuario . '@legacy.local'],
            [
                'name'     => trim($legacy->nombre . ' ' . $legacy->apellido) ?: $legacy->usuario,
                'password' => bcrypt(Str::random(40)),
            ]
        );

        Auth::loginUsingId($appUser->id);

        return $next($request);
    }
}
