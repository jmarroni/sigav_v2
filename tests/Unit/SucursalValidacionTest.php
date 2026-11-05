<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Validación del alta/edición de sucursal (public/sucursales_post.php).
 * El código postal es obligatorio: lo usa la API del e-commerce como origen del envío.
 */
class SucursalValidacionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__.'/../../public/sucursal_validacion.php';
    }

    /** @test */
    public function acepta_cp_de_4_digitos_y_cpa_normalizando_mayusculas_y_espacios()
    {
        $this->assertSame('8500', sucursal_normalizar_cp(' 8500 '));
        $this->assertSame('R8500ABC', sucursal_normalizar_cp('r8500abc'));
    }

    /** @test */
    public function rechaza_cp_vacio_o_con_formato_invalido()
    {
        foreach ([null, '', '   ', '850', '85000', 'ABCD', '8500-A', "8500'; DROP"] as $cp) {
            $this->assertNull(sucursal_normalizar_cp($cp), 'Debería rechazar: '.var_export($cp, true));
        }
    }

    /** @test */
    public function el_error_de_validacion_pide_nombre_y_codigo_postal()
    {
        $this->assertNull(sucursal_error_validacion(['nombre' => 'Viedma', 'codigo_postal' => '8500']));
        $this->assertStringContainsString('código postal', sucursal_error_validacion(['nombre' => 'Viedma', 'codigo_postal' => '']));
        $this->assertStringContainsString('código postal', sucursal_error_validacion(['nombre' => 'Viedma']));
        $this->assertStringContainsString('nombre', sucursal_error_validacion(['nombre' => ' ', 'codigo_postal' => '8500']));
    }
}
