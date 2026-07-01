<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AutorizaRolAdmin;
use App\Models\MercadoPagoConfig;
use App\Models\Sucursales;
use App\Services\MercadoPago\MercadoPagoService;
use Illuminate\Http\Request;

class MercadoPagoConfigController extends Controller
{
    use AutorizaRolAdmin;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->autorizar();

        $sucursales = Sucursales::orderBy('nombre')->get();
        $configs = $sucursales->mapWithKeys(function ($sucursal) {
            return [$sucursal->id => MercadoPagoConfig::firstOrCreate(['sucursal_id' => $sucursal->id])];
        });

        return view('mercadopago.configuracion', compact('sucursales', 'configs'));
    }

    public function guardar(Request $request, int $sucursal_id)
    {
        $this->autorizar();

        $data = $request->validate([
            'access_token' => 'nullable|string|max:255',
            'public_key' => 'nullable|string|max:255',
            'activo' => 'sometimes|boolean',
        ]);

        $config = MercadoPagoConfig::firstOrCreate(['sucursal_id' => $sucursal_id]);

        if (! empty($data['access_token'])) {
            $config->access_token = $data['access_token'];
        }
        $config->public_key = $data['public_key'] ?? $config->public_key;
        $config->activo = $request->boolean('activo');
        $config->save();

        return back()->with('mp_msg', 'Configuración guardada.');
    }

    public function probar(int $sucursal_id, MercadoPagoService $mp)
    {
        $this->autorizar();

        $r = $mp->probarConexion($sucursal_id);

        return back()->with($r['ok'] ? 'mp_msg' : 'mp_error', $r['mensaje']);
    }
}
