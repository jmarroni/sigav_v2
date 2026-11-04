<?php
// tests/Unit/LegacyAfipEmitirTest.php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/** SDK falso con el mismo contrato que afipsdk ElectronicBilling. */
class WsfeFalso
{
    public $ultimo = 10;
    public $crear;        // callable|array
    public $ultimoDespues = null;
    public $info = null;
    public $fallaConsulta = false;
    public $llamadas = [];

    public function GetLastVoucher($pto, $tipo)
    {
        $this->llamadas[] = 'ultimo';
        if ($this->fallaConsulta && count(array_keys($this->llamadas, 'ultimo')) > 1) {
            throw new \Exception('timeout');
        }
        if (count(array_keys($this->llamadas, 'ultimo')) > 1 && $this->ultimoDespues !== null) {
            return $this->ultimoDespues;
        }
        return $this->ultimo;
    }

    public function CreateVoucher($data)
    {
        $this->llamadas[] = 'crear:'.$data['CbteDesde'];
        if (is_callable($this->crear)) {
            return call_user_func($this->crear, $data);
        }
        return $this->crear;
    }

    public function GetVoucherInfo($numero, $pto, $tipo)
    {
        $this->llamadas[] = 'info:'.$numero;
        return $this->info;
    }
}

class LegacyAfipEmitirTest extends TestCase
{
    private $wsfe;
    private $afip;

    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__.'/../../public/legacy_comprobantes.php';
        $this->wsfe = new WsfeFalso();
        $this->afip = (object) ['ElectronicBilling' => $this->wsfe];
    }

    private function data(): array
    {
        return ['PtoVta' => 17, 'CbteTipo' => 13, 'ImpTotal' => 100];
    }

    /** @test */
    public function emite_el_siguiente_numero_y_devuelve_el_cae()
    {
        $this->wsfe->crear = ['CAE' => '123', 'CAEFchVto' => '2026-10-17'];

        $r = legacy_afip_emitir($this->afip, $this->data());

        $this->assertSame('emitida', $r['estado']);
        $this->assertSame(11, $r['res']['voucher_number']);
        $this->assertSame('123', $r['res']['CAE']);
        $this->assertSame(['ultimo', 'crear:11'], $this->wsfe->llamadas);
    }

    /** @test */
    public function un_rechazo_de_afip_sin_comprobante_nuevo_es_rechazada()
    {
        $this->wsfe->crear = function () { throw new \Exception('(10016) numero no corresponde'); };
        $this->wsfe->ultimoDespues = 10;

        $r = legacy_afip_emitir($this->afip, $this->data());

        $this->assertSame('rechazada', $r['estado']);
        $this->assertStringContainsString('10016', $r['mensaje']);
    }

    /** @test */
    public function si_la_llamada_fallo_pero_afip_autorizo_el_comprobante_se_recupera_como_emitida()
    {
        $this->wsfe->crear = function () { throw new \Exception('timeout leyendo la respuesta'); };
        $this->wsfe->ultimoDespues = 11;
        $this->wsfe->info = (object) ['CodAutorizacion' => '999', 'FchVto' => '20261017'];

        $r = legacy_afip_emitir($this->afip, $this->data());

        $this->assertSame('emitida', $r['estado']);
        $this->assertSame('999', $r['res']['CAE']);
        $this->assertSame('2026-10-17', $r['res']['CAEFchVto']);
        $this->assertSame(11, $r['res']['voucher_number']);
    }

    /** @test */
    public function si_no_se_puede_confirmar_con_afip_el_estado_es_desconocido()
    {
        $this->wsfe->crear = function () { throw new \Exception('timeout'); };
        $this->wsfe->fallaConsulta = true;

        $r = legacy_afip_emitir($this->afip, $this->data());

        $this->assertSame('desconocida', $r['estado']);
        $this->assertStringContainsString('NO reintente', $r['mensaje']);
    }

    /** @test */
    public function si_afip_no_responde_al_consultar_el_ultimo_no_se_emite_nada()
    {
        $this->wsfe->ultimo = null;
        $wsfe = new class extends WsfeFalso {
            public function GetLastVoucher($p, $t) { throw new \Exception('caido'); }
        };
        $r = legacy_afip_emitir((object) ['ElectronicBilling' => $wsfe], $this->data());

        $this->assertSame('rechazada', $r['estado']);
        $this->assertSame([], $wsfe->llamadas);
    }
}
