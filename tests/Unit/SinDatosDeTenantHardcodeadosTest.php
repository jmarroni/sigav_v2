<?php
// tests/Unit/SinDatosDeTenantHardcodeadosTest.php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * El mismo código sirve a más de un negocio. Ningún literal de un tenant
 * (dominio, cuenta de hosting, CUIT, SMTP, clave de chat) puede vivir en el
 * código: va en .env, afip_config o perfil.
 */
class SinDatosDeTenantHardcodeadosTest extends TestCase
{
    /** Patrones prohibidos (regex, case-insensitive). */
    private $prohibidos = [
        'mercado-artesanal\.com\.ar',
        'mercado-artisanal',            // typo histórico en enviar_por_mail_pedido.php
        'c2101314',                     // cuenta de hosting Ferozo (DB, SMTP)
        'ferozo\.com',
        '_smartsupp\.key\s*=\s*[\'"][0-9a-f]{8,}',
        '\$cuitempresa\s*=\s*"\d{11}',
        '<b>CUIT</b>&nbsp;\d{11}',
        'jmarroni@gmail\.com',
    ];

    /** Dónde buscar. */
    private $rutas = ['app', 'routes', 'config', 'resources/views', 'public'];

    /** Qué saltar dentro de esas rutas. */
    private $excluir = [
        '/public/vendor/', '/public/assets/', '/public/presupuesto/', '/public/facturas/',
        '/public/clientes/', '/public/branchs/', '/public/cobros/', '/public/notas_credito/',
        '/public/AFIP/', '/public/productos/', '/public/upload', '/public/css/', '/public/js/',
        '/public/img/', '/public/fonts/',
    ];

    /** @test */
    public function no_hay_literales_de_tenant_en_el_codigo()
    {
        $base = realpath(__DIR__.'/../..');
        $hallazgos = [];
        foreach ($this->rutas as $ruta) {
            $dir = $base.'/'.$ruta;
            if (! is_dir($dir)) {
                continue;
            }
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                /** @var \SplFileInfo $file */
                $path = $file->getPathname();
                if (! preg_match('#\.(php|js|blade\.php)$#', $path)) {
                    continue;
                }
                foreach ($this->excluir as $ex) {
                    if (strpos($path, $ex) !== false) {
                        continue 2;
                    }
                }
                $contenido = file_get_contents($path);
                foreach ($this->prohibidos as $p) {
                    if (preg_match('#'.$p.'#i', $contenido, $m)) {
                        $hallazgos[] = substr($path, strlen($base) + 1).' :: '.$m[0];
                    }
                }
            }
        }
        $this->assertSame([], $hallazgos, "Literales de tenant hardcodeados:\n".implode("\n", $hallazgos));
    }
}
