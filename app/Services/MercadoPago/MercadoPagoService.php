<?php

namespace App\Services\MercadoPago;

use App\Models\MercadoPagoConfig;
use App\Models\MercadoPagoPago;
use App\Models\Sucursales;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class MercadoPagoService
{
    private Client $client;

    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? new Client([
            'base_uri' => 'https://api.mercadopago.com/',
            'timeout' => 10,
            'connect_timeout' => 5,
        ]);
    }

    /** Prueba que el Access Token de la sucursal sea válido contra GET /users/me. */
    public function probarConexion(int $sucursalId): array
    {
        $config = MercadoPagoConfig::where('sucursal_id', $sucursalId)->first();

        if (! $config || ! $config->access_token) {
            return ['ok' => false, 'mensaje' => 'No hay token cargado.'];
        }

        try {
            $res = $this->client->request('GET', 'users/me', [
                'headers' => ['Authorization' => 'Bearer '.$config->access_token],
            ]);
            $data = json_decode((string) $res->getBody(), true);
            $nick = $data['nickname'] ?? $data['email'] ?? 'cuenta';

            return ['ok' => true, 'mensaje' => "Conexión OK ({$nick})."];
        } catch (GuzzleException $e) {
            Log::error('MercadoPago probarConexion falló', ['sucursal_id' => $sucursalId, 'error' => $e->getMessage()]);

            return ['ok' => false, 'mensaje' => 'No se pudo conectar con Mercado Pago. Verificá el token.'];
        }
    }

    /** Trae cobros nuevos desde la API de pagos de Mercado Pago y los cachea localmente. */
    public function sincronizarPagos(int $sucursalId, Carbon $desde, Carbon $hasta): array
    {
        $config = MercadoPagoConfig::where('sucursal_id', $sucursalId)->first();

        if (! $config || ! $config->access_token) {
            return ['ok' => false, 'mensaje' => 'No hay token cargado.', 'nuevos' => 0, 'total' => 0];
        }

        $limit = 50;
        $maxPaginas = 10;
        $nuevos = 0;
        $total = 0;
        $cortado = false;

        try {
            for ($pagina = 0; $pagina < $maxPaginas; $pagina++) {
                $res = $this->client->request('GET', 'v1/payments/search', [
                    'headers' => ['Authorization' => 'Bearer '.$config->access_token],
                    'query' => [
                        'range' => 'date_created',
                        'begin_date' => $desde->format('Y-m-d\TH:i:s.000P'),
                        'end_date' => $hasta->format('Y-m-d\TH:i:s.000P'),
                        'sort' => 'date_created',
                        'criteria' => 'desc',
                        'limit' => $limit,
                        'offset' => $pagina * $limit,
                    ],
                ]);
                $data = json_decode((string) $res->getBody(), true);
                $resultados = $data['results'] ?? [];
                $total += count($resultados);

                foreach ($resultados as $pago) {
                    $registro = $this->guardarPago($sucursalId, $pago);
                    if ($registro->wasRecentlyCreated) {
                        $nuevos++;
                    }
                }

                if (count($resultados) < $limit) {
                    break;
                }
                if ($pagina === $maxPaginas - 1) {
                    $cortado = true;
                }
            }
        } catch (GuzzleException $e) {
            Log::error('MercadoPago sincronizarPagos falló', ['sucursal_id' => $sucursalId, 'error' => $e->getMessage()]);

            return ['ok' => false, 'mensaje' => 'No se pudo sincronizar con Mercado Pago.', 'nuevos' => $nuevos, 'total' => $total];
        }

        $mensaje = "Se sincronizaron {$total} pagos ({$nuevos} nuevos).";
        if ($cortado) {
            $mensaje .= ' Se alcanzó el máximo de 500 pagos por corrida; achicá el rango de fechas para traer el resto.';
        }

        return ['ok' => true, 'mensaje' => $mensaje, 'nuevos' => $nuevos, 'total' => $total];
    }

    /** Genera un link de pago (Checkout Pro) por el monto exacto y devuelve la URL para el QR. */
    public function crearPreferencia(int $sucursalId, float $monto): array
    {
        $config = MercadoPagoConfig::where('sucursal_id', $sucursalId)->first();

        if (! $config || ! $config->access_token) {
            // config_pendiente distingue "falta configurar" de otros errores:
            // el front ofrece abrir la guía en vez de un alert seco.
            return [
                'ok' => false,
                'mensaje' => 'No hay token cargado.',
                'config_pendiente' => true,
                'ayuda' => '/ayuda/mercadopago.html',
            ];
        }

        $ref = 'QR-'.$sucursalId.'-'.uniqid();

        try {
            $res = $this->client->request('POST', 'checkout/preferences', [
                'headers' => ['Authorization' => 'Bearer '.$config->access_token],
                'json' => [
                    'items' => [[
                        'title' => $this->tituloVenta($sucursalId),
                        'quantity' => 1,
                        'unit_price' => round($monto, 2),
                        'currency_id' => 'ARS',
                    ]],
                    'external_reference' => $ref,
                    // La API de preferencias usa expires/expiration_date_to
                    // (date_of_expiration es de la API de pagos y MP lo ignora acá).
                    'expires' => true,
                    'expiration_date_to' => Carbon::now()->addMinutes(30)->format('Y-m-d\TH:i:s.000P'),
                ],
            ]);
            $data = json_decode((string) $res->getBody(), true);

            if (empty($data['init_point'])) {
                Log::error('MercadoPago crearPreferencia sin init_point', ['sucursal_id' => $sucursalId]);

                return ['ok' => false, 'mensaje' => 'No se pudo generar el QR. Probá de nuevo.'];
            }

            return ['ok' => true, 'ref' => $ref, 'init_point' => $data['init_point']];
        } catch (GuzzleException $e) {
            Log::error('MercadoPago crearPreferencia falló', ['sucursal_id' => $sucursalId, 'error' => $e->getMessage()]);

            return ['ok' => false, 'mensaje' => 'No se pudo generar el QR. Probá de nuevo.'];
        }
    }

    /** Consulta si ya hay un pago aprobado con esa referencia; si lo hay, lo cachea localmente. */
    public function buscarPagoPorReferencia(int $sucursalId, string $ref): array
    {
        $config = MercadoPagoConfig::where('sucursal_id', $sucursalId)->first();

        if (! $config || ! $config->access_token) {
            return ['ok' => false, 'pagado' => false, 'mensaje' => 'No hay token cargado.'];
        }

        try {
            $res = $this->client->request('GET', 'v1/payments/search', [
                'headers' => ['Authorization' => 'Bearer '.$config->access_token],
                'query' => [
                    'external_reference' => $ref,
                    'sort' => 'date_created',
                    'criteria' => 'desc',
                ],
            ]);
            $data = json_decode((string) $res->getBody(), true);

            foreach ($data['results'] ?? [] as $pago) {
                if (($pago['status'] ?? '') === 'approved') {
                    $registro = $this->guardarPago($sucursalId, $pago);

                    return [
                        'ok' => true,
                        'pagado' => true,
                        'monto' => (float) $registro->monto,
                        'mp_payment_id' => $registro->mp_payment_id,
                    ];
                }
            }

            return ['ok' => true, 'pagado' => false];
        } catch (GuzzleException $e) {
            Log::error('MercadoPago buscarPagoPorReferencia falló', ['sucursal_id' => $sucursalId, 'error' => $e->getMessage()]);

            return ['ok' => false, 'pagado' => false, 'mensaje' => 'No se pudo consultar el estado del pago.'];
        }
    }

    /** Upsert de un pago de MP en la cache local (clave compuesta sucursal + mp_payment_id). */
    private function guardarPago(int $sucursalId, array $pago): MercadoPagoPago
    {
        return MercadoPagoPago::updateOrCreate(
            ['sucursal_id' => $sucursalId, 'mp_payment_id' => (string) $pago['id']],
            [
                'fecha' => $pago['date_approved'] ?? $pago['date_created'],
                'monto' => $pago['transaction_amount'] ?? 0,
                'monto_neto' => $pago['transaction_details']['net_received_amount'] ?? null,
                'estado' => $pago['status'] ?? 'unknown',
                'medio_pago' => $pago['payment_type_id'] ?? null,
                'comprador' => $pago['payer']['email'] ?? null,
                'payload_raw' => $pago,
            ]
        );
    }

    /** Título del ítem del checkout. `sucursales` es tabla legacy sin migración: en tests no existe -> fallback. */
    private function tituloVenta(int $sucursalId): string
    {
        try {
            $nombre = Sucursales::where('id', $sucursalId)->value('nombre');

            return $nombre ? "Venta {$nombre}" : 'Venta';
        } catch (\Throwable $e) {
            return 'Venta';
        }
    }
}
