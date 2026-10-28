<?php

namespace App\Exceptions;

use RuntimeException;

/** La venta no se puede descontar: nada se descuenta (todo o nada). */
class VentaRechazada extends RuntimeException
{
    /** @var string sin_stock | codigo_desconocido */
    public $motivo;

    /** @var array<int, array{codigo_barras:string, disponible:int}> */
    public $items;

    public function __construct(string $motivo, array $items)
    {
        parent::__construct($motivo);
        $this->motivo = $motivo;
        $this->items = $items;
    }
}
