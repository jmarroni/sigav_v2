<?php

namespace App\Console\Commands;

use App\Catalogo\CatalogoCsvParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Reemplaza el catálogo de productos por el del CSV.
 *
 * Por defecto corre en dry-run (no escribe). Con --force ejecuta la limpieza
 * total + carga dentro de una transacción.
 */
class ImportarCatalogo extends Command
{
    protected $signature = 'catalogo:importar
        {--path= : Ruta del CSV (default: CODIGOS PRODUCTOS.csv en la raíz)}
        {--force : Ejecuta de verdad (sin esto es dry-run y no escribe nada)}';

    protected $description = 'Importa el catálogo desde CSV: borra el catálogo actual y carga el nuevo';

    /** Tablas a vaciar, en orden hijo -> padre. */
    private const TABLAS_WIPE = [
        'ventas',
        'productos_en_carrito',
        'descuentos_logs',
        'factura',
        'stock_logs',
        'stock',
        'productos',
    ];

    public function handle(): int
    {
        $path = $this->option('path') ?: base_path('CODIGOS PRODUCTOS.csv');
        if (!is_file($path)) {
            $this->error("No se encontró el CSV en: $path");
            return 1;
        }

        $csv = file_get_contents($path);
        if ($csv === false) {
            $this->error("No se pudo leer el CSV en: $path");
            return 1;
        }

        $parsed = CatalogoCsvParser::parse($csv);
        $productos = $parsed['productos'];

        if (count($productos) === 0) {
            $this->error('El CSV no tiene productos válidos.');
            return 1;
        }

        $sucursal = DB::table('sucursales')->orderBy('id')->first();
        if (!$sucursal) {
            $this->error('No hay sucursales cargadas; no se puede asignar stock.');
            return 1;
        }

        $proveedores = $this->proveedoresDistintos($productos);

        $this->info('Resumen del CSV:');
        $this->line('  Productos válidos:   ' . count($productos));
        $this->line('  Filas omitidas:      ' . $parsed['omitidas']);
        $this->line('  Códigos generados:   ' . $parsed['codigosGenerados']);
        $this->line('  Proveedores:         ' . implode(', ', $proveedores));
        $this->line('  Stock va a sucursal: ' . $sucursal->id . ' (' . $sucursal->nombre . ')');

        if (!$this->option('force')) {
            $this->line('');
            $this->warn('DRY-RUN: no se escribió nada.');
            $this->line('Se BORRARÍAN (limpieza total): ' . implode(', ', self::TABLAS_WIPE));
            $this->line('Se crearían ' . count($proveedores) . ' proveedores + ' . count($proveedores) . ' categorías y ' . count($productos) . ' productos.');
            $this->line('');
            $this->line('Ejemplos de productos a crear:');
            foreach (array_slice($productos, 0, 3) as $p) {
                $this->line(sprintf(
                    '  [%s] %s | venta %s | mayor %s | costo %s | stock %d | %s',
                    $p['codigo_barras'],
                    $p['nombre'],
                    number_format($p['precio_unidad'], 2, ',', '.'),
                    number_format($p['precio_mayorista'], 2, ',', '.'),
                    number_format($p['costo'], 2, ',', '.'),
                    $p['stock'],
                    $p['proveedor']
                ));
            }
            $this->line('');
            $this->info('Para ejecutar de verdad: php artisan catalogo:importar --force');
            return 0;
        }

        $this->ejecutar($productos, $proveedores, (int) $sucursal->id);

        return 0;
    }

    /**
     * @param array<int, array<string, mixed>> $productos
     * @return array<int, string>
     */
    private function proveedoresDistintos(array $productos): array
    {
        $set = [];
        foreach ($productos as $p) {
            $nombre = $p['proveedor'];
            if ($nombre !== '') {
                $set[$nombre] = true;
            }
        }
        return array_keys($set);
    }

    /**
     * @param array<int, array<string, mixed>> $productos
     * @param array<int, string> $proveedores
     */
    private function ejecutar(array $productos, array $proveedores, int $sucursalId): void
    {
        $ahora = date('Y-m-d H:i:s');

        DB::transaction(function () use ($productos, $proveedores, $sucursalId, $ahora) {
            // 1. Limpieza total (hijos primero)
            foreach (self::TABLAS_WIPE as $tabla) {
                DB::table($tabla)->delete();
            }
            $this->info('Catálogo y datos transaccionales de prueba borrados.');

            // 2. Proveedores + 3. categorías (una por proveedor)
            $provIds = [];        // nombre -> proveedores_id
            $catRef = [];         // nombre -> "{cat_id}_{abrev}"
            $abrevUsadas = [];
            foreach ($proveedores as $nombre) {
                $provId = DB::table('proveedores')->insertGetId(['nombre' => $nombre]);
                $provIds[$nombre] = $provId;

                $abrev = $this->abreviatura($nombre, $abrevUsadas);
                $abrevUsadas[$abrev] = true;
                $catId = DB::table('categorias')->insertGetId([
                    'nombre'      => $nombre,
                    'abreviatura' => $abrev,
                    'habilitada'  => 1,
                    'usuario'     => 'import',
                ]);
                $catRef[$nombre] = $catId . '_' . $abrev;
            }
            $this->info(count($proveedores) . ' proveedores y categorías creados.');

            // 4. Productos + stock
            $bar = $this->output->createProgressBar(count($productos));
            foreach ($productos as $p) {
                $prov = $p['proveedor'];
                $productoId = DB::table('productos')->insertGetId([
                    'codigo_barras'     => $p['codigo_barras'],
                    'nombre'            => $p['nombre'],
                    'precio_unidad'     => sprintf('%.2f', $p['precio_unidad']),
                    'costo'             => sprintf('%.2f', $p['costo']),
                    'precio_mayorista'  => sprintf('%.2f', $p['precio_mayorista']),
                    'precio_reposicion' => sprintf('%.2f', $p['costo']),
                    'stock'             => $p['stock'],
                    'stock_minimo'      => 0,
                    'proveedores_id'    => $provIds[$prov] ?? null,
                    'categorias_id'     => $catRef[$prov] ?? '',
                    'es_comodato'       => 0,
                    'descuento'         => 0,
                    'descripcion'       => $p['nombre'],
                    'usuario'           => 'import',
                    'fecha'             => $ahora,
                    'created_at'        => $ahora,
                    'updated_at'        => $ahora,
                ]);

                DB::table('stock')->insert([
                    'stock'        => $p['stock'],
                    'stock_minimo' => 0,
                    'sucursal_id'  => $sucursalId,
                    'usuario'      => 'import',
                    'productos_id' => $productoId,
                    'created_at'   => $ahora,
                    'updated_at'   => $ahora,
                ]);
                $bar->advance();
            }
            $bar->finish();
            $this->line('');
        });

        $this->info('Importación completa: ' . count($productos) . ' productos cargados.');
    }

    /**
     * Abreviatura de categoría a partir del nombre del proveedor: primera
     * palabra en mayúsculas, solo alfanumérico, única dentro del set.
     *
     * @param array<string, bool> $usadas
     */
    private function abreviatura(string $nombre, array $usadas): string
    {
        $primera = strtok(trim($nombre), ' ') ?: $nombre;
        $base = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $primera) ?: 'CAT');
        $base = substr($base, 0, 15);

        $abrev = $base;
        $i = 1;
        while (isset($usadas[$abrev])) {
            $abrev = substr($base, 0, 13) . $i;
            $i++;
        }
        return $abrev;
    }
}
