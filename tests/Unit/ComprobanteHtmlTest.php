<?php
// tests/Unit/ComprobanteHtmlTest.php

namespace Tests\Unit;

use App\Facturacion\ComprobanteHtml;
use PHPUnit\Framework\TestCase;

/**
 * HTML del comprobante (factura / presupuesto) que se renderiza con Html2Pdf.
 * Es la misma plantilla que arma public/facturar.php al emitir, extraída para
 * poder regenerar el PDF de una factura ya emitida a partir de la base.
 */
class ComprobanteHtmlTest extends TestCase
{
    private function datos(array $override = []): array
    {
        return array_replace_recursive([
            'logo_src' => 'file:///tmp/logo.png',
            'sucursal' => ['nombre' => 'Los menucos', 'direccion' => 'Av. Siempre Viva 123', 'codigo_postal' => '8500', 'provincia' => 'Río Negro'],
            'emisor' => ['cuit' => '30715251988', 'ingresos_brutos' => '46161295', 'condicion_iva' => 'IVA EXENTO'],
            'comprobante_tipo' => 11,
            'presupuesto' => false,
            'ptovta' => 20,
            'numero' => 590,
            'fecha' => '30-09-2026',
            'cae' => '86395342432866',
            'cae_vto' => '10-10-2026',
            'cliente' => ['nombre' => 'Juan Pérez', 'direccion' => 'Calle 1', 'documento' => '20111111112', 'iva_texto' => 'Cons. Final', 'forma_pago' => null],
            'lineas' => [['nombre' => 'PULOVER TINTES', 'cantidad' => 1, 'precio' => 150000, 'descuento' => 0]],
            'descuento_total' => 0,
            'total' => 150000,
        ], $override);
    }

    /** @test */
    public function el_documento_trae_original_y_duplicado_en_dos_paginas()
    {
        $html = ComprobanteHtml::documento($this->datos());

        $this->assertSame(2, substr_count($html, '<page>'));
        $this->assertStringContainsString('ORIGINAL', $html);
        $this->assertStringContainsString('DUPLICADO', $html);
        $this->assertStringNotContainsString('@@COMPROBANTE@@', $html);
    }

    /** @test */
    public function una_factura_c_muestra_numero_cae_cliente_y_totales()
    {
        $html = ComprobanteHtml::cuerpo($this->datos());

        $this->assertStringContainsString('FACTURA NRO.</b>&nbsp;000020&nbsp;-&nbsp;000590', $html);
        $this->assertStringContainsString("font-size: 60px;width: 70px;text-align: center;'>C</td>", $html);
        $this->assertStringContainsString('Comprobante Electronico', $html);
        $this->assertStringContainsString('<b>CUIT</b>&nbsp;30715251988', $html);
        $this->assertStringContainsString('Fecha de Emisi&oacute;n</b>&nbsp;30-09-2026', $html);
        $this->assertStringContainsString('<b>Nombre y Apellido</b> Juan Pérez', $html);
        $this->assertStringContainsString('<b>IVA </b> Cons. Final', $html);
        $this->assertStringContainsString('<i>PULOVER TINTES</i>', $html);
        $this->assertStringContainsString('150.000,00', $html);
        $this->assertStringContainsString('<b>CAE Nro.:</b> 86395342432866', $html);
        $this->assertStringContainsString('<b>Fecha de Vto. CAE: </b>10-10-2026', $html);
        $this->assertStringContainsString("file:///tmp/logo.png", $html);
        // Factura C: sin desglose de IVA
        $this->assertStringNotContainsString('Importe Neto', $html);
    }

    /** @test */
    public function la_forma_de_pago_solo_aparece_cuando_se_conoce()
    {
        $sin = ComprobanteHtml::cuerpo($this->datos());
        $con = ComprobanteHtml::cuerpo($this->datos(['cliente' => ['forma_pago' => 'Transferencia']]));

        $this->assertStringNotContainsString('Forma de pago', $sin);
        $this->assertStringContainsString('<b>Forma de pago </b> Transferencia', $con);
    }

    /** @test */
    public function el_descuento_total_agrega_subtotal_y_descuento_antes_del_total()
    {
        $html = ComprobanteHtml::cuerpo($this->datos([
            'lineas' => [['nombre' => 'A', 'cantidad' => 2, 'precio' => 100, 'descuento' => 0]],
            'descuento_total' => 10,
            'total' => 180,
        ]));

        $this->assertStringContainsString('>Subtotal<', $html);
        $this->assertStringContainsString('>200,00<', $html);
        $this->assertStringContainsString('>Descuento (10%)<', $html);
        $this->assertStringContainsString('>-20,00<', $html);
        $this->assertStringContainsString('>180,00<', $html);
    }

    /** @test */
    public function el_descuento_de_linea_se_refleja_en_el_precio_unitario()
    {
        $html = ComprobanteHtml::cuerpo($this->datos([
            'lineas' => [['nombre' => 'A', 'cantidad' => 1, 'precio' => 1000, 'descuento' => 25]],
            'total' => 750,
        ]));

        $this->assertStringContainsString('>750,00<', $html);
        $this->assertStringNotContainsString('>1.000,00<', $html);
    }

    /** @test */
    public function una_factura_a_desglosa_neto_e_iva()
    {
        $html = ComprobanteHtml::cuerpo($this->datos([
            'comprobante_tipo' => 1,
            'lineas' => [['nombre' => 'A', 'cantidad' => 1, 'precio' => 100, 'descuento' => 0]],
            'total' => 100,
        ]));

        $this->assertStringContainsString("text-align: center;'>A</td>", $html);
        // Mismo desglose que CalculadoraVenta declaró a AFIP: neto 82,64 e IVA 17,35 (no 17,36).
        $this->assertStringContainsString('Importe Neto</td>', $html);
        $this->assertStringContainsString('>82,64<', $html);
        $this->assertStringContainsString('IVA (21%)</td>', $html);
        $this->assertStringContainsString('>17,35<', $html);
    }

    /** @test */
    public function un_presupuesto_no_es_factura_y_no_lleva_cae()
    {
        $html = ComprobanteHtml::cuerpo($this->datos(['presupuesto' => true, 'cae' => '', 'cae_vto' => '', 'numero' => 7]));

        $this->assertStringContainsString('PRESUPUESTO NRO.</b>&nbsp;000020&nbsp;-&nbsp;000007', $html);
        $this->assertStringContainsString("text-align: center;'>X</td>", $html);
        $this->assertStringContainsString('No valido como factura', $html);
        $this->assertStringNotContainsString('CAE Nro.', $html);
    }

    /** @test */
    public function escapa_el_html_de_los_datos_de_la_base()
    {
        $html = ComprobanteHtml::cuerpo($this->datos([
            'lineas' => [['nombre' => 'CHAL <rojo> & negro', 'cantidad' => 1, 'precio' => 1, 'descuento' => 0]],
            'cliente' => ['nombre' => 'A<b>B'],
        ]));

        $this->assertStringContainsString('CHAL &lt;rojo&gt; &amp; negro', $html);
        $this->assertStringContainsString('A&lt;b&gt;B', $html);
    }

    /** @test */
    public function una_direccion_larga_de_sucursal_se_parte_en_dos_lineas()
    {
        $html = ComprobanteHtml::cuerpo($this->datos([
            'sucursal' => ['direccion' => 'Cerro Catedral San Carlos de Bariloche'],
        ]));

        $this->assertStringContainsString('Cerro Catedral San Carlos<br /> de Bariloche', $html);
    }
}
