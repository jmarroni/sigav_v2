<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ApiVentaTest extends TestCase
{
    use RefreshDatabase;

    private const SUCURSAL_ECOMMERCE = 1;
    private const SUCURSAL_OTRA = 2;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('sucursales', function (Blueprint $t) {
            $t->increments('id');
            $t->string('nombre');
        });
        Schema::create('relacion_users_sucursales', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('sucursal_id');
            $t->integer('user_id');
        });
        Schema::create('productos', function (Blueprint $t) {
            $t->increments('id');
            $t->string('codigo_barras')->unique();
            $t->string('nombre')->nullable();
            $t->integer('stock')->default(0);
            $t->integer('stock_minimo')->default(0);
            $t->string('precio_unidad')->nullable();
            $t->string('costo')->nullable();
            $t->timestamp('created_at')->nullable();
            $t->timestamp('updated_at')->nullable();
        });
        Schema::create('ventas', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('productos_id');
            $t->integer('cantidad');
            $t->string('precio');
            $t->string('costo');
            $t->string('fecha');
            $t->string('usuario');
            $t->integer('sucursal_id');
            $t->integer('estado')->nullable();
            $t->integer('factura_id')->nullable();
            $t->integer('tipo_pago')->nullable();
            $t->integer('lista_precio')->nullable();
            $t->decimal('descuento', 5, 2)->default(0);
            $t->string('referencia_api', 100)->nullable();
        });
        Schema::create('stock', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('productos_id');
            $t->integer('sucursal_id');
            $t->integer('stock')->default(0);
            $t->integer('stock_minimo')->default(0);
            $t->timestamp('created_at')->nullable();
            $t->timestamp('updated_at')->nullable();
        });
        Schema::create('stock_logs', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('productos_id')->nullable();
            $t->integer('sucursal_id')->nullable();
            $t->integer('stock_anterior')->nullable();
            $t->integer('stock_minimo_anterior')->nullable();
            $t->integer('stock')->nullable();
            $t->integer('stock_minimo')->nullable();
            $t->string('usuario')->nullable();
            $t->string('tipo_operacion')->nullable();
            $t->string('barra')->nullable();
            $t->timestamp('created_at')->nullable();
            $t->timestamp('updated_at')->nullable();
        });

        $this->artisan('passport:client', ['--personal' => true, '--name' => 'test']);

        config(['ecommerce.sucursal_id' => self::SUCURSAL_ECOMMERCE]);

        DB::table('sucursales')->insert([
            ['id' => self::SUCURSAL_ECOMMERCE, 'nombre' => 'e-commerce'],
            ['id' => self::SUCURSAL_OTRA, 'nombre' => 'Otra'],
        ]);
        // productos.stock global en 0 a propósito: la venta NO debe usarlo.
        DB::table('productos')->insert([
            ['id' => 1, 'codigo_barras' => '13_ACER00076217185', 'nombre' => 'Mate', 'stock' => 0, 'precio_unidad' => '60000.00', 'costo' => '400'],
            ['id' => 2, 'codigo_barras' => '18_ALAN00084500323', 'nombre' => 'Cuchillo', 'stock' => 0, 'precio_unidad' => '15.000', 'costo' => '90'],
        ]);
        DB::table('stock')->insert([
            ['productos_id' => 1, 'sucursal_id' => self::SUCURSAL_ECOMMERCE, 'stock' => 5],
            ['productos_id' => 2, 'sucursal_id' => self::SUCURSAL_ECOMMERCE, 'stock' => 1],
            ['productos_id' => 1, 'sucursal_id' => self::SUCURSAL_OTRA, 'stock' => 100],
        ]);
    }

    private function usuarioApi(): User
    {
        return User::create([
            'name' => 'E-commerce',
            'email' => 'ecom@example.com',
            'password' => bcrypt('clave-segura-123'),
        ]);
    }

    /** Un solo login por test: el segundo login en la misma app de test falla (RequestGuard::attempt). */
    private ?string $token = null;

    private function headers(): array
    {
        if ($this->token === null) {
            $this->token = $this->postJson('/api/auth/login', [
                'email' => 'ecom@example.com',
                'password' => 'clave-segura-123',
            ])->assertOk()->json('access_token');
        }

        return ['Authorization' => 'Bearer ' . $this->token];
    }

    private function venta(string $referencia, array $items)
    {
        return $this->withHeaders($this->headers())
            ->postJson('/api/auth/productos/venta', ['referencia' => $referencia, 'items' => $items]);
    }

    private function stock(int $productoId, int $sucursalId = self::SUCURSAL_ECOMMERCE): int
    {
        return (int) DB::table('stock')
            ->where('productos_id', $productoId)
            ->where('sucursal_id', $sucursalId)
            ->value('stock');
    }

    /** @test */
    public function sin_token_responde_401()
    {
        $this->postJson('/api/auth/productos/venta', [])->assertStatus(401);
    }

    /** @test */
    public function una_venta_descuenta_el_stock_de_la_sucursal_ecommerce_y_registra_el_log()
    {
        $this->usuarioApi();

        $this->venta('PED-1', [
            ['codigo_barras' => '13_ACER00076217185', 'cantidad' => 2],
        ])->assertOk()->assertExactJson(['ok' => true]);

        $this->assertSame(3, $this->stock(1));
        $this->assertSame(100, $this->stock(1, self::SUCURSAL_OTRA), 'no toca otras sucursales');
        $this->assertDatabaseHas('stock_logs', [
            'productos_id' => 1,
            'sucursal_id' => self::SUCURSAL_ECOMMERCE,
            'stock_anterior' => 5,
            'stock' => 3,
            'tipo_operacion' => 'Venta e-commerce',
            'barra' => '13_ACER00076217185',
        ]);
    }

    /** @test */
    public function repetir_la_misma_referencia_no_descuenta_dos_veces()
    {
        $this->usuarioApi();
        $items = [['codigo_barras' => '13_ACER00076217185', 'cantidad' => 2]];

        $this->venta('PED-2', $items)->assertOk()->assertExactJson(['ok' => true]);
        $this->venta('PED-2', $items)->assertOk()->assertExactJson(['ok' => true]);

        $this->assertSame(3, $this->stock(1));
    }

    /** @test */
    public function si_un_item_no_alcanza_no_se_descuenta_ninguno_y_se_informa_disponible()
    {
        $this->usuarioApi();

        $this->venta('PED-3', [
            ['codigo_barras' => '13_ACER00076217185', 'cantidad' => 2],
            ['codigo_barras' => '18_ALAN00084500323', 'cantidad' => 4],
        ])->assertStatus(409)->assertExactJson([
            'ok' => false,
            'motivo' => 'sin_stock',
            'items' => [['codigo_barras' => '18_ALAN00084500323', 'disponible' => 1]],
        ]);

        $this->assertSame(5, $this->stock(1));
        $this->assertSame(1, $this->stock(2));
        $this->assertDatabaseMissing('stock_logs', ['tipo_operacion' => 'Venta e-commerce']);
    }

    /** @test */
    public function un_producto_sin_fila_de_stock_en_la_sucursal_figura_con_cero()
    {
        $this->usuarioApi();
        DB::table('productos')->insert(['id' => 3, 'codigo_barras' => 'SIN-FILA', 'nombre' => 'Otro', 'stock' => 50, 'precio_unidad' => '100']);

        $this->venta('PED-S', [['codigo_barras' => 'SIN-FILA', 'cantidad' => 1]])
            ->assertStatus(409)
            ->assertExactJson([
                'ok' => false,
                'motivo' => 'sin_stock',
                'items' => [['codigo_barras' => 'SIN-FILA', 'disponible' => 0]],
            ]);
    }

    /** @test */
    public function la_venta_crea_renglones_sin_facturar_con_la_referencia_y_el_precio()
    {
        $this->usuarioApi();

        $this->venta('PED-V', [
            ['codigo_barras' => '13_ACER00076217185', 'cantidad' => 2],
            ['codigo_barras' => '18_ALAN00084500323', 'cantidad' => 1],
        ])->assertOk();

        $this->assertDatabaseHas('ventas', [
            'productos_id' => 1, 'cantidad' => 2, 'precio' => '60000.00', 'costo' => '400',
            'sucursal_id' => self::SUCURSAL_ECOMMERCE, 'estado' => 1, 'factura_id' => null,
            'referencia_api' => 'PED-V', 'lista_precio' => 1,
        ]);
        $this->assertDatabaseHas('ventas', [
            'productos_id' => 2, 'cantidad' => 1, 'precio' => '15000.00', 'referencia_api' => 'PED-V',
        ]);
    }

    /** @test */
    public function el_comprador_opcional_queda_guardado_en_el_pedido()
    {
        $this->usuarioApi();

        $this->withHeaders($this->headers())->postJson('/api/auth/productos/venta', [
            'referencia' => 'PED-C',
            'items' => [['codigo_barras' => '13_ACER00076217185', 'cantidad' => 1]],
            'cliente' => ['tipo_documento' => 'CUIT', 'numero_documento' => '20123456786', 'nombre' => 'Juan Perez', 'condicion_iva' => 1],
        ])->assertOk();

        $this->assertDatabaseHas('api_ventas_referencias', [
            'referencia' => 'PED-C', 'cliente_tipo_documento' => '80', 'cliente_documento' => '20123456786',
            'cliente_nombre' => 'Juan Perez', 'cliente_condicion_iva' => 1,
        ]);
    }

    /** @test */
    public function un_cliente_incompleto_responde_422_y_no_descuenta()
    {
        $this->usuarioApi();

        $this->withHeaders($this->headers())->postJson('/api/auth/productos/venta', [
            'referencia' => 'PED-I',
            'items' => [['codigo_barras' => '13_ACER00076217185', 'cantidad' => 1]],
            'cliente' => ['tipo_documento' => 'PASAPORTE'],
        ])->assertStatus(422)->assertJsonPath('motivo', 'datos_invalidos');

        $this->assertSame(5, $this->stock(1));
    }

    /** @test */
    public function un_producto_sin_precio_valido_se_rechaza_sin_descontar()
    {
        $this->usuarioApi();
        DB::table('productos')->where('id', 1)->update(['precio_unidad' => 'consultar']);

        $this->venta('PED-P', [['codigo_barras' => '13_ACER00076217185', 'cantidad' => 1]])
            ->assertStatus(409)
            ->assertJsonPath('motivo', 'precio_invalido');

        $this->assertSame(5, $this->stock(1));
        $this->assertDatabaseMissing('ventas', ['referencia_api' => 'PED-P']);
    }

    /** @test */
    public function sin_sucursal_configurada_la_venta_se_rechaza_sin_descontar()
    {
        $this->usuarioApi();
        config(['ecommerce.sucursal_id' => null]);

        $this->venta('PED-N', [['codigo_barras' => '13_ACER00076217185', 'cantidad' => 1]])
            ->assertStatus(409)
            ->assertJsonPath('motivo', 'sin_stock');

        $this->assertSame(5, $this->stock(1));
        $this->assertDatabaseMissing('api_ventas_referencias', ['referencia' => 'PED-N']);
    }

    /** @test */
    public function tras_un_rechazo_la_misma_referencia_se_procesa_al_reponer_stock()
    {
        $this->usuarioApi();
        $items = [['codigo_barras' => '18_ALAN00084500323', 'cantidad' => 2]];

        $this->venta('PED-4', $items)->assertStatus(409);
        DB::table('stock')->where('productos_id', 2)->where('sucursal_id', self::SUCURSAL_ECOMMERCE)->update(['stock' => 4]);

        $this->venta('PED-4', $items)->assertOk()->assertExactJson(['ok' => true]);
        $this->assertSame(2, $this->stock(2));
    }

    /** @test */
    public function un_codigo_inexistente_responde_codigo_desconocido_sin_descontar()
    {
        $this->usuarioApi();

        $this->venta('PED-5', [
            ['codigo_barras' => '13_ACER00076217185', 'cantidad' => 1],
            ['codigo_barras' => 'NO-EXISTE', 'cantidad' => 1],
        ])->assertStatus(409)->assertExactJson([
            'ok' => false,
            'motivo' => 'codigo_desconocido',
            'items' => [['codigo_barras' => 'NO-EXISTE', 'disponible' => 0]],
        ]);

        $this->assertSame(5, $this->stock(1));
    }

    /** @test */
    public function el_mismo_codigo_en_dos_items_se_suma_antes_de_validar_stock()
    {
        $this->usuarioApi();

        $this->venta('PED-6', [
            ['codigo_barras' => '13_ACER00076217185', 'cantidad' => 3],
            ['codigo_barras' => '13_ACER00076217185', 'cantidad' => 3],
        ])->assertStatus(409)->assertJsonPath('items.0.disponible', 5);

        $this->assertSame(5, $this->stock(1));
    }

    /** @test */
    public function datos_invalidos_responde_422()
    {
        $this->usuarioApi();

        $this->venta('PED-7', [])->assertStatus(422)->assertJsonPath('motivo', 'datos_invalidos');
        $this->venta('', [['codigo_barras' => 'X', 'cantidad' => 0]])->assertStatus(422);
    }
}
