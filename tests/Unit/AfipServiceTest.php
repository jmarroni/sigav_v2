<?php

namespace Tests\Unit;

use App\Models\AfipConfig;
use App\Services\Afip\AfipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AfipServiceTest extends TestCase
{
    use RefreshDatabase;

    private string $tmp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmp = sys_get_temp_dir().'/afip_test_'.uniqid();
        config(['afip.storage_path' => $this->tmp]);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tmp)) {
            exec('rm -rf '.escapeshellarg($this->tmp));
        }
        parent::tearDown();
    }

    private function dummyCreds(string $entorno): void
    {
        $dir = $this->tmp.'/'.$entorno.'/';
        @mkdir($dir, 0700, true);
        file_put_contents($dir.'cert', "-----BEGIN CERTIFICATE-----\nX\n-----END CERTIFICATE-----\n");
        file_put_contents($dir.'key', "-----BEGIN PRIVATE KEY-----\nX\n-----END PRIVATE KEY-----\n");
    }

    /** @test */
    public function instancia_homologacion_usa_url_homo_y_no_produccion()
    {
        AfipConfig::create(['entorno' => 'homo', 'cuit' => '20111111112']);
        $this->dummyCreds('homo');

        $afip = (new AfipService())->instancia('homo');

        $this->assertStringContainsString('wsaahomo.afip.gov.ar', $afip->WSAA_URL);
        $this->assertStringContainsString('/homo/', $afip->CERT);
    }

    /** @test */
    public function instancia_produccion_usa_url_de_produccion()
    {
        AfipConfig::create(['entorno' => 'prod', 'cuit' => '20111111112']);
        $this->dummyCreds('prod');

        $afip = (new AfipService())->instancia('prod');

        $this->assertSame('https://wsaa.afip.gov.ar/ws/services/LoginCms', $afip->WSAA_URL);
    }

    /** @test */
    public function guardar_credenciales_rechaza_pem_invalido()
    {
        $this->expectException(\InvalidArgumentException::class);
        (new AfipService())->guardarCredenciales('homo', 'no soy pem', 'tampoco');
    }

    /** @test */
    public function guardar_credenciales_escribe_los_archivos()
    {
        (new AfipService())->guardarCredenciales(
            'homo',
            "-----BEGIN CERTIFICATE-----\nA\n-----END CERTIFICATE-----\n",
            "-----BEGIN PRIVATE KEY-----\nB\n-----END PRIVATE KEY-----\n"
        );

        $this->assertFileExists($this->tmp.'/homo/cert');
        $this->assertFileExists($this->tmp.'/homo/key');
    }

    /** @test */
    public function guardar_credenciales_rechaza_entorno_invalido()
    {
        $this->expectException(\InvalidArgumentException::class);
        (new AfipService())->guardarCredenciales('../../etc', 'x', 'y');
    }

    /** @test */
    public function guardar_credenciales_escribe_con_permisos_restrictivos()
    {
        (new AfipService())->guardarCredenciales(
            'homo',
            "-----BEGIN CERTIFICATE-----\nA\n-----END CERTIFICATE-----\n",
            "-----BEGIN PRIVATE KEY-----\nB\n-----END PRIVATE KEY-----\n"
        );

        $perms = substr(sprintf('%o', fileperms($this->tmp.'/homo/cert')), -4);
        $this->assertSame('0600', $perms);
    }

    /** @test */
    public function probar_devuelve_error_cuando_faltan_credenciales()
    {
        // Config existe pero NO hay archivos cert/key -> el SDK lanza al construir -> capturado.
        AfipConfig::create(['entorno' => 'homo', 'cuit' => '20111111112', 'ptovta' => '1', 'comprobante' => '6']);

        $r = (new AfipService())->probar('homo');

        $this->assertFalse($r['ok']);
        $this->assertArrayHasKey('mensaje', $r);
    }

    /** @test */
    public function tipos_comprobante_devuelve_vacio_en_error()
    {
        AfipConfig::create(['entorno' => 'homo', 'cuit' => '20111111112']);
        // Sin cert/key -> el SDK lanza -> [].
        $this->assertSame([], (new AfipService())->tiposComprobante('homo'));
    }
}
