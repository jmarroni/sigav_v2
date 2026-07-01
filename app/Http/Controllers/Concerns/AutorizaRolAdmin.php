<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Usuario;

trait AutorizaRolAdmin
{
    /** Usuario legacy logueado según la cookie `kiosco`, o null. */
    protected function usuarioActual(): ?Usuario
    {
        $kiosco = $_COOKIE['kiosco'] ?? null;

        return $kiosco ? Usuario::where('usuario', $kiosco)->first() : null;
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
