<?php

namespace App\Http\Controllers;

use App\Facturacion\RegeneradorPdfFactura;
use App\Models\Factura;

/**
 * Botón "Regenerar PDF" del reporte de facturación (reportes/factura.blade.php).
 * Vuelve a escribir el PDF de una factura ya emitida desde la base; no factura
 * ni toca AFIP. Mismo rol mínimo que perfil y usuarios de la API.
 */
class FacturaPdfController extends Controller
{
    use Concerns\AutorizaRolAdmin;

    private const ROL_MINIMO = 4;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function regenerar(int $id, RegeneradorPdfFactura $regenerador)
    {
        $this->autorizar(self::ROL_MINIMO);

        $factura = Factura::findOrFail($id);

        // Solo comprobantes autorizados por AFIP: un presupuesto o una factura
        // sin CAE no puede imprimirse como comprobante electrónico válido.
        if ((int) $factura->presupuesto === 1 || trim((string) $factura->cae) === '') {
            return response()->json([
                'ok' => false,
                'mensaje' => 'La factura '.$factura->numero.' no tiene CAE; no se regenera.',
            ], 422);
        }

        try {
            $regenerador->regenerar($factura);
        } catch (\RuntimeException $e) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No se pudo regenerar la factura '.$factura->numero.': '.$e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            report($e); // el detalle (rutas, SQL) va al log, no al navegador
            return response()->json([
                'ok' => false,
                'mensaje' => 'No se pudo generar el PDF de la factura '.$factura->numero.'. Avise al administrador.',
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'pdf' => $factura->pdf,
            'mensaje' => 'PDF de la factura '.$factura->numero.' regenerado.',
        ]);
    }
}
