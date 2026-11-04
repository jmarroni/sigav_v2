<?php

namespace App\Facturacion;

/**
 * Consulta de solo lectura a AFIP (FECompConsultar) para saber a qué
 * comprobante está asociado uno ya emitido. La tabla nota_de_credito no
 * guarda a qué factura anula cada nota; AFIP sí.
 */
interface ConsultaComprobanteAfip
{
    /**
     * @return array|null ['tipo' => int, 'ptovta' => int, 'nro' => int] del primer comprobante asociado, o null si no informa ninguno
     * @throws \RuntimeException si no se pudo consultar (conexión, credenciales, comprobante inexistente)
     */
    public function comprobanteAsociado(int $ptovta, int $numero, int $tipo): ?array;
}
