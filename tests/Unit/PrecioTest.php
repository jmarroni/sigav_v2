<?php

namespace Tests\Unit;

use App\Support\Precio;
use PHPUnit\Framework\TestCase;

class PrecioTest extends TestCase
{
    /** @test */
    public function normaliza_los_formatos_que_hay_en_la_base_a_dos_decimales()
    {
        $casos = [
            '15000' => '15000.00',
            '60.000' => '60000.00',
            '8.500' => '8500.00',
            '1.200.000' => '1200000.00',
            '1.200,000' => '1200.00',
            '1.200,5' => '1200.50',
            '3292.8' => '3292.80',
            '10' => '10.00',
            '60000.00' => '60000.00',
            ' $ 2.500 ' => '2500.00',
        ];
        foreach ($casos as $entrada => $esperado) {
            $this->assertSame($esperado, Precio::normalizar($entrada), "entrada: [$entrada]");
        }
    }

    /** @test */
    public function devuelve_null_para_vacios_fechas_y_basura()
    {
        foreach ([null, '', '   ', 'NULL', '2025-07-02', 'abc', '12,3,4', '-5'] as $entrada) {
            $this->assertNull(Precio::normalizar($entrada), 'entrada: ' . var_export($entrada, true));
        }
    }
}
