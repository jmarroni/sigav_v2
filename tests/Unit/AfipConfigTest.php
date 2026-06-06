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
        AfipConfig::create(['entorno' => 'homo', 'activo' => true]);
        AfipConfig::create(['entorno' => 'prod', 'activo' => false]);
    }

    /** @test */
    public function activar_deja_exactamente_un_entorno_activo()
    {
        $this->seedEntornos();

        AfipConfig::activar('prod');

        $this->assertSame('prod', AfipConfig::activa()->entorno);
        $this->assertSame(1, AfipConfig::where('activo', true)->count());
    }

    /** @test */
    public function ruta_storage_apunta_a_la_carpeta_del_entorno()
    {
        config(['afip.storage_path' => '/tmp/afip_x']);
        $cfg = AfipConfig::create(['entorno' => 'homo']);

        $this->assertSame('/tmp/afip_x/homo/', $cfg->rutaStorage());
    }
}
