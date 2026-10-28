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
}
