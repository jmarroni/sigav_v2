<?php

namespace Tests\Unit;

use App\Models\AfipConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AfipConfigTest extends TestCase
{
    use RefreshDatabase;

    private function seedEntornos(): void
    {
        // forceCreate para fijar el estado inicial de `activo` (no está en $fillable a propósito).
        AfipConfig::forceCreate(['entorno' => 'homo', 'activo' => true]);
        AfipConfig::forceCreate(['entorno' => 'prod', 'activo' => false]);
    }

    /** @test */
    public function activar_cambia_el_activo_dejando_exactamente_uno()
    {
        $this->seedEntornos(); // homo arranca activo

        AfipConfig::activar('prod');

        // El que estaba activo (homo) quedó en false y prod en true.
        $this->assertSame('prod', AfipConfig::activa()->entorno);
        $this->assertSame(1, AfipConfig::where('activo', true)->count());
        $this->assertFalse(AfipConfig::where('entorno', 'homo')->first()->activo);
    }

    /** @test */
    public function activar_con_entorno_invalido_lanza_excepcion_y_no_rompe_el_estado()
    {
        $this->seedEntornos(); // homo activo

        try {
            AfipConfig::activar('inexistente');
            $this->fail('Se esperaba InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            // esperado
        }

        // El rollback de la transacción dejó homo activo intacto.
        $this->assertSame('homo', AfipConfig::activa()->entorno);
        $this->assertSame(1, AfipConfig::where('activo', true)->count());
    }

    /** @test */
    public function activa_devuelve_null_cuando_ninguno_esta_activo()
    {
        AfipConfig::forceCreate(['entorno' => 'homo', 'activo' => false]);
        AfipConfig::forceCreate(['entorno' => 'prod', 'activo' => false]);

        $this->assertNull(AfipConfig::activa());
    }

    /** @test */
    public function ruta_storage_apunta_a_la_carpeta_del_entorno()
    {
        config(['afip.storage_path' => '/tmp/afip_x']);
        $cfg = AfipConfig::create(['entorno' => 'homo']);

        $this->assertSame('/tmp/afip_x/homo/', $cfg->rutaStorage());
    }

    /** @test */
    public function tiene_credenciales_detecta_cert_y_key()
    {
        $dir = sys_get_temp_dir().'/afip_cred_'.uniqid();
        config(['afip.storage_path' => $dir]);
        $cfg = AfipConfig::create(['entorno' => 'homo']);

        $this->assertFalse($cfg->tieneCredenciales());

        @mkdir($dir.'/homo/', 0700, true);
        file_put_contents($dir.'/homo/cert', 'x');
        file_put_contents($dir.'/homo/key', 'x');

        $this->assertTrue($cfg->tieneCredenciales());

        exec('rm -rf '.escapeshellarg($dir));
    }
}
