<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ReferenciaDuplicada;
use App\Exceptions\VentaRechazada;
use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\Stock_log;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * POST /api/auth/productos/venta
 *
 * Descuenta stock de un pedido del e-commerce. Es una VENTA (resta), no un "poner
 * stock en N", así dos pedidos simultáneos no se pisan.
 *
 * - Todo o nada: todos los ítems se validan bajo bloqueo; si uno falla no se descuenta ninguno.
 * - Idempotente por `referencia` (por usuario de API): un reintento con la misma referencia
 *   ya confirmada responde ok sin descontar otra vez.
 */
class VentaController extends Controller
{
    public function venta(Request $request)
    {
        $validador = Validator::make($request->all(), [
            'referencia' => 'required|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.codigo_barras' => 'required|string|max:100',
            'items.*.cantidad' => 'required|integer|min:1|max:100000',
        ]);
        if ($validador->fails()) {
            return response()->json([
                'ok' => false,
                'motivo' => 'datos_invalidos',
                'errores' => $validador->errors(),
            ], 422);
        }

        $usuario = $request->user();
        $referencia = $request->input('referencia');

        if ($this->yaConfirmada($usuario->id, $referencia)) {
            return response()->json(['ok' => true]);
        }

        // Mismo código repetido en dos ítems se suma; ksort fija el orden de bloqueo.
        $pedido = [];
        foreach ($request->input('items') as $item) {
            $codigo = $item['codigo_barras'];
            $pedido[$codigo] = ($pedido[$codigo] ?? 0) + (int) $item['cantidad'];
        }
        ksort($pedido);

        try {
            DB::transaction(function () use ($usuario, $referencia, $pedido) {
                $this->descontar($usuario, $referencia, $pedido);
            });
        } catch (ReferenciaDuplicada $e) {
            // Otro pedido con la misma referencia confirmó en paralelo.
            return response()->json(['ok' => true]);
        } catch (VentaRechazada $e) {
            return response()->json([
                'ok' => false,
                'motivo' => $e->motivo,
                'items' => $e->items,
            ], 409);
        }

        return response()->json(['ok' => true]);
    }

    private function yaConfirmada($userId, string $referencia): bool
    {
        return DB::table('api_ventas_referencias')
            ->where('user_id', $userId)
            ->where('referencia', $referencia)
            ->exists();
    }

    /**
     * Dentro de una transacción. Si algo falla lanza excepción y se deshace todo,
     * incluida la fila de referencia (así un reintento tras reponer stock sí procesa).
     */
    private function descontar($usuario, string $referencia, array $pedido): void
    {
        try {
            DB::table('api_ventas_referencias')->insert([
                'user_id' => $usuario->id,
                'referencia' => $referencia,
                'created_at' => now(),
            ]);
        } catch (QueryException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false
                || strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                throw new ReferenciaDuplicada($referencia);
            }
            throw $e;
        }

        $productos = Producto::whereIn('codigo_barras', array_keys($pedido))
            ->orderBy('codigo_barras')
            ->lockForUpdate()
            ->get()
            ->keyBy('codigo_barras');

        $faltantes = [];
        $desconocido = false;
        foreach ($pedido as $codigo => $cantidad) {
            $producto = $productos->get($codigo);
            if ($producto === null) {
                $desconocido = true;
                $faltantes[] = ['codigo_barras' => $codigo, 'disponible' => 0];
            } elseif ((int) $producto->stock < $cantidad) {
                $faltantes[] = ['codigo_barras' => $codigo, 'disponible' => (int) $producto->stock];
            }
        }
        if ($faltantes) {
            throw new VentaRechazada($desconocido ? 'codigo_desconocido' : 'sin_stock', $faltantes);
        }

        // La sucursal del log sólo se completa si el usuario de API tiene exactamente una.
        $sucursales = DB::table('relacion_users_sucursales')
            ->where('user_id', $usuario->id)
            ->pluck('sucursal_id')
            ->unique();
        $sucursalId = $sucursales->count() === 1 ? $sucursales->first() : null;

        foreach ($pedido as $codigo => $cantidad) {
            $producto = $productos->get($codigo);
            $anterior = (int) $producto->stock;
            $producto->stock = $anterior - $cantidad;
            $producto->save();

            $log = new Stock_log();
            $log->productos_id = $producto->id;
            $log->sucursal_id = $sucursalId;
            $log->stock_anterior = $anterior;
            $log->stock_minimo_anterior = $producto->stock_minimo;
            $log->stock = $producto->stock;
            $log->stock_minimo = $producto->stock_minimo;
            $log->usuario = mb_substr('API e-commerce: ' . $usuario->name, 0, 200);
            $log->tipo_operacion = 'Venta e-commerce';
            $log->barra = $codigo;
            $log->created_at = now();
            $log->updated_at = now();
            $log->save();
        }
    }
}
