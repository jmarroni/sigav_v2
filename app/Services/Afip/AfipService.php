<?php

namespace App\Services\Afip;

use App\Models\AfipConfig;
use Illuminate\Support\Facades\Log;

class AfipService
{
    public function __construct()
    {
        if (! class_exists('Afip')) {
            require_once config('afip.sdk_path');
        }
    }

    /** Construye una instancia del SDK configurada para el entorno dado (o el activo). */
    public function instancia(?string $entorno = null): \Afip
    {
        $cfg = $entorno
            ? AfipConfig::where('entorno', $entorno)->firstOrFail()
            : AfipConfig::activa();

        if (! $cfg) {
            throw new \RuntimeException('No hay entorno AFIP activo configurado');
        }

        $dir = $cfg->rutaStorage();

        return new \Afip([
            'CUIT'       => floatval($cfg->cuit),
            'production' => $cfg->entorno === 'prod',
            'res_folder' => $dir,
            'ta_folder'  => $dir,
        ]);
    }

    /** Guarda cert/key del entorno en storage (fuera del webroot), con permisos restrictivos. */
    public function guardarCredenciales(string $entorno, string $cert, string $key): void
    {
        if (strpos($cert, '-----BEGIN') === false || strpos($key, '-----BEGIN') === false) {
            throw new \InvalidArgumentException('El certificado o la clave no parecen tener formato PEM válido');
        }

        $dir = rtrim(config('afip.storage_path'), '/').'/'.$entorno.'/';
        if (! is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        file_put_contents($dir.'cert', $cert);
        @chmod($dir.'cert', 0600);
        file_put_contents($dir.'key', $key);
        @chmod($dir.'key', 0600);
    }

    /** Prueba la conexión con AFIP para un entorno. No filtra detalles al usuario. */
    public function probar(string $entorno): array
    {
        try {
            $cfg = AfipConfig::where('entorno', $entorno)->firstOrFail();
            $afip = $this->instancia($entorno);
            $num = $afip->ElectronicBilling->GetLastVoucher($cfg->ptovta, $cfg->comprobante);

            return ['ok' => true, 'mensaje' => "Conexión OK. Último comprobante autorizado: {$num}"];
        } catch (\Throwable $e) {
            Log::error('AFIP probar() falló', ['entorno' => $entorno, 'error' => $e->getMessage()]);

            return ['ok' => false, 'mensaje' => 'No se pudo conectar con AFIP. Revisá las credenciales y los datos cargados.'];
        }
    }

    /** Tipos de comprobante para poblar el select; [] si falla (no rompe la pantalla). */
    public function tiposComprobante(string $entorno): array
    {
        try {
            return (array) $this->instancia($entorno)->ElectronicBilling->GetVoucherTypes();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
