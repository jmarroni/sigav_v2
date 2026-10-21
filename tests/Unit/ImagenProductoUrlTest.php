<?php
// tests/Unit/ImagenProductoUrlTest.php

namespace Tests\Unit;

use App\Models\Imagen_producto;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Resolución de URLs de imágenes de producto. En la base conviven URLs
 * históricas absolutas (atadas al host viejo) y URLs nuevas relativas.
 */
class ImagenProductoUrlTest extends TestCase
{
    /** @test */
    public function miniatura_inserta_el_prefijo_thumb_en_la_carpeta_del_producto()
    {
        $this->assertSame(
            '/storage/productos/images/15/thumb_300x300_abc.jpg',
            Imagen_producto::miniatura('/storage/productos/images/15/abc.jpg', 15)
        );
        $this->assertSame(
            'https://otro.host/storage/productos/images/15/thumb_300x300_abc.jpg',
            Imagen_producto::miniatura('https://otro.host/storage/productos/images/15/abc.jpg', 15)
        );
    }

    /** @test */
    public function miniatura_solo_cambia_el_nombre_de_archivo_aunque_el_id_aparezca_antes_en_la_url()
    {
        $this->assertSame(
            'https://cdn1.host/1/storage/productos/images/1/thumb_300x300_abc.jpg',
            Imagen_producto::miniatura('https://cdn1.host/1/storage/productos/images/1/abc.jpg', 1)
        );
    }

    /** @test */
    public function miniatura_no_toca_el_fallback_sin_imagen()
    {
        $this->assertSame('/assets/img/photos/no-image-featured-image.png',
            Imagen_producto::miniatura('/assets/img/photos/no-image-featured-image.png', 15));
    }

    /** @test */
    public function absoluta_completa_el_host_solo_para_urls_relativas()
    {
        // url() resuelve contra el host del request actual (en prod, el dominio de la instancia).
        URL::forceRootUrl('https://sistema.ejemplo.test');
        URL::forceScheme('https');
        $this->assertSame('https://sistema.ejemplo.test/storage/productos/images/1/a.jpg',
            Imagen_producto::absoluta('/storage/productos/images/1/a.jpg'));
        $this->assertSame('http://viejo.host/storage/productos/images/1/a.jpg',
            Imagen_producto::absoluta('http://viejo.host/storage/productos/images/1/a.jpg'));
    }

    /** @test */
    public function relativa_desde_path_de_storage()
    {
        $this->assertSame('/storage/productos/images/9/x.png',
            Imagen_producto::relativaDesdePath('public/productos/images/9/x.png'));
    }
}
