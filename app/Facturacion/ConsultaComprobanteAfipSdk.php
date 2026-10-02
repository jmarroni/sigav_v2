<?php

namespace App\Facturacion;

use App\Services\Afip\AfipService;

/** Implementación real sobre el SDK (entorno AFIP activo). */
class ConsultaComprobanteAfipSdk implements ConsultaComprobanteAfip
{
    /** @var AfipService */
    private $afip;

    public function __construct(AfipService $afip)
    {
        $this->afip = $afip;
    }

    public function comprobanteAsociado(int $ptovta, int $numero, int $tipo): ?array
    {
        try {
            $info = $this->afip->instancia()->ElectronicBilling->GetVoucherInfo($numero, $ptovta, $tipo);
        } catch (\Throwable $e) {
            throw new \RuntimeException($e->getMessage(), 0, $e);
        }
        if ($info === null) {
            throw new \RuntimeException("AFIP no tiene el comprobante $tipo $ptovta-$numero");
        }

        // El SDK devuelve objetos: CbtesAsoc->CbteAsoc es un objeto (uno) o un array (varios).
        $asoc = $info->CbtesAsoc->CbteAsoc ?? null;
        if (is_array($asoc)) {
            $asoc = $asoc[0] ?? null;
        }
        if (! $asoc || ! isset($asoc->Nro)) {
            return null;
        }

        return ['tipo' => (int) $asoc->Tipo, 'ptovta' => (int) $asoc->PtoVta, 'nro' => (int) $asoc->Nro];
    }
}
