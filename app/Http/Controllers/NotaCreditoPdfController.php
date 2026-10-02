<?php

namespace App\Http\Controllers;

use App\Facturacion\RegeneradorPdfNotaCredito;
use App\Models\NotaCredito;

/**
 * Botón "Regenerar PDF" del listado de notas de crédito (/notas/credito).
 * Reescribe el PDF desde la base; no emite ni toca AFIP salvo la consulta
 * de solo lectura del comprobante asociado.
 */
class NotaCreditoPdfController extends Controller
{
    use Concerns\AutorizaRolAdmin;

    private const ROL_MINIMO = 4;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function regenerar(int $id, RegeneradorPdfNotaCredito $regenerador)
    {
        $this->autorizar(self::ROL_MINIMO);

        $nota = NotaCredito::findOrFail($id);

        if (trim((string) $nota->cae) === '') {
            return response()->json([
                'ok' => false,
                'mensaje' => 'La nota de crédito '.$nota->numero.' no tiene CAE; no se regenera.',
            ], 422);
        }

        try {
            $r = $regenerador->regenerar($nota);
        } catch (\RuntimeException $e) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No se pudo regenerar la nota '.$nota->numero.': '.$e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'ok' => false,
                'mensaje' => 'No se pudo generar el PDF de la nota '.$nota->numero.'. Avise al administrador.',
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'pdf' => $nota->pdf,
            'mensaje' => 'PDF de la nota '.$nota->numero.' regenerado ('.$r['descripcion'].').'.($r['aviso'] ? ' Aviso: '.$r['aviso'] : ''),
        ]);
    }
}
