<?php

namespace App\Support;

/**
 * Traduce el tamaño de envío elegido en el producto a medidas y peso.
 */
class Envio
{
    /**
     * @param int|string|null $tamano número de config/envios.php (1..5) o null
     * @return array{peso_gramos:?int, alto_cm:?int, ancho_cm:?int, largo_cm:?int}
     */
    public static function datos($tamano): array
    {
        $preset = $tamano === null ? null : config('envios.tamanos.' . (int) $tamano);
        return [
            'peso_gramos' => $preset['peso_gramos'] ?? null,
            'alto_cm'     => $preset['alto_cm'] ?? null,
            'ancho_cm'    => $preset['ancho_cm'] ?? null,
            'largo_cm'    => $preset['largo_cm'] ?? null,
        ];
    }

    /** Valores válidos para el select del formulario y la validación. */
    public static function tamanosValidos(): array
    {
        return array_keys(config('envios.tamanos', []));
    }
}
