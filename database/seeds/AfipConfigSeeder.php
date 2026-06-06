<?php

use App\Models\AfipConfig;
use Illuminate\Database\Seeder;

class AfipConfigSeeder extends Seeder
{
    public function run()
    {
        // Dos entornos; homo activo por defecto (para testear sin emitir real).
        // `activo` no es mass-assignable: se setea explícitamente.
        $homo = AfipConfig::firstOrNew(['entorno' => 'homo']);
        $homo->activo = true;
        $homo->save();

        $prod = AfipConfig::firstOrNew(['entorno' => 'prod']);
        // Solo fija activo=false si es un registro nuevo (no piso un prod ya activado).
        if (! $prod->exists) {
            $prod->activo = false;
        }
        $prod->save();
    }
}
