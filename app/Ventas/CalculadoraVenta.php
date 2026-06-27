<?php

declare(strict_types=1);

namespace App\Ventas;

/**
 * Cálculo puro de una venta con descuentos porcentuales apilados.
 * Sin DB ni cookies: única fuente de la matemática de venta + IVA.
 */
class CalculadoraVenta
{
    /** Alícuota de IVA en %. */
    private const IVA_PCT = 21.0;

    /**
     * @param array $lineas Lista de ['precio'=>float, 'cantidad'=>int|float, 'descuento'=>float(%)]
     * @param float|mixed $descuentoTotalPct % de descuento sobre el subtotal ya descontado por línea
     * @return array
     */
    public static function calcular(array $lineas, $descuentoTotalPct): array
    {
        $subtotalBruto = 0.0;
        $subtotal = 0.0;
        $detalle = [];

        foreach ($lineas as $linea) {
            $precio    = self::num(isset($linea['precio']) ? $linea['precio'] : 0);
            $cantidad  = self::num(isset($linea['cantidad']) ? $linea['cantidad'] : 0);
            $descuento = self::clampPct(isset($linea['descuento']) ? $linea['descuento'] : 0);

            $bruto = round($precio * $cantidad, 2);
            $lineaConDesc = round($bruto * (1 - $descuento / 100), 2);
            $descuentoMonto = round($bruto - $lineaConDesc, 2);

            $subtotalBruto += $bruto;
            $subtotal += $lineaConDesc;

            $detalle[] = [
                'bruto'          => $bruto,
                'descuentoMonto' => $descuentoMonto,
                'subtotal'       => $lineaConDesc,
            ];
        }

        $subtotalBruto = round($subtotalBruto, 2);
        $subtotal = round($subtotal, 2);

        $descTotalPct = self::clampPct($descuentoTotalPct);
        $descuentoTotalMonto = round($subtotal * $descTotalPct / 100, 2);
        $total = round($subtotal - $descuentoTotalMonto, 2);

        $neto = round($total / (1 + self::IVA_PCT / 100), 2);
        $iva = round($neto * (self::IVA_PCT / 100), 2);

        return [
            'subtotalBruto'       => $subtotalBruto,
            'subtotal'            => $subtotal,
            'descuentoTotalMonto' => $descuentoTotalMonto,
            'total'               => $total,
            'neto'                => $neto,
            'iva'                 => $iva,
            'lineas'              => $detalle,
        ];
    }

    /** @param mixed $v */
    private static function num($v): float
    {
        return is_numeric($v) ? (float) $v : 0.0;
    }

    /** Normaliza y clampea un porcentaje a [0, 100]. @param mixed $v */
    private static function clampPct($v): float
    {
        $n = self::num($v);
        if ($n < 0) {
            return 0.0;
        }
        if ($n > 100) {
            return 100.0;
        }
        return $n;
    }
}
