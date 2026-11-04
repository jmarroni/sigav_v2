<?php

namespace App\Console\Commands;

use App\Facturacion\RegeneradorPdfNotaCredito;
use App\Models\AfipConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Vuelve a generar el PDF de notas de crédito ya emitidas (CAE en la base).
 * No emite nada: como mucho consulta a AFIP a qué factura está asociada cada nota.
 */
class RegenerarPdfNotaCredito extends Command
{
    protected $signature = 'nota-credito:regenerar-pdf
        {numero?* : Número(s) de nota de crédito}
        {--id=* : Id(s) de la tabla nota_de_credito}
        {--sucursal= : Limita los números a esa sucursal (id)}
        {--faltantes : Todas las notas con CAE cuyo PDF no está en disco}
        {--sin-afip : No consultar a AFIP el comprobante asociado (detalle genérico)}
        {--forzar : Sobrescribe el PDF si ya existe}
        {--dry-run : Solo muestra qué haría}';

    protected $description = 'Regenera el PDF de notas de crédito ya emitidas sin volver a emitir';

    /** @var RegeneradorPdfNotaCredito */
    private $regenerador;

    public function __construct(RegeneradorPdfNotaCredito $regenerador)
    {
        parent::__construct();
        $this->regenerador = $regenerador;
    }

    public function handle(): int
    {
        $notas = $this->seleccionar();
        if ($notas === null) {
            return 1;
        }
        if ($notas->isEmpty()) {
            $this->error('No se encontró ninguna nota de crédito con ese criterio.');
            return 1;
        }

        $afip = AfipConfig::activa();
        if ($afip) {
            $this->line(sprintf('Emisor según config AFIP activa (%s): CUIT %s, %s. Debe ser la misma con la que se emitió.', $afip->entorno, $afip->cuit, $afip->condicion_iva));
        } else {
            $this->warn('Sin config AFIP activa: el comprobante saldrá sin CUIT ni condición de IVA del emisor.');
        }

        $fallidas = 0;
        foreach ($notas as $nota) {
            $ruta = $this->regenerador->rutaPdf($nota);
            $etiqueta = sprintf('Nro %06d (id %d, emitida %s, %s)', (int) $nota->numero, $nota->id, $nota->fecha, $nota->pdf);

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
                $r = $this->regenerador->regenerar($nota, ! $this->option('sin-afip'));
                if ($r['aviso']) {
                    $this->warn(sprintf('Nro %06d: %s', (int) $nota->numero, $r['aviso']));
                }
                $this->info("$etiqueta: PDF generado. Detalle: ".$r['descripcion']);
            } catch (\Throwable $e) {
                $this->error("$etiqueta: ".$e->getMessage());
                $fallidas++;
            }
        }

        return $fallidas > 0 ? 1 : 0;
    }

    /** @return \Illuminate\Support\Collection|null */
    private function seleccionar()
    {
        $numeros = array_values(array_filter(array_map('intval', (array) $this->argument('numero'))));
        $ids = array_values(array_filter(array_map('intval', (array) $this->option('id'))));

        if (! $numeros && ! $ids && ! $this->option('faltantes')) {
            $this->error('Indique números de nota, --id=N o --faltantes. Ej: nota-credito:regenerar-pdf 111 112');
            return null;
        }

        $q = DB::table('nota_de_credito')->whereNotNull('pdf')->where('pdf', '<>', '');
        if ($numeros || $ids) {
            $q->where(function ($w) use ($numeros, $ids) {
                if ($numeros) {
                    $w->whereIn('numero', $numeros);
                }
                if ($ids) {
                    $w->orWhereIn('id', $ids);
                }
            });
        }
        if ($this->option('sucursal') !== null) {
            $q->where('sucursal_id', (int) $this->option('sucursal'));
        }
        $notas = $q->orderBy('id')->get();

        if ($this->option('faltantes')) {
            $notas = $notas->filter(function ($n) {
                return (string) $n->cae !== '' && $this->regenerador->rutaPdf($n) !== null && ! $this->regenerador->pdfExiste($n);
            })->values();
        }

        return $notas;
    }
}
