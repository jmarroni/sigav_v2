<?php

declare(strict_types=1);

namespace App\Catalogo;

/**
 * Parseo puro del CSV de catálogo (sin DB ni framework).
 *
 * Aísla la lógica delicada: parseo de plata con formato es-AR ($ 1.234,56),
 * salteo de filas vacías y generación de códigos de barras únicos para
 * códigos faltantes o duplicados. El comando artisan se queda solo con la
 * escritura a la base.
 */
class CatalogoCsvParser
{
    /** Prefijo de los códigos de barras sintéticos. */
    private const PREFIJO_CODIGO_GENERADO = 'SC-';

    /**
     * @param string $csv Contenido completo del archivo CSV (con header).
     * @return array{productos: array<int, array<string, mixed>>, omitidas: int, codigosGenerados: int}
     */
    public static function parse(string $csv): array
    {
        $lineas = preg_split('/\r\n|\r|\n/', $csv) ?: [];

        $productos = [];
        $omitidas = 0;
        $codigosGenerados = 0;
        $codigosVistos = [];
        $contadorGenerado = 0;

        $esHeader = true;
        foreach ($lineas as $linea) {
            if ($esHeader) {
                $esHeader = false;
                continue; // primera línea = encabezado
            }
            if (trim($linea) === '') {
                continue; // línea en blanco del archivo (no cuenta como omitida)
            }

            $cols = str_getcsv($linea, ';');

            $nombre = isset($cols[1]) ? trim($cols[1]) : '';
            if ($nombre === '') {
                $omitidas++; // fila sin producto -> se omite
                continue;
            }

            $codigoCrudo = isset($cols[0]) ? trim($cols[0]) : '';
            $codigo = $codigoCrudo;
            $generado = false;
            if ($codigo === '' || isset($codigosVistos[$codigo])) {
                do {
                    $contadorGenerado++;
                    $codigo = self::PREFIJO_CODIGO_GENERADO . str_pad((string) $contadorGenerado, 4, '0', STR_PAD_LEFT);
                } while (isset($codigosVistos[$codigo]));
                $generado = true;
            }
            $codigosVistos[$codigo] = true;
            if ($generado) {
                $codigosGenerados++;
            }

            $productos[] = [
                'codigo_barras'    => $codigo,
                'nombre'           => $nombre,
                'stock'            => self::parseEntero($cols[2] ?? ''),
                'costo'            => self::parseMoney($cols[3] ?? ''),
                'precio_unidad'    => self::parseMoney($cols[4] ?? ''),
                'precio_mayorista' => self::parseMoney($cols[5] ?? ''),
                'proveedor'        => isset($cols[6]) ? trim($cols[6]) : '',
            ];
        }

        return [
            'productos'        => $productos,
            'omitidas'         => $omitidas,
            'codigosGenerados' => $codigosGenerados,
        ];
    }

    /**
     * Convierte un importe en formato es-AR ("$ 1.234,56") a float.
     * Quita símbolo, espacios y separador de miles; usa la coma como decimal.
     */
    public static function parseMoney(string $raw): float
    {
        $s = preg_replace('/[^0-9,.-]/', '', $raw);
        if ($s === '' || $s === null) {
            return 0.0;
        }
        $s = str_replace('.', '', $s); // separador de miles
        $s = str_replace(',', '.', $s); // separador decimal
        return is_numeric($s) ? (float) $s : 0.0;
    }

    /** Entero defensivo: no numérico -> 0. */
    private static function parseEntero(string $raw): int
    {
        $s = trim($raw);
        return is_numeric($s) ? (int) $s : 0;
    }
}
