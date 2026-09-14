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
        'jmarroni',
        'fidegroup',
        'sha1\(\s*"[^"$]{8,}"\s*\.',
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

    /**
     * Los ejemplos/plantillas de deploy/ no deben traer secretos reales:
     * ni una LEGACY_SEMILLA no vacía, ni passwords/keys con pinta de valor
     * real (los placeholders CAMBIAR_/</${ están permitidos).
     */
    private $prohibidosDeploy = [
        '^\s*LEGACY_SEMILLA=\s*\S',
        '^\s*(DB_PASSWORD|DB_ROOT_PASSWORD|LEGACY_DB_PASS|MAIL_PASSWORD|API_SECRET_KEY)=\s*(?!CAMBIAR_|<|\$\{)\S{8,}',
    ];

    /** @test */
    public function no_hay_secretos_en_archivos_de_deploy()
    {
        $base = realpath(__DIR__.'/../..');
        $dir = $base.'/deploy';
        $hallazgos = [];
        if (! is_dir($dir)) {
            $this->markTestSkipped('No existe deploy/.');
        }
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            /** @var \SplFileInfo $file */
            if (! $file->isFile()) {
                continue;
            }
            $path = $file->getPathname();
            $lineas = file($path, FILE_IGNORE_NEW_LINES);
            if ($lineas === false) {
                continue;
            }
            foreach ($lineas as $n => $linea) {
                foreach ($this->prohibidosDeploy as $p) {
                    if (preg_match('#'.$p.'#', $linea, $m)) {
                        $hallazgos[] = substr($path, strlen($base) + 1).':'.($n + 1).' :: '.trim($m[0]);
                    }
                }
            }
        }
        $this->assertSame([], $hallazgos, "Posibles secretos en deploy/:\n".implode("\n", $hallazgos));
    }
}
