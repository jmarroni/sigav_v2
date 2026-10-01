<?php
// tests/Unit/LegacyLogoPdfTest.php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Los PDFs legacy (factura, NC, ND) cargan el logo del perfil con Html2Pdf.
 * Si el src es una URL http:// del propio host y el sitio corre en HTTPS,
 * el 308 de redirección hace que Html2Pdf no pueda medir la imagen y tire
 * ImageException despues de haber pedido el CAE a AFIP. El logo debe
 * resolverse siempre como ruta local file:// dentro de public/.
 */
class LegacyLogoPdfTest extends TestCase
{
    private $dir;

    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__.'/../../public/legacy_config.php';
        $this->dir = sys_get_temp_dir().'/legacy_logo_'.uniqid();
        mkdir($this->dir.'/assets/perfil', 0777, true);
        mkdir($this->dir.'/assets/img/photos', 0777, true);
        file_put_contents($this->dir.'/assets/perfil/logo.jpeg', 'x');
        file_put_contents($this->dir.'/assets/img/photos/no-image-featured-image.png', 'x');
    }

    protected function tearDown(): void
    {
        foreach ([
            '/assets/perfil/logo.jpeg', '/assets/perfil', '/assets/img/photos/no-image-featured-image.png',
            '/assets/img/photos', '/assets/img', '/assets', '',
        ] as $p) {
            if (is_file($this->dir.$p)) {
                unlink($this->dir.$p);
            } elseif (is_dir($this->dir.$p)) {
                rmdir($this->dir.$p);
            }
        }
        parent::tearDown();
    }

    /** @test */
    public function devuelve_file_uri_local_del_logo_del_perfil()
    {
        $this->assertSame(
            'file://'.$this->dir.'/assets/perfil/logo.jpeg',
            legacy_logo_pdf($this->dir, '/assets/perfil/logo.jpeg')
        );
    }

    /** @test */
    public function nunca_devuelve_una_url_http_aunque_el_perfil_la_tenga_guardada()
    {
        $src = legacy_logo_pdf($this->dir, 'http://sistema.ejemplo.com/assets/perfil/logo.jpeg');
        $this->assertStringStartsWith('file://', $src);
    }

    /** @test */
    public function cae_al_placeholder_si_el_logo_no_existe_o_esta_vacio()
    {
        $placeholder = 'file://'.$this->dir.'/assets/img/photos/no-image-featured-image.png';
        $this->assertSame($placeholder, legacy_logo_pdf($this->dir, '/assets/perfil/inexistente.png'));
        $this->assertSame($placeholder, legacy_logo_pdf($this->dir, ''));
        $this->assertSame($placeholder, legacy_logo_pdf($this->dir, null));
    }

    /** @test */
    public function no_permite_salir_de_public_con_path_traversal()
    {
        $placeholder = 'file://'.$this->dir.'/assets/img/photos/no-image-featured-image.png';
        $this->assertSame($placeholder, legacy_logo_pdf($this->dir, '/../../etc/passwd'));
    }
}
