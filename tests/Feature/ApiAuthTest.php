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
}
