<?php

namespace App\Facturacion;

use App\Ventas\CalculadoraVenta;

/**
 * Plantilla HTML del comprobante (factura / presupuesto) para Html2Pdf.
 *
 * Es la misma que arma public/facturar.php al emitir, extraída como función
 * pura para poder volver a generar el PDF de una factura ya emitida a partir
 * de lo que quedó en la base (`factura` + `ventas`), sin tocar AFIP.
 *
 * Los datos de la base se escapan: la plantilla legacy no lo hacía y un "&"
 * o "<" en el nombre de un producto rompe el parser de Html2Pdf.
 */
class ComprobanteHtml
{
    /** Documento completo: una página por copia (factura: ORIGINAL y DUPLICADO; nota de crédito: solo ORIGINAL). */
    public static function documento(array $d, array $copias = ['ORIGINAL', 'DUPLICADO']): string
    {
        $cuerpo = self::cuerpo($d);
        $pie = "<br><br><hr style='border-style: dotted;' /><br><br>";

        $html = '';
        foreach ($copias as $copia) {
            $html .= '<page>'.str_replace('@@COMPROBANTE@@', $copia, $cuerpo).$pie.'</page>';
        }

        return $html;
    }

    /** Una copia del comprobante, con @@COMPROBANTE@@ donde va ORIGINAL/DUPLICADO. */
    public static function cuerpo(array $d): string
    {
        $presupuesto = ! empty($d['presupuesto']);
        $letra = $presupuesto ? 'X' : self::letra((int) ($d['comprobante_tipo'] ?? 0));
        $titulo = $d['titulo'] ?? ($presupuesto ? 'PRESUPUESTO NRO.' : 'FACTURA NRO.');
        $validez = $presupuesto ? 'No valido como factura' : 'Comprobante Electronico';

        $sucursal = $d['sucursal'] ?? [];
        $emisor = $d['emisor'] ?? [];
        $cliente = $d['cliente'] ?? [];
        $lineas = $d['lineas'] ?? [];
        $descuentoTotal = (float) ($d['descuento_total'] ?? 0);
        $calc = CalculadoraVenta::calcular(array_map(function ($l) {
            return [
                'precio' => (float) ($l['precio'] ?? 0),
                'cantidad' => (float) ($l['cantidad'] ?? 0),
                'descuento' => (float) ($l['descuento'] ?? 0),
            ];
        }, $lineas), $descuentoTotal);
        // El total es el que se declaró a AFIP (columna factura.total), no el recalculado.
        $total = array_key_exists('total', $d) ? (float) $d['total'] : $calc['total'];

        $html = "
			<style>
			h3{
				font-size:1em;
			}
			</style>
			<table>
			<tr>
			<td colspan='3' style='border: 2px solid #000;text-align:center;'>@@COMPROBANTE@@</td>
			</tr>
			<tr>
			<td style='padding-left:10px;border: 2px solid #000;height: 100px;font-size: 14px;width: 320px;text-align: left;'>
			<table>
			<tr>
			<td>
			<img src='".self::e($d['logo_src'] ?? '')."' style='height:80px;width:120px;'/>
			</td>
			<td>
			<br />".self::e($sucursal['nombre'] ?? '')."
			<br />".self::direccionSucursal($sucursal['direccion'] ?? '')."<br />
			".self::e($sucursal['codigo_postal'] ?? '').' - '.self::e($sucursal['provincia'] ?? '')."<br />
			<b>$validez</b>
			</td>
			</tr>
			</table>
			</td>
			<td style='border: 2px solid #000;height: 100px;font-size: 60px;width: 70px;text-align: center;'>$letra</td>
			<td style='border: 2px solid #000;height: 100px;font-size: 14px;width: 280px;text-align: left;'>
			<b>$titulo</b>&nbsp;".self::seis($d['ptovta'] ?? 0).'&nbsp;-&nbsp;'.self::seis($d['numero'] ?? 0)."<br />
			<b>CUIT</b>&nbsp;".self::e($emisor['cuit'] ?? '')."<br />
			<b>Fecha de Emisi&oacute;n</b>&nbsp;".self::e($d['fecha'] ?? '')."<br />
			<b>Ing.&nbsp;Bruto</b>&nbsp;".self::e($emisor['ingresos_brutos'] ?? '')."<br />
			<b>IVA</b>&nbsp;".self::e($emisor['condicion_iva'] ?? '')." <br />

			</td>
			</tr>
			".self::datosCliente($cliente)."
			<tr>
			<td style='border-bottom: 1px solid #000;word-wrap: break-word;width:230px'><b>Descripcion</b></td>
			<td style='border-bottom: 1px solid #000;'><b>Cantidad</b></td>
			<td style='border-bottom: 1px solid #000;'><b>Precio</b></td>
			</tr>
			";

        foreach ($lineas as $l) {
            $descLinea = is_numeric($l['descuento'] ?? null) ? (float) $l['descuento'] : 0;
            $precioUnitDesc = round((float) ($l['precio'] ?? 0) * (1 - $descLinea / 100), 2);
            $html .= "<tr>
				<td style='border-bottom: 1px solid #000;word-wrap: break-word;width:230px;text-align:justify'><i>".self::e($l['nombre'] ?? '')."</i></td>
				<td style='border-bottom: 1px solid #000;'>".self::e($l['cantidad'] ?? '')."</td>
				<td style='border-bottom: 1px solid #000;'>".self::monto($precioUnitDesc)."</td>
				</tr>
				";
        }

        // Con descuento total los renglones muestran el precio sin ese descuento;
        // Subtotal y Descuento (N%) hacen que el Total cierre contra los renglones.
        if ($descuentoTotal > 0) {
            $pctTexto = rtrim(rtrim(number_format($descuentoTotal, 2, ',', '.'), '0'), ',');
            $html .= "<tr>
				<td></td>
				<td style='border-bottom: 1px solid #000;'>Subtotal</td>
				<td style='border-bottom: 1px solid #000;'>".self::monto($calc['subtotal'])."</td>
				</tr>
				<tr>
				<td></td>
				<td style='border-bottom: 1px solid #000;'>Descuento ($pctTexto%)</td>
				<td style='border-bottom: 1px solid #000;'>-".self::monto($calc['descuentoTotalMonto'])."</td>
				</tr>";
        }
        $html .= "<tr>
			<td></td>
			<td style='border-bottom: 1px solid #000;'>Total</td>
			<td style='border-bottom: 1px solid #000;'>".self::monto($total)."</td>
			</tr>";

        if ((int) ($d['comprobante_tipo'] ?? 0) === 1 && ! $presupuesto) {
            // Mismo desglose que se declaró a AFIP al emitir (CalculadoraVenta sobre los renglones).
            $neto = $calc['neto'];
            $html .= "
				<tr>
				<td></td>
				<td style='border-bottom: 1px solid #000;'>Importe Neto</td>
				<td style='border-bottom: 1px solid #000;'>".self::monto($neto)."</td>
				</tr>
				<tr>
				<td></td>
				<td style='border-bottom: 1px solid #000;'>IVA (21%)</td>
				<td style='border-bottom: 1px solid #000;'>".self::monto($calc['iva'])."</td>
				</tr>";
        }
        $html .= '</table>';

        if (! $presupuesto && ($d['cae'] ?? '') !== '') {
            $html .= "<p style='text-align:right'><b>CAE Nro.:</b> ".self::e($d['cae'])."<br />
				<b>Fecha de Vto. CAE: </b>".self::e($d['cae_vto'] ?? '')."<br /></p>";
        }

        return $html;
    }

    /** Letra del comprobante según el código AFIP (CbteTipo): facturas 1/6/11, notas de crédito 3/8/13, de débito 2/7/12. */
    public static function letra(int $tipo): string
    {
        switch ($tipo) {
            case 1: case 2: case 3:    return 'A';
            case 6: case 7: case 8:    return 'B';
            case 11: case 12: case 13: return 'C';
            default: return 'X';
        }
    }

    private static function datosCliente(array $c): string
    {
        $html = "<tr>
	<td colspan='3' style='border-bottom: 1px solid #000;'><b>Nombre y Apellido</b> ".self::e($c['nombre'] ?? '')."</td>
	</tr><tr>
	<td colspan='3' style='border-bottom: 1px solid #000;'><b>Direcci&oacute;n</b> ".self::e($c['direccion'] ?? '')."</td>
	</tr><tr>
	<td colspan='3' style='border-bottom: 1px solid #000;'><b>CUIT, CUIL &oacute; CDI </b> ".self::e($c['documento'] ?? '')."</td>
	</tr>
	<tr>
	<td colspan='3' style='border-bottom: 1px solid #000;'><b>IVA </b> ".self::e($c['iva_texto'] ?? '')."</td>
	</tr>";
        // La forma de pago no se persiste en `factura`: al regenerar solo se
        // imprime si se conoce, para no inventar un dato en un comprobante.
        if (! empty($c['forma_pago'])) {
            $html .= "
	<tr>
	<td colspan='3' style='border-bottom: 1px solid #000;'><b>Forma de pago </b> ".self::e($c['forma_pago'])."</td>
	</tr>";
        }

        return $html."
	<tr>
	<td colspan='3' style='height:30px;'>&nbsp;</td>
	</tr>";
    }

    /** Misma partición en dos líneas que usa facturar.php para direcciones largas. */
    private static function direccionSucursal(string $direccion): string
    {
        if (strlen($direccion) > 25 && ($corte = strpos($direccion, ' ', 20)) !== false) {
            return self::e(substr($direccion, 0, $corte)).'<br />'.self::e(substr($direccion, $corte));
        }

        return self::e($direccion);
    }

    private static function seis($n): string
    {
        return substr('000000'.(int) $n, -6);
    }

    private static function monto($n): string
    {
        return number_format((float) $n, 2, ',', '.');
    }

    private static function e($v): string
    {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    }
}
