<?php

namespace App\Facturacion;

use App\Models\AfipConfig;
use Illuminate\Support\Facades\DB;
use Spipu\Html2Pdf\Html2Pdf;

/**
 * Vuelve a generar el PDF de una factura ya emitida a partir de la base.
 *
 * No toca AFIP ni escribe en la base: usa el CAE, los datos del cliente y los
 * renglones (`ventas.factura_id`) que quedaron grabados al emitir, y escribe el
 * archivo en la ruta que la factura ya tiene en `factura.pdf`, así el LINK del
 * reporte de facturación vuelve a funcionar. Lo usan el comando
 * factura:regenerar-pdf y el botón "Regenerar PDF" del reporte.
 */
class RegeneradorPdfFactura
{
    private const FORMAS_PAGO = [1 => 'Efectivo', 2 => 'Debito', 3 => 'Credito', 4 => 'Transferencia'];

    private const TEXTO_IVA = [1 => 'Resp. Inscripto', 2 => 'Monotributista', 3 => 'Exento', 4 => 'Cons. Final'];

    /**
     * Genera (o pisa) el PDF de la factura. Devuelve la ruta absoluta escrita.
     *
     * @param object      $factura fila de `factura` (stdClass o Eloquent)
     * @param string|null $fechaEmision AAAA-MM-DD declarada a AFIP si no es la del alta
     * @throws \RuntimeException con un motivo legible para el operador
     */
    public function regenerar($factura, ?string $fechaEmision = null): string
    {
        $ruta = $this->rutaPdf($factura);
        if ($ruta === null) {
            throw new \RuntimeException('la ruta del PDF guardada en la base no es válida.');
        }

        $lineas = DB::table('ventas')
            ->leftJoin('productos', 'productos.id', '=', 'ventas.productos_id')
            ->where('ventas.factura_id', $factura->id)
            ->orderBy('ventas.id')
            ->get(['ventas.cantidad', 'ventas.precio', 'ventas.descuento', 'ventas.tipo_pago', 'productos.nombre']);
        if ($lineas->isEmpty()) {
            throw new \RuntimeException('no tiene renglones de venta asociados (ventas.factura_id); no se puede regenerar.');
        }

        $dir = dirname($ruta);
        if (! is_dir($dir) || ! is_writable($dir)) {
            throw new \RuntimeException("la carpeta $dir no existe o no es escribible.");
        }

        $html2pdf = new Html2Pdf('P', 'A4', 'pt', true, 'UTF-8');
        $html2pdf->setDefaultFont('Arial');
        $html2pdf->writeHTML(ComprobanteHtml::documento($this->datos($factura, $lineas, $fechaEmision)));
        $html2pdf->output($ruta, 'F');

        return $ruta;
    }

    /** ¿Existe en disco el PDF que la factura dice tener? */
    public function pdfExiste($factura): bool
    {
        $ruta = $this->rutaPdf($factura);

        return $ruta !== null && is_file($ruta);
    }

    /** Ruta absoluta del PDF dentro de public/, a partir de factura.pdf (acepta URL absoluta vieja). */
    public function rutaPdf($factura): ?string
    {
        $path = parse_url((string) $factura->pdf, PHP_URL_PATH);
        if (! is_string($path) || ! preg_match('#^/?(facturas|presupuesto)/[A-Za-z0-9_\-]+\.pdf\z#', $path)) {
            return null;
        }

        return rtrim(public_path(), '/').'/'.ltrim($path, '/');
    }

    private function datos($factura, $lineas, ?string $fechaEmision): array
    {
        require_once base_path('public/legacy_config.php');

        $sucursal = DB::table('sucursales')->where('id', $factura->sucursal_id)->first();
        $perfil = DB::table('perfil')->orderBy('id')->first();
        $afip = AfipConfig::activa();

        // El punto de venta con el que se emitió está en el nombre del archivo; la
        // sucursal puede haber cambiado de pto_vta después.
        $ptovta = preg_match('#/(\d+)_[^/]*\.pdf\z#', (string) $factura->pdf, $m)
            ? (int) $m[1] : (int) ($sucursal->pto_vta ?? 0);

        $fecha = preg_match('/^(\d{4})-(\d{2})-(\d{2})/', (string) ($fechaEmision ?: $factura->fecha), $f)
            ? "$f[3]-$f[2]-$f[1]" : (string) $factura->fecha;

        $tipoPago = (int) ($lineas->first()->tipo_pago ?? 0);

        return [
            'logo_src' => $this->logoSrc($perfil->logo ?? ''),
            'sucursal' => [
                'nombre' => $sucursal->nombre ?? '',
                'direccion' => $sucursal->direccion ?? '',
                'codigo_postal' => $sucursal->codigo_postal ?? '',
                'provincia' => $sucursal->provincia ?? '',
            ],
            'emisor' => [
                'cuit' => $afip->cuit ?? '',
                'ingresos_brutos' => $afip->ingresos_brutos ?? '',
                'condicion_iva' => $afip->condicion_iva ?? '',
            ],
            'comprobante_tipo' => (int) ($afip->comprobante ?? 11),
            'presupuesto' => (int) $factura->presupuesto === 1,
            'ptovta' => $ptovta,
            'numero' => (int) $factura->numero,
            'fecha' => $fecha,
            'cae' => (string) $factura->cae,
            'cae_vto' => (string) $factura->fechacae,
            'cliente' => [
                'nombre' => (string) $factura->nombre,
                'direccion' => (string) $factura->direccion,
                'documento' => (string) $factura->documento,
                'iva_texto' => self::TEXTO_IVA[(int) $factura->iva] ?? 'Consumidor Final',
                'forma_pago' => self::FORMAS_PAGO[$tipoPago] ?? null,
            ],
            'lineas' => $lineas->map(function ($l) {
                return [
                    'nombre' => $l->nombre ?? '',
                    'cantidad' => $l->cantidad,
                    'precio' => (float) $l->precio,
                    'descuento' => (float) $l->descuento,
                ];
            })->all(),
            'descuento_total' => (float) ($factura->descuento_total ?? 0),
            'total' => (float) $factura->total,
        ];
    }

    /**
     * Logo del perfil como ruta ABSOLUTA. legacy_logo_pdf() devuelve file://
     * porque la copia vieja de Html2Pdf de public/vendor (facturar.php) solo
     * acepta eso; la copia de Laravel, al revés, ignora file:// en silencio y
     * deja el comprobante sin logo.
     */
    private function logoSrc(string $logoRel): string
    {
        return preg_replace('#^file://#', '', legacy_logo_pdf(public_path(), $logoRel));
    }
}
