<?php

use App\Models\AfipConfig;
use Illuminate\Database\Seeder;

class AfipConfigSeeder extends Seeder
{
    public function run()
    {
        // Crea los dos entornos si no existen. El flag `activo` (no mass-assignable)
        // solo se fija en la creación inicial: homo activo por defecto para testear
        // sin emitir real. En re-corridas NO se toca `activo`, para no pisar el
        // entorno que el operador haya activado desde la pantalla.
        $homo = AfipConfig::firstOrNew(['entorno' => 'homo']);
        if (! $homo->exists) {
            $homo->activo = true;
        }
        $homo->save();

        $prod = AfipConfig::firstOrNew(['entorno' => 'prod']);
        if (! $prod->exists) {
            $prod->activo = false;
        }
        $prod->save();
    }
}
