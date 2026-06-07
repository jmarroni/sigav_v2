<?php

namespace App\Services\Afip;

use App\Models\AfipConfig;
use Illuminate\Support\Facades\Log;

class AfipService
{
    private const ENTORNOS = ['homo', 'prod'];

    public function __construct()
    {
        if (! class_exists('Afip')) {
            require_once config('afip.sdk_path');
        }
    }

    /** Construye una instancia del SDK configurada para el entorno dado (o el activo). */
    public function instancia(?string $entorno = null): \Afip
    {
        if ($entorno !== null) {
            $this->validarEntorno($entorno);
            $cfg = AfipConfig::where('entorno', $entorno)->firstOrFail();
        } else {
            $cfg = AfipConfig::activa();
        }

        if (! $cfg) {
            throw new \RuntimeException('No hay entorno AFIP activo configurado');
        }

        return $this->instanciaDesde($cfg);
    }

    /** Guarda cert/key del entorno en storage (fuera del webroot), con permisos restrictivos. */
    public function guardarCredenciales(string $entorno, string $cert, string $key): void
    {
        $this->validarEntorno($entorno);

        if (strpos($cert, '-----BEGIN') === false || strpos($key, '-----BEGIN') === false) {
            throw new \InvalidArgumentException('El certificado o la clave no parecen tener formato PEM válido');
        }

        $dir = rtrim(config('afip.storage_path'), '/').'/'.$entorno.'/';
        if (! is_dir($dir) && ! mkdir($dir, 0700, true) && ! is_dir($dir)) {
            throw new \RuntimeException("No se pudo crear el directorio {$dir}");
        }

        if (file_put_contents($dir.'cert', $cert) === false) {
            throw new \RuntimeException("No se pudo escribir el certificado en {$dir}cert");
        }
        @chmod($dir.'cert', 0600);

        if (file_put_contents($dir.'key', $key) === false) {
            throw new \RuntimeException("No se pudo escribir la clave en {$dir}key");
        }
        @chmod($dir.'key', 0600);
    }

    /** Prueba la conexión con AFIP para un entorno. No filtra detalles al usuario. */
    public function probar(string $entorno): array
    {
        try {
            $this->validarEntorno($entorno);
            $cfg = AfipConfig::where('entorno', $entorno)->firstOrFail();
            $afip = $this->instanciaDesde($cfg);
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
            Log::warning('AFIP tiposComprobante() falló', ['entorno' => $entorno, 'error' => $e->getMessage()]);

            return [];
        }
    }

    /** @throws \InvalidArgumentException si el entorno no es homo/prod (evita path traversal). */
    private function validarEntorno(string $entorno): void
    {
        if (! in_array($entorno, self::ENTORNOS, true)) {
            throw new \InvalidArgumentException("Entorno AFIP inválido: {$entorno}");
        }
    }

    private function instanciaDesde(AfipConfig $cfg): \Afip
    {
        $dir = $cfg->rutaStorage();

        return new \Afip([
            'CUIT'       => floatval($cfg->cuit),
            'production' => $cfg->entorno === 'prod',
            'res_folder' => $dir,
            'ta_folder'  => $dir,
        ]);
    }
}
