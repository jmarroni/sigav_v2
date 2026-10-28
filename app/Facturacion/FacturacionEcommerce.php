<?php

namespace App\Facturacion;

use App\Models\AfipConfig;
use App\Models\Factura;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Factura un pedido del e-commerce (un comprobante por pedido, Factura C).
 *
 * No toca carrito ni stock: el stock ya se descontó al recibir la venta. Sólo
 * pide el comprobante a AFIP, guarda `factura`, marca los renglones de `ventas`
 * como facturados (estado 2) y genera el PDF con RegeneradorPdfFactura.
 *
 * Seguridad ante reintentos: un pedido tiene un bloqueo (facturando_at) que se
 * toma antes de llamar a AFIP. Si AFIP responde con error se libera; si la
 * llamada queda en duda (timeout) el bloqueo se mantiene: puede que AFIP ya haya
 * autorizado el comprobante y un reintento emitiría otro.
 */
class FacturacionEcommerce
{
    /** @var EmisorComprobanteAfip */
    private $emisor;

    /** @var RegeneradorPdfFactura */
    private $pdf;

    public function __construct(EmisorComprobanteAfip $emisor, RegeneradorPdfFactura $pdf)
    {
        $this->emisor = $emisor;
        $this->pdf = $pdf;
    }

    /**
     * @return array{factura_id:int, numero:string, cae:string, aviso:?string}
     * @throws \RuntimeException con un motivo legible para el operador
     */
    public function facturar(int $pedidoId): array
    {
        $sucursalId = (int) config('ecommerce.sucursal_id');
        $sucursal = $sucursalId > 0 ? DB::table('sucursales')->where('id', $sucursalId)->first() : null;
        if ($sucursal === null) {
            throw new \RuntimeException('no hay sucursal e-commerce configurada (ECOMMERCE_SUCURSAL_ID).');
        }
        $ptoVta = (int) $sucursal->pto_vta;
        if ($ptoVta <= 0) {
            throw new \RuntimeException('la sucursal e-commerce no tiene punto de venta AFIP cargado.');
        }

        $pedido = DB::table('api_ventas_referencias')->where('id', $pedidoId)->first();
        if ($pedido === null) {
            throw new \RuntimeException('el pedido no existe.');
        }
        if ($pedido->factura_id) {
            throw new \RuntimeException('el pedido ya está facturado.');
        }

        $reclamado = DB::table('api_ventas_referencias')
            ->where('id', $pedidoId)
            ->whereNull('factura_id')
            ->whereNull('facturando_at')
            ->update(['facturando_at' => now()]);
        if ($reclamado !== 1) {
            throw new \RuntimeException('el pedido se está facturando o quedó bloqueado: revisá en AFIP antes de liberarlo.');
        }

        // Desde acá, cualquier error de validación libera el bloqueo (todavía no se llamó a AFIP).
        try {
            $lineas = DB::table('ventas')
                ->where('referencia_api', $pedido->referencia)
                ->where('sucursal_id', $sucursalId)
                ->where('estado', 1)
                ->whereNull('factura_id')
                ->orderBy('id')
                ->get();
            if ($lineas->isEmpty()) {
                throw new \RuntimeException('el pedido no tiene renglones sin facturar.');
            }

            $totalCentavos = 0;
            foreach ($lineas as $linea) {
                $totalCentavos += (int) round((float) $linea->precio * 100) * (int) $linea->cantidad;
            }
            if ($totalCentavos <= 0) {
                throw new \RuntimeException('el total del pedido es cero.');
            }
            $total = number_format($totalCentavos / 100, 2, '.', '');

            $config = AfipConfig::activa();
            if ($config === null) {
                throw new \RuntimeException('no hay entorno AFIP activo.');
            }
            $cbteTipo = (int) $config->comprobante;
            if ($cbteTipo !== 11) {
                throw new \RuntimeException('la facturación del e-commerce sólo emite Factura C (tipo 11).');
            }

            $conDocumento = $pedido->cliente_tipo_documento !== null;
            $docTipo = $conDocumento ? (int) $pedido->cliente_tipo_documento : 99;
            $docNro = $conDocumento ? (int) $pedido->cliente_documento : 0;
            $condicionIva = $pedido->cliente_condicion_iva ?? 4;

            $data = [
                'CantReg' => 1,
                'PtoVta' => $ptoVta,
                'CbteTipo' => $cbteTipo,
                'Concepto' => 1,
                'DocTipo' => $docTipo,
                'DocNro' => $docNro,
                'CbteFch' => now()->format('Ymd'),
                'ImpTotal' => $total,
                'ImpTotConc' => 0,
                'ImpNeto' => $total,
                'ImpOpEx' => 0,
                'ImpIVA' => 0,
                'ImpTrib' => 0,
                'MonId' => 'PES',
                'MonCotiz' => 1,
                'CondicionIVAReceptorId' => $this->condicionReceptor((int) $condicionIva),
            ];
        } catch (\RuntimeException $e) {
            $this->liberar($pedidoId);
            throw $e;
        }

        try {
            $res = $this->emisor->emitir($data);
        } catch (\Throwable $e) {
            // Duda: no se libera. Ver el docblock de la clase.
            report($e);
            throw new \RuntimeException('AFIP no confirmó la factura del pedido (pedido '.$pedido->referencia.'). Verificá en AFIP si se emitió antes de liberar el bloqueo.');
        }

        $numero = str_pad((string) $res['voucher_number'], 6, '0', STR_PAD_LEFT);
        $pdfRuta = '/facturas/'.$ptoVta.'_'.$res['CAE'].'_'.$numero.'.pdf';
        $ids = $lineas->pluck('id')->all();

        try {
            $facturaId = DB::transaction(function () use ($sucursalId, $numero, $res, $total, $pdfRuta, $docNro, $docTipo, $condicionIva, $pedido, $ids, $pedidoId) {
                $facturaId = DB::table('factura')->insertGetId([
                    'sucursal_id' => $sucursalId,
                    'fecha' => now()->format('Y-m-d H:i:s'),
                    'usuario' => 'API e-commerce',
                    'numero' => $numero,
                    'cae' => $res['CAE'],
                    'fechacae' => $res['CAEFchVto'],
                    'total' => $total,
                    'pdf' => $pdfRuta,
                    'presupuesto' => 0,
                    'nro_presupuesto' => 0,
                    'nombre' => $pedido->cliente_nombre ?: 'CONSUMIDOR FINAL',
                    'direccion' => '',
                    'documento' => (string) $docNro,
                    'tipo_documento' => (string) $docTipo,
                    'iva' => (string) $condicionIva,
                    'descuento_total' => 0,
                ]);
                DB::table('ventas')->whereIn('id', $ids)->update(['factura_id' => $facturaId, 'estado' => 2]);
                DB::table('api_ventas_referencias')->where('id', $pedidoId)
                    ->update(['factura_id' => $facturaId, 'facturando_at' => null]);

                return $facturaId;
            });
        } catch (\Throwable $e) {
            report($e);
            Log::critical('Factura e-commerce autorizada por AFIP pero no guardada', [
                'pedido' => $pedido->referencia, 'numero' => $numero, 'cae' => $res['CAE'],
            ]);
            throw new \RuntimeException("AFIP autorizó la factura $numero (CAE {$res['CAE']}) pero no se pudo guardar en el sistema. Avisar al administrador; no reintentar.");
        }

        $aviso = null;
        try {
            $this->pdf->regenerar(Factura::find($facturaId));
        } catch (\Throwable $e) {
            report($e);
            $aviso = 'La factura se emitió, pero no se pudo generar el PDF: usá "Regenerar PDF" en el reporte de facturación.';
        }

        return ['factura_id' => $facturaId, 'numero' => $numero, 'cae' => $res['CAE'], 'aviso' => $aviso];
    }

    /** Libera el bloqueo de un pedido que no quedó confirmado en AFIP. */
    public function liberar(int $pedidoId): void
    {
        $n = DB::table('api_ventas_referencias')
            ->where('id', $pedidoId)
            ->whereNull('factura_id')
            ->update(['facturando_at' => null]);
        if ($n !== 1) {
            throw new \RuntimeException('el pedido no existe o ya está facturado.');
        }
    }

    /**
     * Pedidos del e-commerce con sus renglones y total, para la pantalla de facturación.
     *
     * @return array<int, array<string, mixed>>
     */
    public function pedidos(): array
    {
        $sucursalId = (int) config('ecommerce.sucursal_id');
        if ($sucursalId <= 0) {
            return [];
        }

        $lineas = DB::table('ventas')
            ->join('productos', 'productos.id', '=', 'ventas.productos_id')
            ->where('ventas.sucursal_id', $sucursalId)
            ->whereNotNull('ventas.referencia_api')
            ->orderBy('ventas.id')
            ->get(['ventas.referencia_api', 'ventas.cantidad', 'ventas.precio', 'ventas.factura_id', 'ventas.fecha', 'productos.nombre']);

        $porReferencia = [];
        foreach ($lineas as $linea) {
            $ref = $linea->referencia_api;
            $porReferencia[$ref]['renglones'][] = $linea->cantidad.' × '.$linea->nombre;
            $porReferencia[$ref]['centavos'] = ($porReferencia[$ref]['centavos'] ?? 0)
                + (int) round((float) $linea->precio * 100) * (int) $linea->cantidad;
            $porReferencia[$ref]['fecha'] = $linea->fecha;
            $porReferencia[$ref]['factura_id'] = $linea->factura_id;
        }

        $pedidos = DB::table('api_ventas_referencias')
            ->whereIn('referencia', array_keys($porReferencia))
            ->orderByDesc('id')
            ->get();

        $salida = [];
        foreach ($pedidos as $pedido) {
            $datos = $porReferencia[$pedido->referencia] ?? null;
            if ($datos === null) {
                continue;
            }
            $salida[] = [
                'id' => $pedido->id,
                'referencia' => $pedido->referencia,
                'fecha' => $datos['fecha'],
                'renglones' => $datos['renglones'],
                'total' => number_format($datos['centavos'] / 100, 2, ',', '.'),
                'cliente' => $pedido->cliente_nombre ?: 'Consumidor final',
                'facturado' => $pedido->factura_id !== null,
                'bloqueado' => $pedido->facturando_at !== null && $pedido->factura_id === null,
            ];
        }

        return $salida;
    }

    /** Condición IVA del receptor (RG 5616): mismo mapeo que public/afip_bridge.php. */
    private function condicionReceptor(int $iva): int
    {
        switch ($iva) {
            case 1: return 1;
            case 2: return 6;
            case 3: return 4;
            default: return 5;
        }
    }
}
