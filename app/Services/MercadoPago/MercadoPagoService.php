<?php

namespace App\Services\MercadoPago;

use App\Models\MercadoPagoConfig;
use App\Models\MercadoPagoPago;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class MercadoPagoService
{
    private Client $client;

    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? new Client(['base_uri' => 'https://api.mercadopago.com/']);
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
        } catch (RequestException $e) {
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
                    $registro = MercadoPagoPago::updateOrCreate(
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
        } catch (RequestException $e) {
            Log::error('MercadoPago sincronizarPagos falló', ['sucursal_id' => $sucursalId, 'error' => $e->getMessage()]);

            return ['ok' => false, 'mensaje' => 'No se pudo sincronizar con Mercado Pago.', 'nuevos' => $nuevos, 'total' => $total];
        }

        $mensaje = "Se sincronizaron {$total} pagos ({$nuevos} nuevos).";
        if ($cortado) {
            $mensaje .= ' Se alcanzó el máximo de 500 pagos por corrida; achicá el rango de fechas para traer el resto.';
        }

        return ['ok' => true, 'mensaje' => $mensaje, 'nuevos' => $nuevos, 'total' => $total];
    }
}
