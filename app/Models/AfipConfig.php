<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AfipConfig extends Model
{
    protected $table = 'afip_config';

    protected $fillable = [
        'entorno', 'cuit', 'ptovta', 'comprobante', 'condicion_iva',
        'inicio_actividades', 'ingresos_brutos', 'emitir', 'solicitar_datos',
    ];

    protected $casts = [
        'emitir'          => 'boolean',
        'solicitar_datos' => 'boolean',
        'activo'          => 'boolean',
    ];

    /** Entorno actualmente activo (el switch). */
    public static function activa(): ?self
    {
        return static::where('activo', true)->first();
    }

    /** Activa un entorno de forma atómica (exactamente uno queda activo). */
    public static function activar(string $entorno): void
    {
        DB::transaction(function () use ($entorno) {
            static::query()->update(['activo' => false]);
            static::where('entorno', $entorno)->update(['activo' => true]);
        });
    }

    /** Carpeta (con trailing slash) de credenciales de este entorno, fuera del webroot. */
    public function rutaStorage(): string
    {
        return rtrim(config('afip.storage_path'), '/') . '/' . $this->entorno . '/';
    }

    /** ¿Tiene cert y key cargados? */
    public function tieneCredenciales(): bool
    {
        $dir = $this->rutaStorage();
        return is_file($dir . 'cert') && is_file($dir . 'key');
    }
}
