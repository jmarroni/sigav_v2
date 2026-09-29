<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Tablas legacy que no tienen migración (vienen del dump).
        Schema::create('sucursales', function (Blueprint $t) {
            $t->increments('id');
            $t->string('nombre');
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
            foreach (['codigo_barras', 'nombre', 'usuario', 'fecha', 'descripcion', 'descripcion_en', 'descripcion_pr', 'material'] as $c) {
                $t->string($c)->nullable();
            }
            foreach (['precio_unidad', 'costo', 'precio_mayorista', 'stock', 'stock_minimo', 'es_comodato'] as $c) {
                $t->decimal($c)->nullable();
            }
            $t->integer('proveedores_id');
            $t->integer('categorias_id');
        });
        Schema::create('stock', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('productos_id');
            $t->integer('sucursal_id');
        });
        Schema::create('imagen_producto', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('productos_id');
            $t->string('imagen_url');
        });

        $this->artisan('passport:client', ['--personal' => true, '--name' => 'test']);
    }

    private function usuarioApi(string $email = 'api@example.com'): User
    {
        return User::create([
            'name' => 'Integración',
            'email' => $email,
            'password' => bcrypt('clave-segura-123'),
        ]);
    }

    private function token(string $email = 'api@example.com'): string
    {
        return $this->postJson('/api/auth/login', [
            'email' => $email,
            'password' => 'clave-segura-123',
        ])->assertOk()->json('access_token');
    }

    /** @test */
    public function el_token_del_login_autentica_contra_los_endpoints_protegidos()
    {
        $this->usuarioApi();

        $this->withHeader('Authorization', 'Bearer ' . $this->token())
            ->postJson('/api/auth/user')
            ->assertOk()
            ->assertJsonPath('user.email', 'api@example.com');
    }

    /** @test */
    public function el_login_con_clave_incorrecta_devuelve_401()
    {
        $this->usuarioApi();

        $this->postJson('/api/auth/login', ['email' => 'api@example.com', 'password' => 'otra'])
            ->assertStatus(401);
    }

    /** @test */
    public function sin_token_los_endpoints_protegidos_devuelven_401()
    {
        $this->postJson('/api/auth/sucursales')->assertStatus(401);
    }

    /** @test */
    public function sucursales_devuelve_solo_las_del_usuario_autenticado_aunque_pida_otro_user_id()
    {
        $yo = $this->usuarioApi();
        $otro = $this->usuarioApi('otro@example.com');

        DB::table('sucursales')->insert([['id' => 1, 'nombre' => 'Mía'], ['id' => 2, 'nombre' => 'Ajena']]);
        DB::table('relacion_users_sucursales')->insert([
            ['sucursal_id' => 1, 'user_id' => $yo->id],
            ['sucursal_id' => 2, 'user_id' => $otro->id],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->token())
            ->postJson('/api/auth/sucursales', ['user_id' => $otro->id])
            ->assertJson([['nombre' => 'Mía']])
            ->assertJsonCount(1)
            ->assertJsonMissing(['nombre' => 'Ajena']);
    }

    private function productoEnSucursal(User $user): void
    {
        DB::table('sucursales')->insert(['id' => 1, 'nombre' => 'Centro']);
        DB::table('relacion_users_sucursales')->insert(['sucursal_id' => 1, 'user_id' => $user->id]);
        DB::table('categorias')->insert(['id' => 1, 'nombre' => 'Textil']);
        DB::table('proveedor')->insert(['id' => 1, 'nombre' => 'Artesana']);
        DB::table('productos')->insert([
            'id' => 1, 'nombre' => 'Poncho', 'precio_unidad' => 1000, 'costo' => 400,
            'proveedores_id' => 1, 'categorias_id' => 1,
        ]);
        DB::table('stock')->insert(['productos_id' => 1, 'sucursal_id' => 1]);
    }

    /** @test */
    public function los_endpoints_de_productos_no_exponen_el_costo()
    {
        $this->productoEnSucursal($this->usuarioApi());
        $bearer = ['Authorization' => 'Bearer ' . $this->token()];

        $porSucursal = $this->withHeaders($bearer)
            ->postJson('/api/auth/productosPorSucursal', ['nombre_sucursal' => 'Centro'])
            ->assertJsonPath('0.nombre', 'Poncho')
            ->json();
        $this->assertArrayNotHasKey('costo', $porSucursal[0]);

        $todos = $this->withHeaders($bearer)
            ->postJson('/api/auth/productos')
            ->assertJsonPath('0.nombre', 'Poncho')
            ->json();
        $this->assertArrayNotHasKey('costo', $todos[0]);
    }

    /** @test */
    public function el_token_vence_en_un_dia_como_informa_el_login()
    {
        $this->usuarioApi();
        $jwt = $this->token();

        $payload = json_decode(base64_decode(strtr(explode('.', $jwt)[1], '-_', '+/')), true);

        $this->assertLessThanOrEqual(now()->addDay()->addMinute()->timestamp, (int) $payload['exp']);
        $this->assertGreaterThan(now()->addHours(23)->timestamp, (int) $payload['exp']);
    }
}
