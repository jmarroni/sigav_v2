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
}
