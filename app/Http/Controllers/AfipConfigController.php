<?php

namespace App\Http\Controllers;

use App\Models\AfipConfig;
use App\Models\Usuario;
use App\Services\Afip\AfipService;
use Illuminate\Http\Request;

class AfipConfigController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /** Gate por rol (>= 2), replicando configuracion_afip.php legacy. */
    private function autorizar(): void
    {
        $kiosco = $_COOKIE['kiosco'] ?? null;
        $u = $kiosco ? Usuario::where('usuario', $kiosco)->first() : null;
        if (! $u || (int) $u->rol_id < 2) {
            abort(403, 'No autorizado');
        }
    }

    public function index(AfipService $afip)
    {
        $this->autorizar();

        $entornos = [
            'homo' => AfipConfig::firstOrCreate(['entorno' => 'homo']),
            'prod' => AfipConfig::firstOrCreate(['entorno' => 'prod']),
        ];

        $tipos = [
            'homo' => $afip->tiposComprobante('homo'),
            'prod' => $afip->tiposComprobante('prod'),
        ];

        $activo = AfipConfig::activa();

        return view('afip.configuracion', compact('entornos', 'tipos', 'activo'));
    }

    public function guardar(Request $request, string $entorno)
    {
        $this->autorizar();

        $data = $request->validate([
            'cuit' => 'nullable|digits:11',
            'ptovta' => 'nullable|integer',
            'comprobante' => 'nullable|integer',
            'condicion_iva' => 'nullable|string|max:50',
            'inicio_actividades' => 'nullable|string|max:20',
            'ingresos_brutos' => 'nullable|string|max:50',
            'emitir' => 'sometimes|boolean',
            'solicitar_datos' => 'sometimes|boolean',
        ]);

        $data['emitir'] = $request->boolean('emitir');
        $data['solicitar_datos'] = $request->boolean('solicitar_datos');

        AfipConfig::where('entorno', $entorno)->update($data);

        return back()->with('afip_msg', "Datos de {$entorno} guardados.");
    }

    public function subirCredenciales(Request $request, string $entorno, AfipService $afip)
    {
        $this->autorizar();

        $request->validate([
            'cert' => 'required|string',
            'key' => 'required|string',
        ]);

        try {
            $afip->guardarCredenciales($entorno, $request->input('cert'), $request->input('key'));
            return back()->with('afip_msg', "Credenciales de {$entorno} guardadas.");
        } catch (\InvalidArgumentException $e) {
            return back()->with('afip_error', $e->getMessage());
        }
    }

    public function activar(Request $request)
    {
        $this->autorizar();

        $data = $request->validate(['entorno' => 'required|in:homo,prod']);
        AfipConfig::activar($data['entorno']);

        return back()->with('afip_msg', "Entorno activo: {$data['entorno']}.");
    }

    public function probar(string $entorno, AfipService $afip)
    {
        $this->autorizar();

        $r = $afip->probar($entorno);

        return back()->with($r['ok'] ? 'afip_msg' : 'afip_error', $r['mensaje']);
    }
}
