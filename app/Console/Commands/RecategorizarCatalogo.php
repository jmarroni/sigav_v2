<?php

namespace App\Console\Commands;

use App\Catalogo\CategoriaClasificador;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Corrige el catalogo importado con `catalogo:importar`: los productos habian
 * quedado categorizados por PROVEEDOR (categorias 10..13 = SALPA/NAKA/SOX/JLS).
 * Este comando los recategoriza por TIPO DE PRENDA y deja las categorias
 * proveedor (usuario='import') deshabilitadas.
 *
 * - Idempotente: solo toca productos que aun apuntan a una categoria-proveedor.
 * - Tambien normaliza a ASCII el nombre de los proveedores (arregla el mojibake
 *   "SOX PIG?E SA" -> "SOX PIGUE SA").
 *
 * Dry-run por defecto. Con --force escribe (todo dentro de una transaccion).
 */
class RecategorizarCatalogo extends Command
{
    protected $signature = 'catalogo:recategorizar
        {--force : Ejecuta de verdad (sin esto es dry-run)}
        {--all : Reclasifica TODOS los productos usuario=import (no solo los que apuntan a categoria-proveedor)}';

    protected $description = 'Recategoriza el catalogo por tipo de prenda (arregla la categorizacion por proveedor)';

    public function handle(): int
    {
        // Categorias creadas por el import: una por proveedor. Son las "malas".
        $bogus = DB::table('categorias')->where('usuario', 'import')->get();
        $bogusIds = $bogus->pluck('id')->all();

        if (empty($bogusIds)) {
            $this->warn('No hay categorias con usuario=import (nada por proveedor que corregir).');
            $this->line('Igual reviso proveedores con encoding roto y salgo.');
        } else {
            $this->info('Categorias-proveedor detectadas (se van a deshabilitar):');
            foreach ($bogus as $c) {
                $this->line("  [{$c->id}] {$c->nombre} ({$c->abreviatura})");
            }
        }

        // Productos a reclasificar: con --all, todo el catalogo importado; sin
        // --all, solo los que todavia apuntan a una categoria-proveedor.
        $productos = DB::table('productos')
            ->when($this->option('all'), function ($q) {
                $q->where('usuario', 'import');
            }, function ($q) use ($bogusIds) {
                if (empty($bogusIds)) {
                    $q->whereRaw('1 = 0');
                } else {
                    $q->whereIn(DB::raw('CAST(categorias_id AS UNSIGNED)'), $bogusIds);
                }
            })
            ->select('id', 'nombre', 'categorias_id')
            ->get();

        $this->line('');
        $this->info('Productos a recategorizar: ' . $productos->count());

        // Plan de recategorizacion (nombre -> tipo) para mostrar distribucion.
        $plan = [];       // productoId => [catNombre, catAbrev]
        $dist = [];
        foreach ($productos as $p) {
            [$catNombre, $catAbrev] = CategoriaClasificador::clasificar($p->nombre);
            $plan[$p->id] = [$catNombre, $catAbrev];
            $dist[$catNombre] = ($dist[$catNombre] ?? 0) + 1;
        }
        arsort($dist);
        $this->line('Distribucion resultante por tipo:');
        foreach ($dist as $cat => $n) {
            $this->line(sprintf('  %-14s %d', $cat, $n));
        }

        // Proveedores con nombre no-ASCII (mojibake) que hay que normalizar.
        $provFix = [];
        foreach (DB::table('proveedores')->get() as $pr) {
            $ascii = $this->aAscii($pr->nombre);
            if ($ascii !== $pr->nombre) {
                $provFix[$pr->id] = [$pr->nombre, $ascii];
            }
        }
        if ($provFix) {
            $this->line('');
            $this->info('Proveedores a normalizar (ASCII):');
            foreach ($provFix as $id => [$de, $a]) {
                $this->line("  [{$id}] '{$de}' -> '{$a}'");
            }
        }

        if (!$this->option('force')) {
            $this->line('');
            $this->warn('DRY-RUN: no se escribio nada. Para aplicar: php artisan catalogo:recategorizar --force');
            return 0;
        }

        DB::transaction(function () use ($plan, $provFix, $bogusIds) {
            // 1. Asegura las categorias por tipo y arma el mapa nombre -> "{id}_{abrev}".
            $ref = [];
            foreach (CategoriaClasificador::categorias() as [$nombre, $abrev]) {
                $ref[$nombre] = $this->asegurarCategoria($nombre, $abrev);
            }

            // 2. Reasigna cada producto.
            foreach ($plan as $productoId => [$catNombre]) {
                DB::table('productos')->where('id', $productoId)
                    ->update(['categorias_id' => $ref[$catNombre]]);
            }

            // 3. Normaliza proveedores.
            foreach ($provFix as $id => [, $ascii]) {
                DB::table('proveedores')->where('id', $id)->update(['nombre' => $ascii]);
            }

            // 4. Deshabilita las categorias-proveedor (ya nadie las usa).
            if (!empty($bogusIds)) {
                DB::table('categorias')->whereIn('id', $bogusIds)->update(['habilitada' => 0]);
            }
        });

        $this->line('');
        $this->info('Listo. Productos recategorizados: ' . count($plan)
            . ' | proveedores normalizados: ' . count($provFix)
            . ' | categorias-proveedor deshabilitadas: ' . count($bogusIds));

        return 0;
    }

    /**
     * Devuelve "{id}_{abreviatura}" de la categoria; la reusa por nombre si ya
     * existe (respetando su abreviatura), o la crea. Evita colision de abrev.
     */
    private function asegurarCategoria(string $nombre, string $abrev): string
    {
        $cat = DB::table('categorias')->whereRaw('LOWER(nombre) = ?', [mb_strtolower($nombre)])->first();
        if ($cat) {
            $ab = $cat->abreviatura ?: $abrev;
            return $cat->id . '_' . $ab;
        }

        // Evita chocar con una abreviatura ya usada por otra categoria distinta.
        $ab = $abrev;
        $i = 1;
        while (DB::table('categorias')->where('abreviatura', $ab)->exists()) {
            $ab = substr($abrev, 0, 3) . $i;
            $i++;
        }

        $id = DB::table('categorias')->insertGetId([
            'nombre'      => $nombre,
            'abreviatura' => $ab,
            'habilitada'  => 1,
            'usuario'     => 'recat',
        ]);
        return $id . '_' . $ab;
    }

    /** Translitera a ASCII (sin acentos/ñ, sin mojibake). */
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
