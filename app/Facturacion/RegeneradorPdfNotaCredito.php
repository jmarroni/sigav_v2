<?php

namespace App\Facturacion;

use App\Models\AfipConfig;
use Illuminate\Support\Facades\DB;
use Spipu\Html2Pdf\Html2Pdf;

/**
 * Vuelve a generar el PDF de una nota de crédito ya emitida a partir de la base.
 *
 * Misma idea que RegeneradorPdfFactura. El PDF legacy (nota_de_credito.php)
 * no lista productos: lleva una sola línea con la observación del operador y
 * el total. La observación no se persiste, así que la línea se arma con el
 * comprobante asociado que informa AFIP ("Nota de crédito s/ Factura C
 * 00017-00000289") o, sin AFIP, con un texto genérico.
 */
class RegeneradorPdfNotaCredito
{
    public const DESCRIPCION_GENERICA = 'Anulación de comprobante';

    private const TIPO_NC_C = 13;

    private const TEXTO_IVA = [1 => 'Resp. Inscripto', 2 => 'Monotributista', 3 => 'Exento', 4 => 'Cons. Final'];

    /** @var ConsultaComprobanteAfip */
    private $afip;

    /** @var string|null aviso de la última regeneración (p. ej. AFIP no respondió) */
    private $ultimoAviso;

    public function __construct(ConsultaComprobanteAfip $afip)
    {
        $this->afip = $afip;
    }

    /**
     * Genera (o pisa) el PDF. Devuelve ['ruta' => abs, 'descripcion' => string, 'aviso' => ?string].
     *
     * @throws \RuntimeException con un motivo legible para el operador
     */
    public function regenerar($nota, bool $consultarAfip = true): array
    {
        $ruta = $this->rutaPdf($nota);
        if ($ruta === null) {
            throw new \RuntimeException('la ruta del PDF guardada en la base no es válida.');
        }
        $dir = dirname($ruta);
        if (! is_dir($dir) || ! is_writable($dir)) {
            throw new \RuntimeException("la carpeta $dir no existe o no es escribible.");
        }

        $this->ultimoAviso = null;
        $descripcion = $this->descripcion($nota, $consultarAfip);

        $html2pdf = new Html2Pdf('P', 'A4', 'pt', true, 'UTF-8');
        $html2pdf->setDefaultFont('Arial');
        $html2pdf->writeHTML(ComprobanteHtml::documento($this->datos($nota, $descripcion), ['ORIGINAL']));
        $html2pdf->output($ruta, 'F');

        return ['ruta' => $ruta, 'descripcion' => $descripcion, 'aviso' => $this->ultimoAviso];
    }

    /** Línea de detalle del comprobante: factura asociada según AFIP, o el texto genérico. */
    public function descripcion($nota, bool $consultarAfip): string
    {
        if (! $consultarAfip) {
            return self::DESCRIPCION_GENERICA;
        }
        try {
            $asoc = $this->afip->comprobanteAsociado($this->ptovta($nota), (int) $nota->numero, self::TIPO_NC_C);
        } catch (\RuntimeException $e) {
            $this->ultimoAviso = sprintf(
                'no se pudo consultar el comprobante asociado en AFIP (%s); se usa "%s".',
                $e->getMessage(), self::DESCRIPCION_GENERICA
            );
            return self::DESCRIPCION_GENERICA;
        }
        if ($asoc === null) {
            return self::DESCRIPCION_GENERICA;
        }

        return sprintf(
            'Nota de crédito s/ Factura %s %05d-%08d',
            ComprobanteHtml::letra((int) $asoc['tipo']), $asoc['ptovta'], $asoc['nro']
        );
    }

    public function pdfExiste($nota): bool
    {
        $ruta = $this->rutaPdf($nota);

        return $ruta !== null && is_file($ruta);
    }

    /** Ruta absoluta del PDF dentro de public/notas_credito, a partir de nota.pdf. */
    public function rutaPdf($nota): ?string
    {
        $path = parse_url((string) $nota->pdf, PHP_URL_PATH);
        if (! is_string($path) || ! preg_match('#^/?notas_credito/[A-Za-z0-9_\-]+\.pdf\z#', $path)) {
            return null;
        }

        return rtrim(public_path(), '/').'/'.ltrim($path, '/');
    }

    /** Punto de venta con el que se emitió: está en el nombre del archivo. */
    private function ptovta($nota): int
    {
        if (preg_match('#/(\d+)_[^/]*\.pdf\z#', (string) $nota->pdf, $m)) {
            return (int) $m[1];
        }
        $sucursal = DB::table('sucursales')->where('id', $nota->sucursal_id)->first();

        return (int) ($sucursal->pto_vta ?? 0);
    }

    private function datos($nota, string $descripcion): array
    {
        require_once base_path('public/legacy_config.php');

        $sucursal = DB::table('sucursales')->where('id', $nota->sucursal_id)->first();
        $perfil = DB::table('perfil')->orderBy('id')->first();
        $afip = AfipConfig::activa();

        $fecha = preg_match('/^(\d{4})-(\d{2})-(\d{2})/', (string) $nota->fecha, $f)
            ? "$f[3]-$f[2]-$f[1]" : (string) $nota->fecha;

        return [
            'titulo' => 'NOTA DE CREDITO NRO.',
            'logo_src' => preg_replace('#^file://#', '', legacy_logo_pdf(public_path(), $perfil->logo ?? '')),
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
            'comprobante_tipo' => self::TIPO_NC_C,
            'presupuesto' => false,
            'ptovta' => $this->ptovta($nota),
            'numero' => (int) $nota->numero,
            'fecha' => $fecha,
            'cae' => (string) $nota->cae,
            'cae_vto' => (string) $nota->fechacae,
            'cliente' => [
                'nombre' => (string) $nota->nombre,
                'direccion' => (string) $nota->direccion,
                'documento' => (string) $nota->documento,
                'iva_texto' => self::TEXTO_IVA[(int) $nota->iva] ?? 'Consumidor Final',
                'forma_pago' => null,
            ],
            'lineas' => [['nombre' => $descripcion, 'cantidad' => 1, 'precio' => (float) $nota->total, 'descuento' => 0]],
            'descuento_total' => 0,
            'total' => (float) $nota->total,
        ];
    }
}
