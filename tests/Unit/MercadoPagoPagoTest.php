<?php

namespace Tests\Unit;

use App\Models\MercadoPagoPago;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MercadoPagoPagoTest extends TestCase
{
    use RefreshDatabase;

    private function datosBase(array $overrides = []): array
    {
        return array_merge([
            'sucursal_id' => 1,
            'mp_payment_id' => '123',
            'fecha' => now(),
            'monto' => 100.50,
            'estado' => 'approved',
            'payload_raw' => ['id' => 123, 'status' => 'approved'],
        ], $overrides);
    }

    /** @test */
    public function estado_facturacion_tiene_default_pendiente_y_no_es_asignable_masivamente()
    {
        $pago = MercadoPagoPago::create($this->datosBase(['estado_facturacion' => 'facturado']));

        $this->assertSame('pendiente', $pago->fresh()->estado_facturacion);
    }

    /** @test */
    public function payload_raw_se_castea_a_array()
    {
        $pago = MercadoPagoPago::create($this->datosBase());

        $this->assertIsArray($pago->fresh()->payload_raw);
        $this->assertSame(123, $pago->fresh()->payload_raw['id']);
    }

    /** @test */
    public function mp_payment_id_es_unico_por_sucursal()
    {
        MercadoPagoPago::create($this->datosBase(['mp_payment_id' => '999']));

        $this->expectException(QueryException::class);
        MercadoPagoPago::create($this->datosBase(['mp_payment_id' => '999']));
    }

    /** @test */
    public function el_mismo_mp_payment_id_es_valido_en_otra_sucursal()
    {
        MercadoPagoPago::create($this->datosBase(['sucursal_id' => 1, 'mp_payment_id' => '777']));
        $pago = MercadoPagoPago::create($this->datosBase(['sucursal_id' => 2, 'mp_payment_id' => '777']));

        $this->assertSame(2, MercadoPagoPago::count());
        $this->assertSame(2, $pago->sucursal_id);
    }
}
