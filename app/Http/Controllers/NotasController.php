<?php

namespace App\Http\Controllers;

use App\Models\NotaCredito;
use App\Models\Usuario;
use Illuminate\Http\Request;

/**
 * Pantallas de Notas de Crédito y Débito (migradas del legacy
 * nota_credito.php / nota_debito.php). La emisión sigue en los procesadores
 * legacy probados (nota_de_credito.php / nota_de_debito.php), que estas
 * pantallas invocan por AJAX.
 */
class NotasController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /** Rol del usuario logueado (vía cookie legacy), o 403. */
    private function rolActual(): int
    {
        $kiosco = $_COOKIE['kiosco'] ?? null;
        $u = $kiosco ? Usuario::where('usuario', $kiosco)->first() : null;
        if (! $u) {
            abort(403, 'No autorizado');
        }

        return (int) $u->rol_id;
    }

    /** Reporte de Notas de Crédito (reemplaza nota_credito.php). */
    public function credito(Request $request)
    {
        if ($this->rolActual() < 4) {
            abort(403, 'No autorizado');
        }

        $data = $request->validate([
            'desde' => 'nullable|date',
            'hasta' => 'nullable|date',
        ]);
        $desde = $data['desde'] ?? null;
        $hasta = $data['hasta'] ?? null;

        $q = NotaCredito::leftJoin('sucursales', 'sucursales.id', '=', 'nota_de_credito.sucursal_id')
            ->select('nota_de_credito.*', 'sucursales.nombre as nombre_sucursal');

        if ($desde) {
            $q->whereDate('nota_de_credito.fecha', '>=', $desde);
        }
        if ($hasta) {
            $q->whereDate('nota_de_credito.fecha', '<=', $hasta);
        }

        $notas = $q->orderBy('nota_de_credito.fecha', 'desc')->get();

        return view('notas.credito', compact('notas', 'desde', 'hasta'));
    }

    /** Pantalla para generar una Nota de Débito a partir de una NC (reemplaza nota_debito.php). */
    public function debito(Request $request)
    {
        $rol = $this->rolActual();
        // Legacy: permite rol >= 4 o rol == 1 (Venta).
        if ($rol < 4 && $rol !== 1) {
            abort(403, 'No autorizado');
        }

        $notasCredito = NotaCredito::where('cae', '<>', '')
            ->where('fechacae', '<>', '')
            ->orderBy('fecha', 'desc')
            ->get();

        return view('notas.debito', compact('notasCredito'));
    }
}
