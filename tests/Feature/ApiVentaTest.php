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

        DB::table('sucursales')->insert(['id' => 1, 'nombre' => 'Central']);
        DB::table('productos')->insert([
            ['id' => 1, 'codigo_barras' => '13_ACER00076217185', 'nombre' => 'Mate', 'stock' => 5],
            ['id' => 2, 'codigo_barras' => '18_ALAN00084500323', 'nombre' => 'Cuchillo', 'stock' => 1],
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

    private function stock(int $id): int
    {
        return (int) DB::table('productos')->where('id', $id)->value('stock');
    }

    /** @test */
    public function sin_token_responde_401()
    {
        $this->postJson('/api/auth/productos/venta', [])->assertStatus(401);
    }

    /** @test */
    public function una_venta_descuenta_el_stock_y_registra_el_log()
    {
        $this->usuarioApi();

        $this->venta('PED-1', [
            ['codigo_barras' => '13_ACER00076217185', 'cantidad' => 2],
        ])->assertOk()->assertExactJson(['ok' => true]);

        $this->assertSame(3, $this->stock(1));
        $this->assertDatabaseHas('stock_logs', [
            'productos_id' => 1,
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
    public function tras_un_rechazo_la_misma_referencia_se_procesa_al_reponer_stock()
    {
        $this->usuarioApi();
        $items = [['codigo_barras' => '18_ALAN00084500323', 'cantidad' => 2]];

        $this->venta('PED-4', $items)->assertStatus(409);
        DB::table('productos')->where('id', 2)->update(['stock' => 4]);

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
