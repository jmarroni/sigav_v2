<?php
// tests/Unit/LegacyComprobantesTest.php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Helpers compartidos por los procesadores legacy de comprobantes
 * (nota_de_credito.php, nota_de_debito.php): resolución del punto de venta
 * de la sucursal y pre-vuelo del PDF antes de pedir el CAE a AFIP.
 */
class LegacyComprobantesTest extends TestCase
{
    private $dir;

    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__.'/../../public/legacy_comprobantes.php';
        $this->dir = sys_get_temp_dir().'/legacy_comp_'.uniqid();
        mkdir($this->dir, 0777, true);
    }

    protected function tearDown(): void
    {
        exec('chmod -R u+w '.escapeshellarg($this->dir).'; rm -rf '.escapeshellarg($this->dir));
        parent::tearDown();
    }

    /** @test */
    public function el_punto_de_venta_sale_de_la_sucursal()
    {
        $this->assertSame(17, legacy_ptovta_sucursal(['id' => 40, 'pto_vta' => '17']));
        $this->assertSame(20, legacy_ptovta_sucursal(['pto_vta' => 20]));
    }

    /** @test */
    public function sin_punto_de_venta_configurado_devuelve_null()
    {
        $this->assertNull(legacy_ptovta_sucursal(['pto_vta' => null]));
        $this->assertNull(legacy_ptovta_sucursal(['pto_vta' => '']));
        $this->assertNull(legacy_ptovta_sucursal(['pto_vta' => '0']));
        $this->assertNull(legacy_ptovta_sucursal(['pto_vta' => 'abc']));
        $this->assertNull(legacy_ptovta_sucursal([]));
        $this->assertNull(legacy_ptovta_sucursal(null));
    }

    /** @test */
    public function el_punto_de_venta_real_de_un_comprobante_esta_en_el_nombre_de_su_pdf()
    {
        $this->assertSame(17, legacy_ptovta_de_pdf('/facturas/17_86384356395368_000288.pdf'));
        $this->assertSame(20, legacy_ptovta_de_pdf('/notas_credito/20_86384356395368_000111.pdf'));
        $this->assertNull(legacy_ptovta_de_pdf(''));
        $this->assertNull(legacy_ptovta_de_pdf(null));
        $this->assertNull(legacy_ptovta_de_pdf('/facturas/sin_numero.pdf'));
    }

    /** @test */
    public function el_punto_de_venta_del_comprobante_prefiere_el_pdf_y_cae_a_la_sucursal()
    {
        $sucursal = ['pto_vta' => 17];
        $this->assertSame(20, legacy_ptovta_comprobante('/facturas/20_1_000001.pdf', $sucursal));
        $this->assertSame(17, legacy_ptovta_comprobante('', $sucursal));
        $this->assertNull(legacy_ptovta_comprobante('', ['pto_vta' => null]));
    }

    /** @test */
    public function el_prevuelo_pasa_con_carpeta_escribible_y_logo_valido()
    {
        $logo = $this->dir.'/logo.png';
        file_put_contents($logo, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='
        ));

        $this->assertNull(legacy_prevuelo_pdf($this->dir, 'file://'.$logo));
    }

    /** @test */
    public function el_prevuelo_falla_si_la_carpeta_no_existe()
    {
        $error = legacy_prevuelo_pdf($this->dir.'/no_existe', 'file://'.$this->dir.'/x.png');

        $this->assertIsString($error);
        $this->assertStringContainsString('carpeta', $error);
    }

    /** @test */
    public function el_prevuelo_falla_si_la_carpeta_no_es_escribible()
    {
        if (posix_geteuid() === 0) {
            $this->markTestSkipped('root escribe en cualquier carpeta');
        }
        $carpeta = $this->dir.'/solo_lectura';
        mkdir($carpeta, 0555);

        $error = legacy_prevuelo_pdf($carpeta, 'file://'.$this->dir.'/x.png');

        $this->assertIsString($error);
        $this->assertStringContainsString('escribible', $error);
    }

    /** @test */
    public function el_prevuelo_falla_si_el_logo_no_se_puede_renderizar()
    {
        $error = legacy_prevuelo_pdf($this->dir, 'file://'.$this->dir.'/no_existe.png');

        $this->assertIsString($error);
        $this->assertStringContainsString('PDF', $error);
    }

    /** @test */
    public function el_escape_para_el_pdf_latin1_conserva_los_acentos()
    {
        $nombre_latin1 = utf8_decode('Muñoz & "Cía" <ñandú>');

        $escapado = legacy_html_latin1($nombre_latin1);

        $this->assertSame(utf8_decode('Muñoz &amp; &quot;Cía&quot; &lt;ñandú&gt;'), $escapado);
        $this->assertNotSame('', legacy_html_latin1(utf8_decode('ñ')));
    }

    /** @test */
    public function el_texto_del_formulario_se_lleva_a_latin1()
    {
        $this->assertSame(utf8_decode('Devolución'), legacy_texto_form_latin1('  Devolución '));
        $this->assertSame('', legacy_texto_form_latin1(null));
        $latin1 = utf8_decode('ya latin1 ñ');
        $this->assertSame($latin1, legacy_texto_form_latin1($latin1));
    }
}
