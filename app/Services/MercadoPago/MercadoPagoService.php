<?php

namespace App\Services\MercadoPago;

use App\Models\MercadoPagoConfig;
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
}
