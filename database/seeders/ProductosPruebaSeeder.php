<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Puebla la base con ~100 productos de indumentaria + stock para la sucursal
 * existente, para poder testear ventas y facturación AFIP.
 *
 * Idempotente: marca lo que crea con usuario = self::MARCA y, al re-ejecutarse,
 * borra primero esos productos/stock. NUNCA toca datos reales (otra marca).
 *
 *   php artisan db:seed --class=ProductosPruebaSeeder
 */
class ProductosPruebaSeeder extends Seeder
{
    /** Marca para identificar (y poder borrar) los datos de prueba. */
    private const MARCA = 'seed_prueba';

    /** Sucursal destino del stock (la única existente). */
    private const SUCURSAL_ID = 2;

    /** Cantidad de productos a generar. */
    private const CANTIDAD = 100;

    /** Prefijo numérico para códigos de barras de prueba. */
    private const BARCODE_PREFIX = '200';

    public function run()
    {
        // Rango de precios solicitado (pesos).
        $precioMin = 50000;
        $precioMax = 100000;

        $categorias = [
            ['nombre' => 'Remeras',    'abrev' => 'RE'],
            ['nombre' => 'Pantalones', 'abrev' => 'PA'],
            ['nombre' => 'Camperas',   'abrev' => 'CA'],
            ['nombre' => 'Buzos',      'abrev' => 'BU'],
            ['nombre' => 'Camisas',    'abrev' => 'CM'],
            ['nombre' => 'Vestidos',   'abrev' => 'VE'],
            ['nombre' => 'Jeans',      'abrev' => 'JE'],
            ['nombre' => 'Accesorios', 'abrev' => 'AC'],
        ];

        // Sin acentos a propósito: evita problemas de encoding latin1/utf8 en la
        // pantalla legacy, el payload a AFIP y el PDF (tcpdf) con datos de prueba.
        $prendaPorCategoria = [
            'Remeras'    => ['Remera', 'Remera oversize', 'Musculosa', 'Remera manga larga'],
            'Pantalones' => ['Pantalon', 'Pantalon cargo', 'Jogger', 'Pantalon de vestir'],
            'Camperas'   => ['Campera', 'Campera inflable', 'Rompevientos', 'Camperon'],
            'Buzos'      => ['Buzo', 'Buzo canguro', 'Buzo con capucha', 'Hoodie'],
            'Camisas'    => ['Camisa', 'Camisa de lino', 'Camisa oxford', 'Camisa a cuadros'],
            'Vestidos'   => ['Vestido', 'Vestido largo', 'Vestido camisero', 'Vestido de fiesta'],
            'Jeans'      => ['Jean', 'Jean chupin', 'Jean recto', 'Jean mom'],
            'Accesorios' => ['Gorra', 'Bufanda', 'Cinturon', 'Medias pack x3'],
        ];

        $materiales = ['Algodon', 'Lino', 'Lana', 'Denim', 'Frisa', 'Gabardina', 'Modal'];
        $colores    = ['Negro', 'Blanco', 'Azul', 'Gris', 'Verde', 'Bordo', 'Beige', 'Camel'];
        $talles     = ['S', 'M', 'L', 'XL', 'XXL'];

        DB::transaction(function () use (
            $categorias, $prendaPorCategoria, $materiales, $colores, $talles, $precioMin, $precioMax
        ) {
            $this->limpiarDatosPrevios();

            // 1) Categorías (reutiliza por nombre si ya existen).
            $catIds = [];
            foreach ($categorias as $cat) {
                $existente = DB::table('categorias')->where('nombre', $cat['nombre'])->first();
                if ($existente) {
                    $catId = $existente->id;
                } else {
                    $catId = DB::table('categorias')->insertGetId([
                        'nombre'      => $cat['nombre'],
                        'abreviatura' => $cat['abrev'],
                        'habilitada'  => 1,
                        'usuario'     => self::MARCA,
                    ]);
                }
                // categorias_id en productos se guarda como "{id}_{abreviatura}".
                $catIds[$cat['nombre']] = $catId.'_'.$cat['abrev'];
            }

            // 2) Productos + stock por sucursal.
            $ahora     = date('Y-m-d H:i:s');
            $nombresCat = array_keys($prendaPorCategoria);
            $insertados = 0;

            for ($i = 1; $i <= self::CANTIDAD; $i++) {
                $nombreCat = $nombresCat[($i - 1) % count($nombresCat)];
                $prendas   = $prendaPorCategoria[$nombreCat];

                $prenda   = $prendas[($i) % count($prendas)];
                $material = $materiales[($i * 3) % count($materiales)];
                $color    = $colores[($i * 5) % count($colores)];
                $talle    = $talles[($i * 2) % count($talles)];

                $nombre = "{$prenda} {$material} {$color} - Talle {$talle}";

                $precio = $precioMin + (($i * 997) % ($precioMax - $precioMin + 1));
                $costo  = (int) round($precio * 0.55);
                $mayor  = (int) round($precio * 0.90);

                // Stock diverso por sucursal (siempre > 0 para que sea facturable).
                $stock        = (($i * 7) % 58) + 3;   // 3..60
                $stockMinimo  = 5;

                $codigoBarras = self::BARCODE_PREFIX.str_pad((string) $i, 10, '0', STR_PAD_LEFT);

                $productoId = DB::table('productos')->insertGetId([
                    'codigo_barras'     => $codigoBarras,
                    'nombre'            => $nombre,
                    'precio_unidad'     => (string) $precio,
                    'costo'             => (string) $costo,
                    'stock'             => $stock,
                    'stock_minimo'      => $stockMinimo,
                    'proveedores_id'    => null,
                    'categorias_id'     => $catIds[$nombreCat],
                    'usuario'           => self::MARCA,
                    'fecha'             => $ahora,
                    'precio_mayorista'  => (string) $mayor,
                    'es_comodato'       => 0,
                    'descripcion'       => "{$prenda} de {$material}, color {$color}. Producto de prueba.",
                    'precio_reposicion' => (string) $costo,
                    'created_at'        => $ahora,
                    'updated_at'        => $ahora,
                ]);

                DB::table('stock')->insert([
                    'stock'        => $stock,
                    'stock_minimo' => $stockMinimo,
                    'sucursal_id'  => self::SUCURSAL_ID,
                    'usuario'      => self::MARCA,
                    'productos_id' => $productoId,
                    'created_at'   => $ahora,
                    'updated_at'   => $ahora,
                ]);

                $insertados++;
            }

            $this->command->info("Productos de prueba creados: {$insertados} (sucursal {$this->sucursalId()}).");
        });
    }

    private function sucursalId(): int
    {
        return self::SUCURSAL_ID;
    }

    /** Borra productos/stock de una corrida anterior del seeder (por marca). */
    private function limpiarDatosPrevios(): void
    {
        $ids = DB::table('productos')->where('usuario', self::MARCA)->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }

        DB::table('stock')->whereIn('productos_id', $ids)->delete();
        DB::table('productos')->whereIn('id', $ids)->delete();

        $this->command->info("Limpieza: {$ids->count()} productos de prueba previos eliminados.");
    }
}
