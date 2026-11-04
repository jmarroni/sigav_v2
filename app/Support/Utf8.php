<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Repara strings que no son UTF-8 válido antes de serializarlos a JSON.
 *
 * La base de Mercado mezcla convenciones: la conexión Laravel es latin1
 * (passthrough) y casi todas las celdas traen bytes UTF-8, pero unas ~300
 * (productos, categorias, proveedor) quedaron en latin1 real, p. ej.
 * "PE\xD1A". json_encode() las rechaza ("Malformed UTF-8 characters") y el
 * endpoint entero responde 500. Un string que no valida como UTF-8 se
 * interpreta como latin1 y se convierte; el resto pasa intacto.
 */
class Utf8
{
    /**
     * @param mixed $valor string, array, objeto, Collection o escalar
     * @return mixed misma forma, con los strings reparados (no muta el original)
     */
    public static function sanear($valor)
    {
        if (is_string($valor)) {
            return mb_check_encoding($valor, 'UTF-8') ? $valor : utf8_encode($valor);
        }
        if ($valor instanceof Collection) {
            return $valor->map([static::class, 'sanear']);
        }
        if (is_array($valor)) {
            return array_map([static::class, 'sanear'], $valor);
        }
        if (is_object($valor)) {
            $copia = clone $valor;
            foreach (get_object_vars($copia) as $k => $v) {
                $copia->$k = static::sanear($v);
            }
            return $copia;
        }

        return $valor;
    }
}
