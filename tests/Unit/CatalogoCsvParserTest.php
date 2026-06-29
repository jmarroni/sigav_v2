<?php

namespace Tests\Unit;

use App\Catalogo\CatalogoCsvParser;
use PHPUnit\Framework\TestCase;

class CatalogoCsvParserTest extends TestCase
{
    private const HEADER = "CODIGO;PRODUCTO;STOCK;PRECIO ULTIMA COMPRA;VALOR MINORISTA;VALOR MAYORISTA;PROVEEDOR;VENDIDOS;INGRESOS";

    private function csv(array $filas): string
    {
        return self::HEADER . "\n" . implode("\n", $filas);
    }

    /** @test */
    public function mapea_columnas_basicas()
    {
        $r = CatalogoCsvParser::parse($this->csv([
            '1699541342213;Babucha Slide (M, Azul);1; $34.001,00; $ 74.802,20 ; $ 68.002,00 ;SALPA SRL;;',
        ]));

        $this->assertCount(1, $r['productos']);
        $p = $r['productos'][0];
        $this->assertSame('1699541342213', $p['codigo_barras']);
        $this->assertSame('Babucha Slide (M, Azul)', $p['nombre']);
        $this->assertSame(1, $p['stock']);
        $this->assertSame(34001.00, $p['costo']);
        $this->assertSame(74802.20, $p['precio_unidad']);
        $this->assertSame(68002.00, $p['precio_mayorista']);
        $this->assertSame('SALPA SRL', $p['proveedor']);
        $this->assertSame(0, $r['omitidas']);
        $this->assertSame(0, $r['codigosGenerados']);
    }

    /** @test */
    public function parsea_plata_en_distintos_formatos()
    {
        $this->assertSame(74802.20, CatalogoCsvParser::parseMoney(' $ 74.802,20 '));
        $this->assertSame(34001.00, CatalogoCsvParser::parseMoney('$34.001,00'));
        $this->assertSame(5440.00, CatalogoCsvParser::parseMoney(' $ 5.440,00 '));
        $this->assertSame(0.0, CatalogoCsvParser::parseMoney(''));
        $this->assertSame(0.0, CatalogoCsvParser::parseMoney('   '));
        $this->assertSame(1234567.89, CatalogoCsvParser::parseMoney('$ 1.234.567,89'));
    }

    /** @test */
    public function omite_filas_sin_producto()
    {
        $r = CatalogoCsvParser::parse($this->csv([
            ';;;;;;;;',
            ';Cuello sin codigo;1; $3.645,00; $ 8.019,00 ; $ 7.290,00 ;SALPA SRL;;',
            '   ',
        ]));

        // La fila vacía cuenta como omitida; la línea de espacios en blanco no.
        $this->assertSame(1, $r['omitidas']);
        $this->assertCount(1, $r['productos']);
        $this->assertSame('Cuello sin codigo', $r['productos'][0]['nombre']);
    }

    /** @test */
    public function genera_codigo_para_codigo_vacio()
    {
        $r = CatalogoCsvParser::parse($this->csv([
            ';Cuello sin codigo;1; $3.645,00; $ 8.019,00 ; $ 7.290,00 ;SALPA SRL;;',
        ]));

        $this->assertSame(1, $r['codigosGenerados']);
        $this->assertSame('SC-0001', $r['productos'][0]['codigo_barras']);
    }

    /** @test */
    public function genera_codigo_para_duplicados_y_conserva_el_primero()
    {
        $r = CatalogoCsvParser::parse($this->csv([
            '7791307936972;Producto A;1; $1.000,00; $ 2.000,00 ; $ 1.800,00 ;SALPA SRL;;',
            '7791307936972;Producto B;1; $1.000,00; $ 2.000,00 ; $ 1.800,00 ;SALPA SRL;;',
            '7791307936972;Producto C;1; $1.000,00; $ 2.000,00 ; $ 1.800,00 ;SALPA SRL;;',
        ]));

        $this->assertSame('7791307936972', $r['productos'][0]['codigo_barras']);
        $this->assertSame('SC-0001', $r['productos'][1]['codigo_barras']);
        $this->assertSame('SC-0002', $r['productos'][2]['codigo_barras']);
        $this->assertSame(2, $r['codigosGenerados']);
    }

    /** @test */
    public function todos_los_codigos_resultan_unicos()
    {
        $r = CatalogoCsvParser::parse($this->csv([
            ';Sin codigo 1;1; $1,00; $ 2,00 ; $ 1,00 ;SALPA SRL;;',
            'SC-0001;Choca con generado;1; $1,00; $ 2,00 ; $ 1,00 ;SALPA SRL;;',
            ';Sin codigo 2;1; $1,00; $ 2,00 ; $ 1,00 ;SALPA SRL;;',
        ]));

        $codigos = array_column($r['productos'], 'codigo_barras');
        $this->assertSame($codigos, array_unique($codigos), 'No debe haber códigos repetidos');
        $this->assertCount(3, $codigos);
    }
}
