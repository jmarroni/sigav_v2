<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AutorizaRolAdmin;
use App\Models\MercadoPagoConfig;
use App\Models\MercadoPagoPago;
use App\Models\Sucursales;
use App\Services\MercadoPago\MercadoPagoService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MercadoPagoMovimientosController extends Controller
{
    use AutorizaRolAdmin;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $esAdmin = $this->tieneRol();

        $sucursalId = $esAdmin && $request->filled('sucursal_id')
            ? (int) $request->input('sucursal_id')
            : Sucursales::getSucursal();

        $desde = $request->filled('desde') ? Carbon::parse($request->input('desde')) : now()->subDays(30);
        $hasta = $request->filled('hasta') ? Carbon::parse($request->input('hasta')) : now();

        $pagos = MercadoPagoPago::where('sucursal_id', $sucursalId)
            ->whereBetween('fecha', [$desde->copy()->startOfDay(), $hasta->copy()->endOfDay()])
            ->orderBy('fecha', 'desc')
            ->paginate(25)
            ->appends($request->query());

        $config = MercadoPagoConfig::where('sucursal_id', $sucursalId)->first();
        $sucursales = $esAdmin ? Sucursales::orderBy('nombre')->get() : collect();

        return view('mercadopago.movimientos', compact('pagos', 'config', 'sucursales', 'sucursalId', 'esAdmin', 'desde', 'hasta'));
    }

    public function sincronizar(Request $request, int $sucursal_id, MercadoPagoService $mp)
    {
        if ($sucursal_id !== Sucursales::getSucursal()) {
            $this->autorizar();
        }

        $request->validate([
            'desde' => 'nullable|date',
            'hasta' => 'nullable|date',
        ]);

        $desde = $request->filled('desde') ? Carbon::parse($request->input('desde')) : now()->subDays(30);
        $hasta = $request->filled('hasta') ? Carbon::parse($request->input('hasta')) : now();

        if ($desde->diffInDays($hasta) > 90) {
            return back()->with('mp_error', 'El rango máximo por sincronización es de 90 días.');
        }

        $r = $mp->sincronizarPagos($sucursal_id, $desde, $hasta);

        return back()->with($r['ok'] ? 'mp_msg' : 'mp_error', $r['mensaje']);
    }
}
