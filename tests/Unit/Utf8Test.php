<?php
// tests/Unit/Utf8Test.php

namespace Tests\Unit;

use App\Support\Utf8;
use PHPUnit\Framework\TestCase;

/**
 * La base de Mercado mezcla convenciones: casi todo son bytes UTF-8 en columnas
 * latin1 (pasan intactos con DB_CHARSET=latin1), pero ~300 celdas quedaron en
 * latin1 real (PE\xD1A). json_encode las rechaza y el endpoint da 500.
 */
class Utf8Test extends TestCase
{
    /** @test */
    public function repara_un_string_latin1_y_deja_intacto_el_utf8()
    {
        $this->assertSame('PEÑA', Utf8::sanear("PE\xD1A"));
        $this->assertSame('CERÁMICA', Utf8::sanear("CER\xC1MICA"));
        $this->assertSame('PEÑA', Utf8::sanear('PEÑA'));
        $this->assertSame('Poncho', Utf8::sanear('Poncho'));
        $this->assertSame('', Utf8::sanear(''));
    }

    /** @test */
    public function no_toca_lo_que_no_es_string()
    {
        $this->assertSame(12, Utf8::sanear(12));
        $this->assertSame(1.5, Utf8::sanear(1.5));
        $this->assertNull(Utf8::sanear(null));
        $this->assertTrue(Utf8::sanear(true));
    }

    /** @test */
    public function recorre_arrays_objetos_y_colecciones()
    {
        $obj = (object) ['nombre' => "PE\xD1A", 'precio' => 10, 'imagenes' => ["a\xD1", 'b']];
        $saneado = Utf8::sanear(['fila' => $obj, 'otro' => "CER\xC1MICA"]);

        $this->assertSame('PEÑA', $saneado['fila']->nombre);
        $this->assertSame(10, $saneado['fila']->precio);
        $this->assertSame(['aÑ', 'b'], $saneado['fila']->imagenes);
        $this->assertSame('CERÁMICA', $saneado['otro']);
        $this->assertSame("PE\xD1A", $obj->nombre, 'no muta el objeto original');

        $col = Utf8::sanear(collect([(object) ['n' => "\xD1"]]));
        $this->assertSame('Ñ', $col[0]->n);
    }

    /** @test */
    public function el_resultado_siempre_se_puede_codificar_a_json()
    {
        $this->assertNotFalse(json_encode(Utf8::sanear(['x' => "\xFF\xFE mezcla \xD1 fin"])));
    }
}
