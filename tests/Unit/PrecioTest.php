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

    /** @test */
    public function el_precio_online_suma_el_recargo_y_redondea_al_entero_mas_cercano()
    {
        $casos = [
            '1000' => '1100.00',
            '999' => '1099.00',        // 1098.90
            '60.000' => '66000.00',
            '1.200,000' => '1320.00',
            '1234.50' => '1358.00',    // 1357.95
            '3292.8' => '3622.00',     // 3622.08: hacia abajo
            '5' => '6.00',             // 5.50: la mitad sube
            '15' => '17.00',           // 16.50
        ];
        foreach ($casos as $entrada => $esperado) {
            $this->assertSame($esperado, Precio::online($entrada, 10), "entrada: [$entrada]");
        }
    }

    /** @test */
    public function el_precio_online_es_null_si_el_precio_no_sirve_para_vender()
    {
        // '0.01' con 10% da 0.011 → 0: tampoco se vende gratis.
        foreach ([null, '', 'abc', '2025-07-02', '0', '0.00', '0.01'] as $entrada) {
            $this->assertNull(Precio::online($entrada, 10), 'entrada: ' . var_export($entrada, true));
        }
    }

    /** @test */
    public function con_recargo_cero_solo_redondea_al_entero()
    {
        $this->assertSame('1235.00', Precio::online('1234.56', 0));
        $this->assertSame('1234.00', Precio::online('1234.40', 0));
        $this->assertSame('1000.00', Precio::online('1000', 0));
    }

    /** @test */
    public function un_recargo_fuera_de_rango_es_un_error_de_configuracion()
    {
        foreach ([-1, 101] as $pct) {
            try {
                Precio::online('1000', $pct);
                $this->fail("pct $pct debía fallar");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString('ECOMMERCE_RECARGO_PCT', $e->getMessage());
            }
        }
    }

    /** @test */
    public function un_precio_absurdo_no_desborda_y_no_se_publica()
    {
        // Ej. un código de barras cargado en el precio: antes intdiv() recibía un float y tiraba warning.
        $this->assertNull(Precio::online('999999999999999', 10));
        $this->assertNull(Precio::online('99999999999999999', 100));
        $this->assertSame('1099999999999.00', Precio::online('999999999999', 10), 'hasta 12 dígitos de pesos sí');
    }
}
