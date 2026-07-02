<?php

namespace App\Http\Controllers;

use App\Models\Sucursales;
use App\Services\MercadoPago\MercadoPagoService;
use Illuminate\Http\Request;

class MercadoPagoQrController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /** Crea un link de pago por el total de la venta en curso, para la sucursal de la sesión. */
    public function crear(Request $request, MercadoPagoService $mp)
    {
        $data = $request->validate([
            'monto' => 'required|numeric|min:0.01|max:99999999',
        ]);

        $sucursalId = Sucursales::getSucursal();

        return response()->json($mp->crearPreferencia($sucursalId, (float) $data['monto']));
    }

    /** Estado del cobro: ¿ya hay un pago aprobado con esta referencia? */
    public function estado(Request $request, MercadoPagoService $mp)
    {
        $data = $request->validate([
            'ref' => 'required|string|max:100',
        ]);

        $sucursalId = Sucursales::getSucursal();

        return response()->json($mp->buscarPagoPorReferencia($sucursalId, $data['ref']));
    }
}
