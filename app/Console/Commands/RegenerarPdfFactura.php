<?php

namespace App\Console\Commands;

use App\Facturacion\ComprobanteHtml;
use App\Facturacion\RegeneradorPdfFactura;
use App\Models\AfipConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Vuelve a generar el PDF de facturas ya emitidas a partir de la base.
 * La lógica está en App\Facturacion\RegeneradorPdfFactura (compartida con el
 * botón "Regenerar PDF" del reporte); acá solo la selección y la salida.
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

    /** @var RegeneradorPdfFactura */
    private $regenerador;

    public function __construct(RegeneradorPdfFactura $regenerador)
    {
        parent::__construct();
        $this->regenerador = $regenerador;
    }

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
            $ruta = $this->regenerador->rutaPdf($factura);
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
                $this->regenerador->regenerar($factura, $fecha);
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
                    && $this->regenerador->rutaPdf($f) !== null && ! $this->regenerador->pdfExiste($f);
            })->values();
        }

        return $facturas;
    }
}
