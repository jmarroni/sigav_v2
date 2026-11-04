<?php
// tests/Unit/ConsultaComprobanteAfipSdkTest.php

namespace Tests\Unit;

use App\Facturacion\ConsultaComprobanteAfipSdk;
use App\Services\Afip\AfipService;
use Mockery;
use Tests\TestCase;

class ConsultaComprobanteAfipSdkTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! class_exists('Afip')) {
            require_once config('afip.sdk_path');
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function sdkFalso($respuesta): object
    {
        $afip = Mockery::mock('Afip');
        $afip->ElectronicBilling = new class($respuesta) {
            private $r;
            public $llamadas = [];
            public function __construct($r) { $this->r = $r; }
            public function GetVoucherInfo($n, $p, $t) { $this->llamadas[] = [$n, $p, $t]; return $this->r; }
        };
        return $afip;
    }

    /** @test */
    public function instancia_el_sdk_una_sola_vez_aunque_consulte_varias_notas()
    {
        // Afip::__get hace `include` (no _once) de sus clases: una segunda instancia
        // en el mismo proceso tira "Cannot declare class ElectronicBilling".
        $sdk = $this->sdkFalso((object) ['CbtesAsoc' => (object) ['CbteAsoc' => (object) ['Tipo' => 11, 'PtoVta' => 17, 'Nro' => 289]]]);
        $servicio = Mockery::mock(AfipService::class);
        $servicio->shouldReceive('instancia')->once()->andReturn($sdk);

        $consulta = new ConsultaComprobanteAfipSdk($servicio);
        $a = $consulta->comprobanteAsociado(20, 111, 13);
        $b = $consulta->comprobanteAsociado(20, 112, 13);

        $this->assertSame(['tipo' => 11, 'ptovta' => 17, 'nro' => 289], $a);
        $this->assertSame($a, $b);
        $this->assertSame([[111, 20, 13], [112, 20, 13]], $sdk->ElectronicBilling->llamadas);
    }

    /** @test */
    public function interpreta_uno_o_varios_asociados_y_la_ausencia()
    {
        $uno = (object) ['CbtesAsoc' => (object) ['CbteAsoc' => (object) ['Tipo' => 6, 'PtoVta' => 3, 'Nro' => 7]]];
        $this->assertSame(['tipo' => 6, 'ptovta' => 3, 'nro' => 7], ConsultaComprobanteAfipSdk::primerAsociado($uno));

        $varios = (object) ['CbtesAsoc' => (object) ['CbteAsoc' => [
            (object) ['Tipo' => 11, 'PtoVta' => 20, 'Nro' => 1],
            (object) ['Tipo' => 11, 'PtoVta' => 20, 'Nro' => 2],
        ]]];
        $this->assertSame(['tipo' => 11, 'ptovta' => 20, 'nro' => 1], ConsultaComprobanteAfipSdk::primerAsociado($varios));

        $this->assertNull(ConsultaComprobanteAfipSdk::primerAsociado((object) ['CbteTipo' => 13]));
    }

    /** @test */
    public function si_afip_no_tiene_el_comprobante_lanza_runtime()
    {
        $servicio = Mockery::mock(AfipService::class);
        $servicio->shouldReceive('instancia')->once()->andReturn($this->sdkFalso(null));

        $this->expectException(\RuntimeException::class);
        (new ConsultaComprobanteAfipSdk($servicio))->comprobanteAsociado(20, 999, 13);
    }
}
