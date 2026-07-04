<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Unifica los proveedores del catalogo en la tabla `proveedor` (singular), que
 * es la que usa toda la app (pantalla de Productos, etiquetas, reportes, APIs).
 *
 * El import masivo habia guardado los proveedores en la tabla `proveedores`
 * (plural) y dejado `productos.proveedores_id` apuntando ahi, pero la app lee
 * la singular -> el proveedor mostrado quedaba corrido (SALPA -> "PROVEEDOR
 * UNICO", NAKA -> "SALPA", etc.).
 *
 * Fuente de verdad: el CSV (codigo -> nombre de proveedor). Idempotente.
 *
 * Ademas reconstruye `relacion_categoria_proveedor` (que estaba toda apuntando
 * a "Indumentaria") con las categorias reales de cada proveedor, para que el
 * combo de categoria del form filtre bien.
 *
 * Dry-run por defecto. Con --force escribe (en una transaccion).
 */
class UnificarProveedores extends Command
{
    protected $signature = 'catalogo:unificar-proveedores
        {--path= : Ruta del CSV (default: CODIGOS PRODUCTOS.csv en la raiz)}
        {--force : Ejecuta de verdad (sin esto es dry-run)}';

    protected $description = 'Unifica proveedores en la tabla singular y reconstruye la relacion categoria-proveedor';

    public function handle(): int
    {
        $path = $this->option('path') ?: base_path('CODIGOS PRODUCTOS.csv');
        if (!is_file($path)) {
            $this->error("No se encontro el CSV en: $path");
            return 1;
        }

        // 1. CSV: codigo -> nombre de proveedor (ASCII).
        $csvProv = [];       // codigo => nombreAscii
        $nombresCsv = [];    // nombreAscii => true
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $i => $ln) {
            if ($i === 0) continue; // header
            $c = explode(';', $ln);
            if (count($c) < 7) continue;
            $codigo = trim($c[0]);
            $nombre = $this->aAscii($c[6]);
            if ($codigo === '' || $nombre === '') continue;
            $csvProv[$codigo] = $nombre;
            $nombresCsv[$nombre] = true;
        }

        // Fallback para productos cuyo codigo no este en el CSV: nombre segun el
        // valor actual de proveedores_id leido de la tabla plural.
        $pluralNombre = [];  // pluralId => nombreAscii
        foreach (DB::table('proveedores')->get() as $pp) {
            $pluralNombre[$pp->id] = $this->aAscii($pp->nombre);
            $nombresCsv[$this->aAscii($pp->nombre)] = true;
        }

        // 2. Asegura cada proveedor en la tabla singular; mapa nombreAscii -> id.
        $singId = [];        // nombreAscii => proveedor.id (singular)
        foreach (array_keys($nombresCsv) as $nombre) {
            $singId[$nombre] = $this->asegurarProveedorSingular($nombre);
        }

        // 3. Productos del import y su proveedor destino (id singular).
        $productos = DB::table('productos')->where('usuario', 'import')
            ->select('id', 'codigo_barras', 'categorias_id', 'proveedores_id')->get();

        $plan = [];          // productoId => singularId
        $distProv = [];      // nombre => count
        $sinResolver = 0;
        foreach ($productos as $p) {
            $nombre = $csvProv[$p->codigo_barras] ?? ($pluralNombre[$p->proveedores_id] ?? null);
            if ($nombre === null || !isset($singId[$nombre])) {
                $sinResolver++;
                continue;
            }
            $plan[$p->id] = $singId[$nombre];
            $distProv[$nombre] = ($distProv[$nombre] ?? 0) + 1;
        }

        // 4. Pares (proveedor_singular_id, categoria_id) reales para la relacion.
        $rel = [];           // "prov_cat" => [provId, catId]
        foreach ($productos as $p) {
            if (!isset($plan[$p->id])) continue;
            $provId = $plan[$p->id];
            $catId = (int) $p->categorias_id; // "10_RE" -> 10
            if ($catId <= 0) continue;
            $rel[$provId . '_' . $catId] = [$provId, $catId];
        }

        // Resumen.
        $this->info('Proveedores destino (tabla singular):');
        arsort($distProv);
        foreach ($distProv as $nombre => $n) {
            $this->line(sprintf('  %-24s id=%-3d  %d productos', $nombre, $singId[$nombre], $n));
        }
        $this->line('');
        $this->info('Productos a repuntar: ' . count($plan) . ($sinResolver ? "  (sin resolver: {$sinResolver})" : ''));
        $this->info('Relaciones categoria-proveedor a crear: ' . count($rel));

        if (!$this->option('force')) {
            $this->line('');
            $this->warn('DRY-RUN: no se escribio nada. Para aplicar: php artisan catalogo:unificar-proveedores --force');
            return 0;
        }

        DB::transaction(function () use ($plan, $rel, $singId) {
            // Repunta proveedores_id de cada producto a la tabla singular.
            foreach ($plan as $productoId => $provId) {
                DB::table('productos')->where('id', $productoId)->update(['proveedores_id' => $provId]);
            }

            // Reconstruye la relacion: borra lo viejo de estos proveedores y de la
            // categoria "Indumentaria", e inserta los pares reales.
            $provIds = array_values(array_unique(array_map(fn ($r) => $r[0], $rel)));
            if ($provIds) {
                DB::table('relacion_categoria_proveedor')->whereIn('proveedor_id', $provIds)->delete();
            }
            $indumentaria = DB::table('categorias')->where('nombre', 'Indumentaria')->pluck('id')->all();
            if ($indumentaria) {
                DB::table('relacion_categoria_proveedor')->whereIn('categoria_id', $indumentaria)->delete();
            }
            foreach ($rel as [$provId, $catId]) {
                DB::table('relacion_categoria_proveedor')->insert([
                    'proveedor_id' => $provId,
                    'categoria_id' => $catId,
                ]);
            }
        });

        $this->line('');
        $this->info('Listo. Proveedores unificados en la tabla singular y relacion categoria-proveedor reconstruida.');
        return 0;
    }

    /**
     * Asegura un proveedor (por nombre ASCII completo) en la tabla singular y
     * devuelve su id. Compara contra nombre+apellido normalizados.
     */
    private function asegurarProveedorSingular(string $nombreAscii): int
    {
        foreach (DB::table('proveedor')->get() as $pr) {
            $full = $this->aAscii(trim($pr->nombre . ' ' . ($pr->apellido ?? '')));
            if ($full === $nombreAscii) {
                // Normaliza nombre/apellido si tenian mojibake.
                if ($this->aAscii($pr->nombre) !== $pr->nombre || $this->aAscii($pr->apellido ?? '') !== ($pr->apellido ?? '')) {
                    DB::table('proveedor')->where('id', $pr->id)->update([
                        'nombre'   => $this->aAscii($pr->nombre),
                        'apellido' => $this->aAscii($pr->apellido ?? ''),
                    ]);
                }
                return $pr->id;
            }
        }
        return DB::table('proveedor')->insertGetId([
            'nombre'   => $nombreAscii,
            'apellido' => '',
            'usuario'  => 'import',
        ]);
    }

    /** Translitera a ASCII (sin acentos/ni, sin mojibake) para tablas latin1. */
    private function aAscii(string $s): string
    {
        $map = [
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n',
            'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N',
            'à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u','â'=>'a','ê'=>'e',
        ];
        $s = strtr($s, $map);
        $s = preg_replace('/[^\x20-\x7E]/', '', $s);
        return trim(preg_replace('/\s+/', ' ', $s));
    }
}
