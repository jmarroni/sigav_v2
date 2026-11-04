<?php

namespace App\Facturacion;

use App\Services\Afip\AfipService;

/** Implementación real sobre el SDK (entorno AFIP activo). */
class ConsultaComprobanteAfipSdk implements ConsultaComprobanteAfip
{
    /** @var AfipService */
    private $afip;

    /** @var \Afip|null */
    private $sdk;

    public function __construct(AfipService $afip)
    {
        $this->afip = $afip;
    }

    public function comprobanteAsociado(int $ptovta, int $numero, int $tipo): ?array
    {
        try {
            $info = $this->sdk()->ElectronicBilling->GetVoucherInfo($numero, $ptovta, $tipo);
        } catch (\Throwable $e) {
            throw new \RuntimeException($e->getMessage(), 0, $e);
        }
        if ($info === null) {
            throw new \RuntimeException("AFIP no tiene el comprobante $tipo $ptovta-$numero");
        }

        return self::primerAsociado($info);
    }

    /**
     * Una sola instancia del SDK por proceso: Afip::__get carga sus clases con
     * `include` (no `include_once`), así que una segunda instancia redeclara
     * ElectronicBilling y el proceso muere ("Cannot declare class").
     */
    private function sdk(): \Afip
    {
        if ($this->sdk === null) {
            $this->sdk = $this->afip->instancia();
        }

        return $this->sdk;
    }

    /**
     * El SDK devuelve objetos: CbtesAsoc->CbteAsoc es un objeto (uno) o un array (varios).
     *
     * @param object $info ResultGet de FECompConsultar
     */
    public static function primerAsociado($info): ?array
    {
        $asoc = $info->CbtesAsoc->CbteAsoc ?? null;
        if (is_array($asoc)) {
            $asoc = $asoc[0] ?? null;
        }
        if (! is_object($asoc) || ! isset($asoc->Nro)) {
            return null;
        }

        return ['tipo' => (int) $asoc->Tipo, 'ptovta' => (int) $asoc->PtoVta, 'nro' => (int) $asoc->Nro];
    }
}
