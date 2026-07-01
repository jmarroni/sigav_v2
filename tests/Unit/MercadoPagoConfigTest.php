<?php

namespace Tests\Unit;

use App\Models\MercadoPagoConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MercadoPagoConfigTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function el_access_token_se_guarda_cifrado_y_se_lee_en_texto_plano()
    {
        $config = MercadoPagoConfig::create(['sucursal_id' => 1, 'activo' => true]);
        $config->access_token = 'APP_USR-1234567890';
        $config->save();

        $crudo = DB::table('mercadopago_config')->where('id', $config->id)->value('access_token');
        $this->assertNotSame('APP_USR-1234567890', $crudo);

        $releido = MercadoPagoConfig::find($config->id);
        $this->assertSame('APP_USR-1234567890', $releido->access_token);
    }

    /** @test */
    public function access_token_nulo_no_intenta_desencriptar()
    {
        $config = MercadoPagoConfig::create(['sucursal_id' => 2, 'activo' => true]);

        $this->assertNull($config->access_token);
    }

    /** @test */
    public function token_enmascarado_solo_muestra_los_ultimos_4_caracteres()
    {
        $config = MercadoPagoConfig::create(['sucursal_id' => 3, 'activo' => true]);
        $config->access_token = 'APP_USR-1234567890';
        $config->save();

        $this->assertSame('····7890', $config->tokenEnmascarado());
    }

    /** @test */
    public function token_enmascarado_es_null_sin_token()
    {
        $config = MercadoPagoConfig::create(['sucursal_id' => 4, 'activo' => true]);

        $this->assertNull($config->tokenEnmascarado());
    }

    /** @test */
    public function activo_tiene_default_true()
    {
        $config = MercadoPagoConfig::forceCreate(['sucursal_id' => 5]);

        $this->assertTrue($config->activo);
    }

    /** @test */
    public function sucursal_id_es_unico()
    {
        MercadoPagoConfig::create(['sucursal_id' => 6, 'activo' => true]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        MercadoPagoConfig::create(['sucursal_id' => 6, 'activo' => true]);
    }
}
