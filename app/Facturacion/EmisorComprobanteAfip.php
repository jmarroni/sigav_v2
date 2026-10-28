<?php

namespace App\Facturacion;

use App\Services\Afip\AfipService;

/**
 * Pide a AFIP el próximo comprobante (WSFE CreateNextVoucher), igual que facturar.php.
 * Devuelve el CAE, su vencimiento (AAAA-MM-DD) y el número asignado.
 */
class EmisorComprobanteAfip
{
    /** @var AfipService|null */
    private $afip;

    public function __construct(AfipService $afip)
    {
        $this->afip = $afip;
    }

    /**
     * @param array $data cuerpo de CreateNextVoucher (ver facturar.php)
     * @return array{CAE:string, CAEFchVto:string, voucher_number:int}
     * @throws \Exception si AFIP rechaza o no responde
     */
    public function emitir(array $data): array
    {
        $res = $this->afip->instancia()->ElectronicBilling->CreateNextVoucher($data);
        if (empty($res['CAE'])) {
            throw new \RuntimeException('AFIP no devolvió CAE.');
        }

        // AFIP devuelve la fecha como AAAA-MM-DD; la base la guarda igual.
        return [
            'CAE' => (string) $res['CAE'],
            'CAEFchVto' => (string) $res['CAEFchVto'],
            'voucher_number' => (int) $res['voucher_number'],
        ];
    }
}
