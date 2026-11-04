<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Usuario;

trait AutorizaRolAdmin
{
    /**
     * Usuario legacy logueado según las cookies, o null.
     *
     * `kiosco` viaja en texto plano, así que no alcanza sola: se exige que la
     * cookie `rol` sea sha1(SEMILLA.rol_id.SEMILLA) de ESE usuario, igual que
     * hace LegacyCookieAuth al crear la sesión. Si no, un usuario con sesión
     * ya abierta podría cambiar `kiosco` por el nombre de un admin.
     */
    protected function usuarioActual(): ?Usuario
    {
        $kiosco = $_COOKIE['kiosco'] ?? null;
        $rolCk = $_COOKIE['rol'] ?? null;
        $semilla = (string) config('app.legacy_semilla');
        if (! $kiosco || ! is_string($rolCk) || $rolCk === '' || $semilla === '') {
            return null;
        }

        $usuario = Usuario::where('usuario', $kiosco)->first();
        if (! $usuario || ! hash_equals(sha1($semilla.$usuario->rol_id.$semilla), $rolCk)) {
            return null;
        }

        return $usuario;
    }

    /** ¿El usuario logueado tiene rol_id >= $rolMinimo? */
    protected function tieneRol(int $rolMinimo = 2): bool
    {
        $u = $this->usuarioActual();

        return $u && (int) $u->rol_id >= $rolMinimo;
    }

    /** Aborta con 403 si el usuario logueado no tiene rol_id >= $rolMinimo. */
    protected function autorizar(int $rolMinimo = 2): void
    {
        if (! $this->tieneRol($rolMinimo)) {
            abort(403, 'No autorizado');
        }
    }
}
