<?php

namespace App\Console\Commands;

use App\Facturacion\ComprobanteHtml;
use App\Models\AfipConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spipu\Html2Pdf\Html2Pdf;

/**
 * Vuelve a generar el PDF de facturas ya emitidas a partir de la base.
 *
 * No toca AFIP ni la base: usa el CAE, los datos del cliente y los renglones
 * (`ventas.factura_id`) que quedaron grabados al emitir, y escribe el archivo
 * en la ruta que la factura ya tiene en `factura.pdf`, así el LINK del listado
 * de facturación vuelve a funcionar. Sirve para PDFs perdidos en una migración
 * o que nunca se escribieron (incidente del logo http:// del 2026-09-23).
 */
class RegenerarPdfFactura extends Command
{
    protected $signature = 'factura:regenerar-pdf
        {numero?* : Número(s) de comprobante, como figuran en el listado de facturación}
        {--id=* : Id(s) de la tabla factura}
        {--sucursal= : Limita los números a esa sucursal (id) cuando se repiten entre puntos de venta}
        {--faltantes : Todas las facturas con CAE cuyo PDF no está en disco}
        {--fecha= : Fecha de emisión (AAAA-MM-DD) declarada a AFIP, si no coincide con factura.fecha}
        {--forzar : Sobrescribe el PDF si ya existe}
        {--dry-run : Solo muestra qué haría}';

    protected $description = 'Regenera el PDF de facturas ya emitidas (CAE en la base) sin volver a facturar';

    private const FORMAS_PAGO = [1 => 'Efectivo', 2 => 'Debito', 3 => 'Credito', 4 => 'Transferencia'];

    private const TEXTO_IVA = [1 => 'Resp. Inscripto', 2 => 'Monotributista', 3 => 'Exento', 4 => 'Cons. Final'];

    public function handle(): int
    {
        $facturas = $this->seleccionar();
        if ($facturas === null) {
            return 1;
        }
        if ($facturas->isEmpty()) {
            $this->error('No se encontró ninguna factura con ese criterio.');
            return 1;
        }

        if (($fecha = $this->option('fecha')) !== null && ! preg_match('/^\d{4}-\d{2}-\d{2}\z/', $fecha)) {
            $this->error('--fecha debe ser AAAA-MM-DD.');
            return 1;
        }

        $this->mostrarEmisor();

        $fallidas = 0;
        foreach ($facturas as $factura) {
            $ruta = $this->rutaPdf($factura);
            $etiqueta = sprintf('Nro %06d (id %d, emitida %s, %s)', (int) $factura->numero, $factura->id, $factura->fecha, $factura->pdf);

            if ($ruta === null) {
                $this->error("$etiqueta: la ruta del PDF guardada en la base no es válida.");
                $fallidas++;
                continue;
            }
            if (is_file($ruta) && ! $this->option('forzar')) {
                $this->line("$etiqueta: el PDF ya existe, se conserva (usar --forzar para pisarlo).");
                continue;
            }
            if ($this->option('dry-run')) {
                $this->info("$etiqueta: se generaría en $ruta");
                continue;
            }
            try {
                $this->generar($factura, $ruta);
                $this->info("$etiqueta: PDF generado.");
            } catch (\Throwable $e) {
                $this->error("$etiqueta: ".$e->getMessage());
                $fallidas++;
            }
        }

        return $fallidas > 0 ? 1 : 0;
    }

    /**
     * Datos del emisor con los que se va a imprimir. `factura` no los guarda:
     * salen de la config AFIP activa, que debe ser la misma con la que se emitió.
     */
    private function mostrarEmisor(): void
    {
        $afip = AfipConfig::activa();
        if (! $afip) {
            $this->warn('Sin config AFIP activa: el comprobante saldrá sin CUIT ni condición de IVA del emisor.');
            return;
        }
        $this->line(sprintf(
            'Emisor según config AFIP activa (%s): CUIT %s, comprobante tipo %s (%s), %s. Debe ser la misma con la que se emitió.',
            $afip->entorno, $afip->cuit, $afip->comprobante, ComprobanteHtml::letra((int) $afip->comprobante), $afip->condicion_iva
        ));
        if ($this->option('fecha') === null) {
            $this->line('Fecha de emisión: se usa la de factura.fecha (momento del alta). Si se facturó con otra fecha, pasar --fecha=AAAA-MM-DD.');
        }
    }

    /** @return \Illuminate\Support\Collection|null null = criterio inválido (ya se informó) */
    private function seleccionar()
    {
        $numeros = array_values(array_filter(array_map('intval', (array) $this->argument('numero'))));
        $ids = array_values(array_filter(array_map('intval', (array) $this->option('id'))));

        if (! $numeros && ! $ids && ! $this->option('faltantes')) {
            $this->error('Indique números de comprobante, --id=N o --faltantes. Ej: factura:regenerar-pdf 590 591');
            return null;
        }

        $q = DB::table('factura')->whereNotNull('pdf')->where('pdf', '<>', '');
        if ($numeros || $ids) {
            $q->where(function ($w) use ($numeros, $ids) {
                if ($numeros) {
                    // Los presupuestos guardan su propio correlativo en `numero`: por número solo facturas.
                    $w->where(function ($f) use ($numeros) {
                        $f->whereIn('numero', $numeros)->where('presupuesto', 0);
                    });
                }
                if ($ids) {
                    $w->orWhereIn('id', $ids);
                }
            });
        }
        if ($this->option('sucursal') !== null) {
            $q->where('sucursal_id', (int) $this->option('sucursal'));
        }
        $facturas = $q->orderBy('id')->get();

        if ($this->option('faltantes')) {
            $facturas = $facturas->filter(function ($f) {
                return (int) $f->presupuesto === 0 && (string) $f->cae !== ''
                    && ($ruta = $this->rutaPdf($f)) !== null && ! is_file($ruta);
            })->values();
        }

        return $facturas;
    }

    /** Ruta absoluta del PDF dentro de public/, a partir de factura.pdf (acepta URL absoluta vieja). */
    private function rutaPdf($factura): ?string
    {
        $path = parse_url((string) $factura->pdf, PHP_URL_PATH);
        if (! is_string($path) || ! preg_match('#^/?(facturas|presupuesto)/[A-Za-z0-9_\-]+\.pdf\z#', $path)) {
            return null;
        }

        return rtrim(public_path(), '/').'/'.ltrim($path, '/');
    }

    private function generar($factura, string $ruta): void
    {
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
        $html2pdf->writeHTML(ComprobanteHtml::documento($this->datos($factura, $lineas)));
        $html2pdf->output($ruta, 'F');
    }

    private function datos($factura, $lineas): array
    {
        require_once base_path('public/legacy_config.php');

        $sucursal = DB::table('sucursales')->where('id', $factura->sucursal_id)->first();
        $perfil = DB::table('perfil')->orderBy('id')->first();
        $afip = AfipConfig::activa();

        // El punto de venta con el que se emitió está en el nombre del archivo; la
        // sucursal puede haber cambiado de pto_vta después.
        $ptovta = preg_match('#/(\d+)_[^/]*\.pdf$#', (string) $factura->pdf, $m)
            ? (int) $m[1] : (int) ($sucursal->pto_vta ?? 0);

        $fecha = preg_match('/^(\d{4})-(\d{2})-(\d{2})/', (string) ($this->option('fecha') ?: $factura->fecha), $f)
            ? "$f[3]-$f[2]-$f[1]" : (string) $factura->fecha;

        $tipoPago = (int) ($lineas->first()->tipo_pago ?? 0);

        return [
            'logo_src' => legacy_logo_pdf(public_path(), $perfil->logo ?? ''),
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
}
