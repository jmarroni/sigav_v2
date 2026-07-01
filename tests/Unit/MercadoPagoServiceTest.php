<?php

namespace Tests\Unit;

use App\Models\MercadoPagoConfig;
use App\Services\MercadoPago\MercadoPagoService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MercadoPagoServiceTest extends TestCase
{
    use RefreshDatabase;

    private function servicioConRespuestas(array $responses): MercadoPagoService
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack, 'base_uri' => 'https://api.mercadopago.com/']);

        return new MercadoPagoService($client);
    }

    private function configConToken(int $sucursalId, string $token): MercadoPagoConfig
    {
        $config = MercadoPagoConfig::create(['sucursal_id' => $sucursalId, 'activo' => true]);
        $config->access_token = $token;
        $config->save();

        return $config->fresh();
    }

    /** @test */
    public function probar_conexion_sin_token_no_pega_a_la_red()
    {
        MercadoPagoConfig::create(['sucursal_id' => 1, 'activo' => true]);
        $servicio = $this->servicioConRespuestas([]);

        $r = $servicio->probarConexion(1);

        $this->assertFalse($r['ok']);
        $this->assertSame('No hay token cargado.', $r['mensaje']);
    }

    /** @test */
    public function probar_conexion_ok_con_token_valido()
    {
        $this->configConToken(1, 'TEST-TOKEN');
        $servicio = $this->servicioConRespuestas([
            new Response(200, [], json_encode(['nickname' => 'mitienda'])),
        ]);

        $r = $servicio->probarConexion(1);

        $this->assertTrue($r['ok']);
        $this->assertStringContainsString('mitienda', $r['mensaje']);
    }

    /** @test */
    public function probar_conexion_token_invalido_no_filtra_el_error_crudo()
    {
        $this->configConToken(1, 'TOKEN-MALO');
        $servicio = $this->servicioConRespuestas([
            new ClientException(
                'Unauthorized',
                new Psr7Request('GET', 'users/me'),
                new Response(401, [], json_encode(['message' => 'invalid token']))
            ),
        ]);

        $r = $servicio->probarConexion(1);

        $this->assertFalse($r['ok']);
        $this->assertStringNotContainsString('invalid token', $r['mensaje']);
    }
}
