<?php

namespace App\Catalogo;

/**
 * Clasifica un producto en una categoria por TIPO DE PRENDA a partir de su
 * nombre. Reemplaza el criterio viejo (una categoria por proveedor), que era
 * el origen del bug: los productos quedaban categorizados por proveedor.
 *
 * Devuelve el par [nombre, abreviatura] de la categoria destino. La primera
 * regla que matchea gana; si ninguna matchea, cae en "Accesorios".
 *
 * El match se hace sobre el nombre normalizado a ASCII + mayusculas, asi es
 * robusto ante acentos, la ñ y el mojibake de la tabla latin1.
 */
final class CategoriaClasificador
{
    /** @var array<int, array{0:string,1:string,2:string}> patron, nombre, abreviatura */
    private const REGLAS = [
        // Accesorios que colisionarian con reglas de prenda: mantas de emergencia
        // y chalecos de HIDRATACION de running (AONIJIE / con capacidad "5L").
        // Van primero para ganarles. El chaleco-prenda (ej "Chaleco Kosten")
        // NO matchea aca y cae en Camperas mas abajo.
        ['/\bMANTA|AONIJIE.*CHALECO|CHALECO.*\dL/',         'Accesorios', 'AC'],
        ['/\bMEDIA|\bSOX\b|\bSOCK/',                        'Medias',     'ME'],
        ['/CAMPERA|ROMPEVIENTO|CORTAVIENTO|CHALECO/',       'Camperas',   'CA'],
        ['/\bBUZO/',                                        'Buzos',      'BU'],
        ['/REMERA|MUSCULOSA|\bTOP\b|TERMICA|MANGUITA|CAMISETA/', 'Remeras', 'RE'],
        ['/CALZA|\bBIKER/',                                 'Calzas',     'CL'],
        ['/SHORT|BERMUDA|BABUCHA/',                         'Shorts',     'SH'],
        ['/PANTALON|JOGGER|\bJEAN/',                        'Pantalones', 'PA'],
    ];

    private const FALLBACK = ['Accesorios', 'AC'];

    /**
     * @return array{0:string,1:string} [nombre, abreviatura]
     */
    public static function clasificar(string $nombre): array
    {
        $u = self::normalizar($nombre);
        foreach (self::REGLAS as [$patron, $cat, $abrev]) {
            if (preg_match($patron, $u)) {
                return [$cat, $abrev];
            }
        }
        return self::FALLBACK;
    }

    /**
     * Lista de todas las categorias posibles (para asegurarlas en la DB).
     *
     * @return array<int, array{0:string,1:string}>
     */
    public static function categorias(): array
    {
        $out = [];
        foreach (self::REGLAS as [, $cat, $abrev]) {
            $out[] = [$cat, $abrev];
        }
        $out[] = self::FALLBACK;
        return $out;
    }

    /** Normaliza a ASCII (sin acentos/ñ, sin bytes mojibake) y mayusculas. */
    private static function normalizar(string $s): string
    {
        $map = [
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n',
            'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N',
            'à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u','â'=>'a','ê'=>'e',
        ];
        $s = strtr($s, $map);
        $s = preg_replace('/[^\x20-\x7E]/', '', $s); // limpia mojibake / multibyte
        return strtoupper($s);
    }
}
