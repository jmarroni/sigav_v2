<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ApiProductosFormatoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('sucursales', function (Blueprint $t) {
            $t->increments('id');
            $t->string('nombre');
            $t->string('direccion')->nullable();
            $t->string('provincia')->nullable();
            $t->string('codigo_postal')->nullable();
        });
        Schema::create('relacion_users_sucursales', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('sucursal_id');
            $t->integer('user_id');
        });
        Schema::create('categorias', function (Blueprint $t) {
            $t->increments('id');
            $t->string('nombre');
        });
        Schema::create('proveedor', function (Blueprint $t) {
            $t->increments('id');
            $t->string('nombre');
            $t->string('apellido')->nullable();
        });
        Schema::create('productos', function (Blueprint $t) {
            $t->increments('id');
            $t->string('codigo_barras');
            $t->string('nombre');
            $t->string('descripcion')->nullable();
            $t->string('precio_unidad');
            $t->string('precio_mayorista')->nullable();
            $t->integer('stock')->default(0);
            $t->integer('stock_minimo')->default(0);
            $t->string('usuario')->nullable();
            $t->string('fecha')->nullable();
            $t->integer('es_comodato')->default(0);
            $t->unsignedTinyInteger('envio_tamano')->nullable();
            $t->string('descripcion_en')->nullable();
            $t->string('descripcion_pr')->nullable();
            $t->string('material')->nullable();
            $t->boolean('venta_online')->default(false);
            $t->integer('proveedores_id');
            $t->integer('categorias_id');
        });
        Schema::create('stock', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('productos_id');
            $t->integer('sucursal_id');
            $t->integer('stock')->default(0);
        });
        Schema::create('imagen_producto', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('productos_id');
            $t->string('imagen_url');
        });

        $this->artisan('passport:client', ['--personal' => true, '--name' => 'test']);

        User::create([
            'name' => 'E-commerce',
            'email' => 'ecom@example.com',
            'password' => bcrypt('clave-segura-123'),
        ]);

        DB::table('sucursales')->insert([
            ['id' => 1, 'nombre' => 'Ingeniero Jacobacci', 'codigo_postal' => '8418', 'direccion' => null, 'provincia' => null],
            ['id' => 2, 'nombre' => 'Viedma', 'codigo_postal' => '8500', 'direccion' => 'San Martín 123', 'provincia' => 'Río Negro'],
        ]);
        DB::table('relacion_users_sucursales')->insert(['sucursal_id' => 1, 'user_id' => 1]);
        DB::table('categorias')->insert(['id' => 1, 'nombre' => 'CUERO']);
        DB::table('proveedor')->insert(['id' => 1, 'nombre' => 'PLACIDO', 'apellido' => 'DEL CASTILLO']);
        DB::table('productos')->insert([
            ['id' => 1, 'codigo_barras' => '20_ACUE00224561269', 'nombre' => 'CUCHILLO', 'precio_unidad' => '60.000',
                'precio_mayorista' => null, 'stock' => 3, 'proveedores_id' => 1, 'categorias_id' => 1, 'envio_tamano' => 2, 'venta_online' => 1],
            ['id' => 2, 'codigo_barras' => '13_ACER00076217185', 'nombre' => 'MATE', 'precio_unidad' => '1.200,000',
                'precio_mayorista' => '2025-07-02', 'stock' => 1, 'proveedores_id' => 1, 'categorias_id' => 1, 'envio_tamano' => null, 'venta_online' => 1],
            // No marcado para venta online: no tiene que salir en la web.
            ['id' => 3, 'codigo_barras' => '99_OFFLINE', 'nombre' => 'SOLO MOSTRADOR', 'precio_unidad' => '5000',
                'precio_mayorista' => null, 'stock' => 1, 'proveedores_id' => 1, 'categorias_id' => 1, 'envio_tamano' => null, 'venta_online' => 0],
            // Marcado, pero con precio que no sirve: tampoco se publica.
            ['id' => 4, 'codigo_barras' => '99_SINPRECIO', 'nombre' => 'SIN PRECIO', 'precio_unidad' => '2025-07-02',
                'precio_mayorista' => null, 'stock' => 1, 'proveedores_id' => 1, 'categorias_id' => 1, 'envio_tamano' => null, 'venta_online' => 1],
        ]);
        config(['ecommerce.recargo_online_pct' => 10]);
        // Producto 1 dado de alta en Jacobacci y luego también en Viedma: manda la primera fila.
        DB::table('stock')->insert([
            ['productos_id' => 1, 'sucursal_id' => 1, 'stock' => 0],
            ['productos_id' => 1, 'sucursal_id' => 2, 'stock' => 7],
            ['productos_id' => 2, 'sucursal_id' => 2, 'stock' => 2],
            ['productos_id' => 3, 'sucursal_id' => 1, 'stock' => 4],
            ['productos_id' => 4, 'sucursal_id' => 1, 'stock' => 4],
        ]);
    }

    private function listado()
    {
        $token = $this->postJson('/api/auth/login', [
            'email' => 'ecom@example.com',
            'password' => 'clave-segura-123',
        ])->assertOk()->json('access_token');

        return $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/productos')
            ->assertStatus(201)
            ->json();
    }

    /** @test */
    public function los_precios_salen_con_el_recargo_online_y_un_formato_unico()
    {
        $productos = collect($this->listado())->keyBy('codigo_barras');

        // 60.000 y 1.200,000 + 10%, redondeado al entero.
        $this->assertSame('66000.00', $productos['20_ACUE00224561269']['precio_unidad']);
        $this->assertNull($productos['20_ACUE00224561269']['precio_mayorista']);
        $this->assertSame('1320.00', $productos['13_ACER00076217185']['precio_unidad']);
        $this->assertNull($productos['13_ACER00076217185']['precio_mayorista']);
    }

    /** @test */
    public function el_tamano_de_envio_se_traduce_a_medidas_y_peso()
    {
        $productos = collect($this->listado())->keyBy('codigo_barras');

        $this->assertSame(
            ['peso_gramos' => 1000, 'alto_cm' => 15, 'ancho_cm' => 20, 'largo_cm' => 25],
            array_intersect_key($productos['20_ACUE00224561269'], array_flip(['peso_gramos', 'alto_cm', 'ancho_cm', 'largo_cm']))
        );
        $this->assertNull($productos['13_ACER00076217185']['peso_gramos']);
        $this->assertNull($productos['13_ACER00076217185']['largo_cm']);
    }

    /** @test */
    public function la_delegacion_es_la_sucursal_del_primer_stock_con_su_codigo_postal()
    {
        $productos = collect($this->listado())->keyBy('codigo_barras');

        $this->assertSame('Ingeniero Jacobacci', $productos['20_ACUE00224561269']['delegacion']);
        $this->assertSame('8418', $productos['20_ACUE00224561269']['delegacion_codigo_postal']);
        $this->assertSame('Viedma', $productos['13_ACER00076217185']['delegacion']);
    }

    /** @test */
    public function el_stock_del_listado_es_el_de_la_sucursal_ecommerce()
    {
        config(['ecommerce.sucursal_id' => 2]);
        $productos = collect($this->listado())->keyBy('codigo_barras');

        $this->assertSame(7, $productos['20_ACUE00224561269']['stock']);
        $this->assertSame(2, $productos['13_ACER00076217185']['stock']);
    }

    /** @test */
    public function sin_sucursal_ecommerce_configurada_el_stock_figura_en_cero()
    {
        config(['ecommerce.sucursal_id' => null]);
        $productos = collect($this->listado())->keyBy('codigo_barras');

        $this->assertSame(0, $productos['20_ACUE00224561269']['stock']);
    }

    /** @test */
    public function cada_producto_trae_la_sucursal_ecommerce_donde_tiene_stock()
    {
        config(['ecommerce.sucursal_id' => 2]);
        DB::table('stock')->where(['productos_id' => 2, 'sucursal_id' => 2])->update(['stock' => 0]);
        $productos = collect($this->listado())->keyBy('codigo_barras');

        $this->assertSame([[
            'id' => 2,
            'nombre' => 'Viedma',
            'direccion' => 'San Martín 123',
            'provincia' => 'Río Negro',
            'codigo_postal' => '8500',
            'stock' => 7,
        ]], $productos['20_ACUE00224561269']['sucursales']);
        // Sin stock en la sucursal del e-commerce: lista vacía.
        $this->assertSame([], $productos['13_ACER00076217185']['sucursales']);
    }

    /** @test */
    public function sin_sucursal_ecommerce_configurada_no_se_informan_sucursales()
    {
        config(['ecommerce.sucursal_id' => null]);
        $productos = collect($this->listado())->keyBy('codigo_barras');

        $this->assertSame([], $productos['20_ACUE00224561269']['sucursales']);
    }

    /** @test */
    public function incluye_el_id_del_proveedor()
    {
        $producto = collect($this->listado())->firstWhere('codigo_barras', '20_ACUE00224561269');

        $this->assertSame(1, $producto['proveedor_id']);
        $this->assertSame('PLACIDO', $producto['nombre_proveedor']);
        $this->assertNull($producto['id']);
    }

    /** @test */
    public function solo_salen_los_productos_marcados_para_venta_online_con_precio_valido()
    {
        $codigos = collect($this->listado())->pluck('codigo_barras')->sort()->values()->all();

        $this->assertSame(['13_ACER00076217185', '20_ACUE00224561269'], $codigos);
    }

    /** @test */
    public function el_recargo_sale_de_la_configuracion()
    {
        config(['ecommerce.recargo_online_pct' => 0]);
        $producto = collect($this->listado())->firstWhere('codigo_barras', '20_ACUE00224561269');

        $this->assertSame('60000.00', $producto['precio_unidad']);
    }

    /** @test */
    public function productos_por_sucursal_tambien_filtra_y_aplica_el_recargo()
    {
        $token = $this->postJson('/api/auth/login', [
            'email' => 'ecom@example.com',
            'password' => 'clave-segura-123',
        ])->assertOk()->json('access_token');

        $productos = collect($this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/productosPorSucursal', ['nombre_sucursal' => 'Ingeniero Jacobacci'])
            ->assertStatus(201)
            ->json())->keyBy('codigo_barras');

        // En Jacobacci hay stock de 1 (online), 3 (offline) y 4 (online sin precio).
        $this->assertSame(['20_ACUE00224561269'], $productos->keys()->all());
        $this->assertSame('66000.00', $productos['20_ACUE00224561269']['precio']);
    }
}
