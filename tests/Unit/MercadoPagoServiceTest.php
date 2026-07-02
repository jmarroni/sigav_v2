<?php

namespace Tests\Unit;

use App\Models\MercadoPagoConfig;
use App\Services\MercadoPago\MercadoPagoService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
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

    /** @test */
    public function probar_conexion_maneja_fallas_de_conexion_sin_filtrar_detalle()
    {
        $this->configConToken(1, 'TEST-TOKEN');
        $servicio = $this->servicioConRespuestas([
            new ConnectException(
                'cURL error 6: Could not resolve host: api.mercadopago.com',
                new Psr7Request('GET', 'users/me')
            ),
        ]);

        $r = $servicio->probarConexion(1);

        $this->assertFalse($r['ok']);
        $this->assertStringNotContainsString('resolve host', $r['mensaje']);
    }

    /** @test */
    public function sincronizar_pagos_sin_token_no_pega_a_la_red()
    {
        MercadoPagoConfig::create(['sucursal_id' => 1, 'activo' => true]);
        $servicio = $this->servicioConRespuestas([]);

        $r = $servicio->sincronizarPagos(1, \Carbon\Carbon::parse('2026-06-01'), \Carbon\Carbon::parse('2026-06-30'));

        $this->assertFalse($r['ok']);
        $this->assertSame(0, $r['nuevos']);
    }

    /** @test */
    public function sincronizar_pagos_crea_un_registro_por_pago_nuevo()
    {
        $this->configConToken(1, 'TEST-TOKEN');
        $pagoMp = [
            'id' => 123456,
            'date_approved' => '2026-06-01T10:00:00.000-03:00',
            'transaction_amount' => 1500.50,
            'status' => 'approved',
            'payment_type_id' => 'account_money',
            'payer' => ['email' => 'cliente@test.com'],
        ];
        $servicio = $this->servicioConRespuestas([
            new Response(200, [], json_encode(['results' => [$pagoMp]])),
        ]);

        $r = $servicio->sincronizarPagos(1, \Carbon\Carbon::parse('2026-06-01'), \Carbon\Carbon::parse('2026-06-30'));

        $this->assertTrue($r['ok']);
        $this->assertSame(1, $r['nuevos']);
        $this->assertSame(1, \App\Models\MercadoPagoPago::count());
        $this->assertSame('approved', \App\Models\MercadoPagoPago::first()->estado);
        $this->assertSame('cliente@test.com', \App\Models\MercadoPagoPago::first()->comprador);
    }

    /** @test */
    public function sincronizar_pagos_no_duplica_si_se_corre_dos_veces()
    {
        $this->configConToken(1, 'TEST-TOKEN');
        $pagoMp = [
            'id' => 123456,
            'date_created' => '2026-06-01T10:00:00.000-03:00',
            'transaction_amount' => 500,
            'status' => 'approved',
        ];
        $desde = \Carbon\Carbon::parse('2026-06-01');
        $hasta = \Carbon\Carbon::parse('2026-06-30');

        $servicio1 = $this->servicioConRespuestas([new Response(200, [], json_encode(['results' => [$pagoMp]]))]);
        $r1 = $servicio1->sincronizarPagos(1, $desde, $hasta);
        $this->assertSame(1, $r1['nuevos']);

        $servicio2 = $this->servicioConRespuestas([new Response(200, [], json_encode(['results' => [$pagoMp]]))]);
        $r2 = $servicio2->sincronizarPagos(1, $desde, $hasta);
        $this->assertSame(0, $r2['nuevos']);

        $this->assertSame(1, \App\Models\MercadoPagoPago::count());
    }

    /** @test */
    public function sincronizar_pagos_con_el_mismo_mp_payment_id_en_sucursales_distintas_no_se_deduplica()
    {
        $this->configConToken(1, 'TEST-TOKEN-1');
        $this->configConToken(2, 'TEST-TOKEN-2');
        $pagoMp = [
            'id' => 555555,
            'date_created' => '2026-06-01T10:00:00.000-03:00',
            'transaction_amount' => 250,
            'status' => 'approved',
        ];
        $desde = \Carbon\Carbon::parse('2026-06-01');
        $hasta = \Carbon\Carbon::parse('2026-06-30');

        $servicioSucursal1 = $this->servicioConRespuestas([new Response(200, [], json_encode(['results' => [$pagoMp]]))]);
        $r1 = $servicioSucursal1->sincronizarPagos(1, $desde, $hasta);
        $this->assertSame(1, $r1['nuevos']);

        $servicioSucursal2 = $this->servicioConRespuestas([new Response(200, [], json_encode(['results' => [$pagoMp]]))]);
        $r2 = $servicioSucursal2->sincronizarPagos(2, $desde, $hasta);
        $this->assertSame(1, $r2['nuevos']);

        $this->assertSame(2, \App\Models\MercadoPagoPago::count());
        $this->assertSame(1, \App\Models\MercadoPagoPago::where('sucursal_id', 1)->where('mp_payment_id', '555555')->count());
        $this->assertSame(1, \App\Models\MercadoPagoPago::where('sucursal_id', 2)->where('mp_payment_id', '555555')->count());
    }

    /** @test */
    public function sincronizar_pagos_corta_al_llegar_al_tope_de_paginas_y_lo_avisa()
    {
        $this->configConToken(1, 'TEST-TOKEN');
        $respuestas = [];
        for ($pagina = 0; $pagina < 10; $pagina++) {
            $resultados = [];
            for ($i = 0; $i < 50; $i++) {
                $resultados[] = [
                    'id' => ($pagina * 50) + $i,
                    'date_created' => '2026-06-01T10:00:00.000-03:00',
                    'transaction_amount' => 100,
                    'status' => 'approved',
                ];
            }
            $respuestas[] = new Response(200, [], json_encode(['results' => $resultados]));
        }
        $servicio = $this->servicioConRespuestas($respuestas);

        $r = $servicio->sincronizarPagos(1, \Carbon\Carbon::parse('2026-06-01'), \Carbon\Carbon::parse('2026-06-30'));

        $this->assertTrue($r['ok']);
        $this->assertSame(500, $r['total']);
        $this->assertStringContainsString('máximo', $r['mensaje']);
    }

    /** @test */
    public function resuelto_por_el_contenedor_de_laravel_usa_un_client_con_base_uri()
    {
        // Regresión: sin el binding contextual en AppServiceProvider, el
        // contenedor auto-resuelve `?Client $client = null` construyendo un
        // `new Client()` vacío (porque Client es instanciable), en vez de
        // pasar null y dejar que el constructor arme el suyo con base_uri.
        // Eso rompía toda llamada real (controllers) con
        // "The scheme "" is not allowed by the protocols request option.",
        // aunque los tests que instancian el service a mano nunca lo notaban.
        $servicio = app(MercadoPagoService::class);

        $cliente = (function () {
            return $this->client;
        })->call($servicio);

        $this->assertSame('https://api.mercadopago.com/', (string) $cliente->getConfig('base_uri'));
    }

    /** @test */
    public function crear_preferencia_sin_token_no_pega_a_la_red()
    {
        MercadoPagoConfig::create(['sucursal_id' => 1, 'activo' => true]);
        $servicio = $this->servicioConRespuestas([]);

        $r = $servicio->crearPreferencia(1, 1500.50);

        $this->assertFalse($r['ok']);
        $this->assertSame('No hay token cargado.', $r['mensaje']);
    }

    /** @test */
    public function crear_preferencia_devuelve_init_point_y_referencia()
    {
        $this->configConToken(1, 'TEST-TOKEN');
        $servicio = $this->servicioConRespuestas([
            new Response(201, [], json_encode([
                'id' => '123-abc',
                'init_point' => 'https://www.mercadopago.com.ar/checkout/v1/redirect?pref_id=123-abc',
            ])),
        ]);

        $r = $servicio->crearPreferencia(1, 1500.50);

        $this->assertTrue($r['ok']);
        $this->assertSame('https://www.mercadopago.com.ar/checkout/v1/redirect?pref_id=123-abc', $r['init_point']);
        $this->assertStringStartsWith('QR-1-', $r['ref']);
    }

    /** @test */
    public function crear_preferencia_envia_el_payload_correcto_a_mp()
    {
        $this->configConToken(1, 'TEST-TOKEN');

        $historial = [];
        $mock = new MockHandler([
            new Response(201, [], json_encode(['init_point' => 'https://mp.test/checkout'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push(\GuzzleHttp\Middleware::history($historial));
        $client = new Client(['handler' => $handlerStack, 'base_uri' => 'https://api.mercadopago.com/']);
        $servicio = new MercadoPagoService($client);

        $r = $servicio->crearPreferencia(1, 1500.505);

        $this->assertTrue($r['ok']);
        $this->assertCount(1, $historial);

        $request = $historial[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/checkout/preferences', $request->getUri()->getPath());
        $this->assertSame('Bearer TEST-TOKEN', $request->getHeaderLine('Authorization'));

        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame('ARS', $body['items'][0]['currency_id']);
        $this->assertSame(1, $body['items'][0]['quantity']);
        $this->assertSame(1500.51, $body['items'][0]['unit_price']);
        $this->assertSame($r['ref'], $body['external_reference']);
        $this->assertRegExp('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}[+-]\d{2}:\d{2}$/', $body['date_of_expiration']);
    }

    /** @test */
    public function crear_preferencia_sin_init_point_en_la_respuesta_devuelve_error_generico()
    {
        $this->configConToken(1, 'TEST-TOKEN');
        $servicio = $this->servicioConRespuestas([
            new Response(201, [], json_encode(['id' => '123-abc'])),
        ]);

        $r = $servicio->crearPreferencia(1, 100);

        $this->assertFalse($r['ok']);
        $this->assertSame('No se pudo generar el QR. Probá de nuevo.', $r['mensaje']);
    }

    /** @test */
    public function crear_preferencia_error_de_api_no_filtra_detalle()
    {
        $this->configConToken(1, 'TEST-TOKEN');
        $servicio = $this->servicioConRespuestas([
            new ClientException(
                'Bad Request',
                new Psr7Request('POST', 'checkout/preferences'),
                new Response(400, [], json_encode(['message' => 'detalle interno secreto']))
            ),
        ]);

        $r = $servicio->crearPreferencia(1, 100);

        $this->assertFalse($r['ok']);
        $this->assertStringNotContainsString('secreto', $r['mensaje']);
    }

    /** @test */
    public function buscar_pago_por_referencia_sin_token_no_pega_a_la_red()
    {
        MercadoPagoConfig::create(['sucursal_id' => 1, 'activo' => true]);
        $servicio = $this->servicioConRespuestas([]);

        $r = $servicio->buscarPagoPorReferencia(1, 'QR-1-abc');

        $this->assertFalse($r['ok']);
        $this->assertFalse($r['pagado']);
    }

    /** @test */
    public function buscar_pago_aprobado_lo_guarda_y_devuelve_pagado()
    {
        $this->configConToken(1, 'TEST-TOKEN');
        $servicio = $this->servicioConRespuestas([
            new Response(200, [], json_encode(['results' => [[
                'id' => 999888,
                'status' => 'approved',
                'date_approved' => '2026-07-01T12:00:00.000-03:00',
                'transaction_amount' => 1500.50,
                'payment_type_id' => 'account_money',
                'payer' => ['email' => 'cliente@test.com'],
            ]]])),
        ]);

        $r = $servicio->buscarPagoPorReferencia(1, 'QR-1-abc');

        $this->assertTrue($r['ok']);
        $this->assertTrue($r['pagado']);
        $this->assertSame(1500.50, $r['monto']);
        $this->assertSame('999888', $r['mp_payment_id']);
        $this->assertSame(1, \App\Models\MercadoPagoPago::count());
        $this->assertSame('approved', \App\Models\MercadoPagoPago::first()->estado);
    }

    /** @test */
    public function buscar_pago_pendiente_no_lo_guarda_y_devuelve_no_pagado()
    {
        $this->configConToken(1, 'TEST-TOKEN');
        $servicio = $this->servicioConRespuestas([
            new Response(200, [], json_encode(['results' => [[
                'id' => 999888,
                'status' => 'pending',
                'date_created' => '2026-07-01T12:00:00.000-03:00',
                'transaction_amount' => 1500.50,
            ]]])),
        ]);

        $r = $servicio->buscarPagoPorReferencia(1, 'QR-1-abc');

        $this->assertTrue($r['ok']);
        $this->assertFalse($r['pagado']);
        $this->assertSame(0, \App\Models\MercadoPagoPago::count());
    }

    /** @test */
    public function buscar_pago_sin_resultados_devuelve_no_pagado()
    {
        $this->configConToken(1, 'TEST-TOKEN');
        $servicio = $this->servicioConRespuestas([
            new Response(200, [], json_encode(['results' => []])),
        ]);

        $r = $servicio->buscarPagoPorReferencia(1, 'QR-1-inexistente');

        $this->assertTrue($r['ok']);
        $this->assertFalse($r['pagado']);
    }

    /** @test */
    public function buscar_pago_error_de_api_no_filtra_detalle()
    {
        $this->configConToken(1, 'TEST-TOKEN');
        $servicio = $this->servicioConRespuestas([
            new ClientException(
                'Unauthorized',
                new Psr7Request('GET', 'v1/payments/search'),
                new Response(401, [], json_encode(['message' => 'detalle interno secreto']))
            ),
        ]);

        $r = $servicio->buscarPagoPorReferencia(1, 'QR-1-abc');

        $this->assertFalse($r['ok']);
        $this->assertFalse($r['pagado']);
        $this->assertStringNotContainsString('secreto', $r['mensaje']);
    }
}
