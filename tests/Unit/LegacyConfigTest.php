<?php
// tests/Unit/LegacyConfigTest.php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class LegacyConfigTest extends TestCase
{
    private $vars = [
        'LEGACY_DB_HOST', 'LEGACY_DB_USER', 'LEGACY_DB_PASS', 'LEGACY_DB_NAME',
        'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_ENCRYPTION',
        'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME', 'SMARTSUPP_KEY',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__.'/../../public/legacy_config.php';
        foreach ($this->vars as $v) {
            putenv($v); // limpia
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->vars as $v) {
            putenv($v);
        }
        parent::tearDown();
    }

    /** @test */
    public function legacy_env_devuelve_null_si_falta_o_esta_vacia()
    {
        $this->assertNull(legacy_env('LEGACY_DB_HOST'));
        putenv('LEGACY_DB_HOST=');
        $this->assertNull(legacy_env('LEGACY_DB_HOST'));
        putenv('LEGACY_DB_HOST=db');
        $this->assertSame('db', legacy_env('LEGACY_DB_HOST'));
    }

    /** @test */
    public function db_config_falla_listando_las_variables_que_faltan()
    {
        putenv('LEGACY_DB_HOST=db');
        putenv('LEGACY_DB_USER=root');
        try {
            legacy_db_config();
            $this->fail('Debía lanzar RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertSame('Faltan variables de entorno: LEGACY_DB_PASS, LEGACY_DB_NAME', $e->getMessage());
        }
    }

    /** @test */
    public function db_config_completa_devuelve_las_cuatro_claves()
    {
        putenv('LEGACY_DB_HOST=db');
        putenv('LEGACY_DB_USER=root');
        putenv('LEGACY_DB_PASS=secret');
        putenv('LEGACY_DB_NAME=laravel');
        $this->assertSame(
            ['host' => 'db', 'user' => 'root', 'pass' => 'secret', 'name' => 'laravel'],
            legacy_db_config()
        );
    }

    /** @test */
    public function mail_config_aplica_defaults_y_exige_host_usuario_y_password()
    {
        putenv('MAIL_HOST=smtp.ejemplo.com');
        putenv('MAIL_USERNAME=facturacion@ejemplo.com');
        putenv('MAIL_PASSWORD=pw');
        $cfg = legacy_mail_config();
        $this->assertSame('smtp.ejemplo.com', $cfg['host']);
        $this->assertSame('587', $cfg['port']);
        $this->assertSame('tls', $cfg['encryption']);
        $this->assertSame('facturacion@ejemplo.com', $cfg['from_address']);
        $this->assertSame('SIGAV', $cfg['from_name']);

        putenv('MAIL_PASSWORD');
        $this->expectException(\RuntimeException::class);
        legacy_mail_config();
    }

    /** @test */
    public function imagen_default_es_relativa_al_protocolo_y_al_host()
    {
        $this->assertSame(
            '//sistema.ejemplo.com/assets/img/photos/no-image-featured-image.png',
            legacy_imagen_default('sistema.ejemplo.com')
        );
    }

    /** @test */
    public function smartsupp_key_es_null_sin_env()
    {
        $this->assertNull(legacy_smartsupp_key());
        putenv('SMARTSUPP_KEY=abc');
        $this->assertSame('abc', legacy_smartsupp_key());
    }
}
