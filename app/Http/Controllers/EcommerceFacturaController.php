<?php

namespace App\Http\Controllers;

use App\Facturacion\FacturacionEcommerce;
use App\Http\Controllers\Concerns\AutorizaRolAdmin;

/**
 * Pantalla "Ventas e-commerce": lista los pedidos del web service y permite
 * facturarlos. Mismo rol mínimo que la facturación manual.
 */
class EcommerceFacturaController extends Controller
{
    use AutorizaRolAdmin;

    private const ROL_MINIMO = 4;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(FacturacionEcommerce $facturacion)
    {
        $this->autorizar(self::ROL_MINIMO);

        return view('ventas_ecommerce.index', ['pedidos' => $facturacion->pedidos()]);
    }

    public function facturar(int $id, FacturacionEcommerce $facturacion)
    {
        $this->autorizar(self::ROL_MINIMO);

        try {
            $r = $facturacion->facturar($id);
        } catch (\RuntimeException $e) {
            return back()->with('error', 'No se facturó el pedido: '.$e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'No se pudo facturar el pedido. Avisar al administrador.');
        }

        $mensaje = 'Factura '.$r['numero'].' emitida (CAE '.$r['cae'].').';

        return back()->with('ok', $r['aviso'] ? $mensaje.' '.$r['aviso'] : $mensaje);
    }

    public function liberar(int $id, FacturacionEcommerce $facturacion)
    {
        $this->autorizar(self::ROL_MINIMO);

        try {
            $facturacion->liberar($id);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('ok', 'Bloqueo liberado: el pedido puede volver a facturarse.');
    }
}
