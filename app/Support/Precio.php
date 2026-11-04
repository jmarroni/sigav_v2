<?php

namespace App\Support;

/**
 * Normaliza precios guardados como texto (productos.precio_unidad es varchar).
 *
 * En la base conviven "15000", "60.000" (punto como miles), "1.200,000" (miles con
 * coma decimal), "3292.8" y basura como una fecha. Salida: siempre "60000.00"
 * (dos decimales, sin separador de miles), o null si no se puede interpretar.
 *
 * Regla ambigua: "1.500" se lee como 1500 (miles), no como 1,5. En el catálogo de
 * Mercado Artesanal los precios con punto son casi siempre miles.
 */
class Precio
{
    public static function normalizar($valor): ?string
    {
        if ($valor === null) {
            return null;
        }
        $texto = str_replace([' ', '$', "\u{00A0}"], '', trim((string) $valor));
        if ($texto === '' || preg_match('/^\d{4}-\d{2}-\d{2}/', $texto)) {
            return null;
        }

        if (strpos($texto, ',') !== false) {
            // Formato argentino: punto = miles, coma = decimales.
            if (!preg_match('/^\d{1,3}(\.\d{3})*,\d+$|^\d+,\d+$/', $texto)) {
                return null;
            }
            $numero = str_replace(['.', ','], ['', '.'], $texto);
        } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $texto)) {
            // Solo puntos seguidos de tres dígitos: separador de miles.
            $numero = str_replace('.', '', $texto);
        } elseif (preg_match('/^\d+(\.\d+)?$/', $texto)) {
            $numero = $texto;
        } else {
            return null;
        }

        return number_format(round((float) $numero, 2), 2, '.', '');
    }

    /**
     * Precio que ve y cobra el e-commerce: precio_unidad + $pct %, redondeado al peso
     * entero más cercano (la mitad sube). Ej. con 10%: 999 → 1099 (1098.90), 5 → 6 (5.50).
     * Cuenta en centavos enteros, sin floats. Null si el precio no sirve o da 0.
     */
    public static function online($valor, int $pct): ?string
    {
        if ($pct < 0 || $pct > 100) {
            throw new \InvalidArgumentException("ECOMMERCE_RECARGO_PCT fuera de rango (0 a 100): $pct");
        }
        $normalizado = self::normalizar($valor);
        if ($normalizado === null) {
            return null;
        }
        [$pesos, $decimales] = explode('.', $normalizado);
        // Tope de sanidad (12 dígitos de pesos): evita desbordar int y publicar basura
        // como un código de barras cargado en el precio.
        if (strlen(ltrim($pesos, '0')) > 12) {
            return null;
        }
        $centavos = (int) $pesos * 100 + (int) $decimales;

        // centavos × (100 + pct) está en "diezmilésimos de peso"; +5000 redondea a la mitad.
        $resultado = intdiv($centavos * (100 + $pct) + 5000, 10000);

        return $resultado > 0 ? $resultado . '.00' : null;
    }

    /** Recargo configurado (config ecommerce.recargo_online_pct). Solo enteros 0..100. */
    public static function recargoOnline(): int
    {
        $valor = config('ecommerce.recargo_online_pct');
        if (is_int($valor)) {
            $pct = $valor;
        } elseif (is_string($valor) && preg_match('/^\d{1,3}$/', trim($valor))) {
            $pct = (int) trim($valor);
        } else {
            throw new \InvalidArgumentException('ECOMMERCE_RECARGO_PCT debe ser un entero de 0 a 100.');
        }
        if ($pct > 100) {
            throw new \InvalidArgumentException("ECOMMERCE_RECARGO_PCT fuera de rango (0 a 100): $pct");
        }

        return $pct;
    }

    /** precio_unidad con el recargo configurado. */
    public static function onlineConfigurado($valor): ?string
    {
        return self::online($valor, self::recargoOnline());
    }
}
