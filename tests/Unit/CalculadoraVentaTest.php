<?php

namespace Tests\Unit;

use App\Ventas\CalculadoraVenta;
use PHPUnit\Framework\TestCase;

class CalculadoraVentaTest extends TestCase
{
    /** @test */
    public function sin_descuentos_total_igual_a_bruto()
    {
        $r = CalculadoraVenta::calcular(
            [['precio' => 100, 'cantidad' => 2, 'descuento' => 0],
             ['precio' => 50,  'cantidad' => 1, 'descuento' => 0]],
            0
        );
        $this->assertSame(250.0, $r['subtotalBruto']);
        $this->assertSame(250.0, $r['subtotal']);
        $this->assertSame(0.0, $r['descuentoTotalMonto']);
        $this->assertSame(250.0, $r['total']);
    }

    /** @test */
    public function solo_descuento_de_linea()
    {
        $r = CalculadoraVenta::calcular([['precio' => 100, 'cantidad' => 1, 'descuento' => 10]], 0);
        $this->assertSame(100.0, $r['subtotalBruto']);
        $this->assertSame(90.0, $r['subtotal']);
        $this->assertSame(90.0, $r['total']);
        $this->assertSame(10.0, $r['lineas'][0]['descuentoMonto']);
    }

    /** @test */
    public function solo_descuento_total()
    {
        $r = CalculadoraVenta::calcular([['precio' => 100, 'cantidad' => 1, 'descuento' => 0]], 10);
        $this->assertSame(100.0, $r['subtotal']);
        $this->assertSame(10.0, $r['descuentoTotalMonto']);
        $this->assertSame(90.0, $r['total']);
    }

    /** @test */
    public function descuentos_apilados_linea_primero_luego_total()
    {
        // línea: 100 -10% = 90 ; total: 90 -10% = 81
        $r = CalculadoraVenta::calcular([['precio' => 100, 'cantidad' => 1, 'descuento' => 10]], 10);
        $this->assertSame(90.0, $r['subtotal']);
        $this->assertSame(9.0, $r['descuentoTotalMonto']);
        $this->assertSame(81.0, $r['total']);
    }

    /** @test */
    public function redondeo_a_centavos()
    {
        $r = CalculadoraVenta::calcular([['precio' => 100, 'cantidad' => 1, 'descuento' => 33.33]], 0);
        $this->assertSame(66.67, $r['subtotal']);
        $this->assertSame(66.67, $r['total']);
    }

    /** @test */
    public function iva_se_calcula_sobre_el_total_ya_descontado()
    {
        // total 121 -> neto 100, iva 21
        $r = CalculadoraVenta::calcular([['precio' => 121, 'cantidad' => 1, 'descuento' => 0]], 0);
        $this->assertSame(121.0, $r['total']);
        $this->assertSame(100.0, $r['neto']);
        $this->assertSame(21.0, $r['iva']);
    }

    /** @test */
    public function borde_cien_por_ciento_de_linea_deja_total_en_cero()
    {
        $r = CalculadoraVenta::calcular([['precio' => 100, 'cantidad' => 1, 'descuento' => 100]], 0);
        $this->assertSame(0.0, $r['subtotal']);
        $this->assertSame(0.0, $r['total']);
    }

    /** @test */
    public function porcentaje_no_numerico_se_trata_como_cero()
    {
        $r = CalculadoraVenta::calcular([['precio' => 100, 'cantidad' => 1, 'descuento' => 'abc']], 'xx');
        $this->assertSame(100.0, $r['total']);
    }

    /** @test */
    public function porcentaje_fuera_de_rango_se_clampea()
    {
        // descuento de línea 150 -> 100% ; descuento total -10 -> 0%
        $r = CalculadoraVenta::calcular([['precio' => 100, 'cantidad' => 1, 'descuento' => 150]], -10);
        $this->assertSame(0.0, $r['subtotal']);
        $this->assertSame(0.0, $r['total']);
    }

    /** @test */
    public function carrito_vacio_da_todo_cero()
    {
        $r = CalculadoraVenta::calcular([], 50);
        $this->assertSame(0.0, $r['subtotalBruto']);
        $this->assertSame(0.0, $r['subtotal']);
        $this->assertSame(0.0, $r['descuentoTotalMonto']);
        $this->assertSame(0.0, $r['total']);
        $this->assertSame(0.0, $r['neto']);
        $this->assertSame(0.0, $r['iva']);
    }

    /** @test */
    public function invariante_subtotal_menos_descuento_total_igual_total()
    {
        $r = CalculadoraVenta::calcular(
            [['precio' => 123.45, 'cantidad' => 2, 'descuento' => 7.5],
             ['precio' => 10,     'cantidad' => 3, 'descuento' => 0]],
            12.5
        );
        $this->assertSame($r['total'], round($r['subtotal'] - $r['descuentoTotalMonto'], 2));
    }
}
