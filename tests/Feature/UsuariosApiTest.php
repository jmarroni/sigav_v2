<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UsuariosApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Tablas legacy que no tienen migración (vienen del dump).
        Schema::create('usuarios', function (Blueprint $t) {
            $t->increments('id');
            $t->string('usuario');
            $t->string('clave')->nullable();
            $t->integer('rol_id');
            $t->string('nombre')->nullable();
            $t->string('apellido')->nullable();
            $t->string('telefono')->nullable();
            $t->integer('sucursal_id')->nullable();
        });
        Schema::create('sucursales', function (Blueprint $t) {
            $t->increments('id');
            $t->string('nombre');
        });
        Schema::create('relacion_users_sucursales', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('sucursal_id');
            $t->integer('user_id');
        });

        DB::table('sucursales')->insert([['id' => 1, 'nombre' => 'Centro'], ['id' => 2, 'nombre' => 'Feria']]);
    }

    protected function tearDown(): void
    {
        unset($_COOKIE['kiosco']);
        parent::tearDown();
    }

    /** Sesión Laravel + cookie legacy de un usuario con el rol dado. */
    private function logueadoConRol(int $rol): self
    {
        DB::table('usuarios')->insert(['usuario' => 'operador', 'rol_id' => $rol]);
        $_COOKIE['kiosco'] = 'operador';

        $sesion = User::create(['name' => 'op', 'email' => 'operador@legacy.local', 'password' => bcrypt('x')]);

        return $this->actingAs($sesion);
    }

    private function usuarioApi(array $attrs = []): User
    {
        return User::create(array_merge([
            'name' => 'Tienda',
            'email' => 'tienda@example.com',
            'password' => bcrypt('clave-original-123'),
        ], $attrs));
    }

    /** @test */
    public function sin_rol_admin_no_puede_ver_ni_crear_usuarios_api()
    {
        $this->logueadoConRol(2);

        $this->get('/usuarios-api')->assertStatus(403);
        $this->post('/usuarios-api', [
            'name' => 'X', 'email' => 'x@example.com', 'password' => 'una-clave-larga',
        ])->assertStatus(403);

        $this->assertDatabaseMissing('users', ['email' => 'x@example.com']);
    }

    /** @test */
    public function sin_sesion_redirige_al_login()
    {
        $this->post('/usuarios-api', [
            'name' => 'X', 'email' => 'x@example.com', 'password' => 'una-clave-larga',
        ])->assertRedirect('/login.php');
    }

    /** @test */
    public function el_viejo_signup_por_get_ya_no_existe()
    {
        $this->get('/signup?name=X&email=x@example.com&password=123')->assertStatus(404);
        $this->assertDatabaseMissing('users', ['email' => 'x@example.com']);
    }

    /** @test */
    public function admin_crea_usuario_con_clave_bcrypt()
    {
        $this->logueadoConRol(4);

        $this->post('/usuarios-api', [
            'name' => 'Tienda online', 'email' => 'tienda@example.com', 'password' => 'una-clave-larga',
        ])->assertRedirect('/usuarios-api');

        $user = User::where('email', 'tienda@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('una-clave-larga', $user->password));
    }

    /** @test */
    public function no_acepta_claves_cortas_ni_emails_repetidos()
    {
        $this->logueadoConRol(4);
        $this->usuarioApi();

        $this->post('/usuarios-api', ['name' => 'A', 'email' => 'nuevo@example.com', 'password' => 'corta'])
            ->assertSessionHasErrors('password');
        $this->post('/usuarios-api', ['name' => 'A', 'email' => 'tienda@example.com', 'password' => 'una-clave-larga'])
            ->assertSessionHasErrors('email');
    }

    /** @test */
    public function editar_sin_clave_conserva_la_anterior_y_con_clave_la_reemplaza()
    {
        $this->logueadoConRol(4);
        $user = $this->usuarioApi();

        $this->put("/usuarios-api/{$user->id}", ['name' => 'Renombrado', 'email' => 'tienda@example.com', 'password' => ''])
            ->assertRedirect('/usuarios-api');
        $this->assertSame('Renombrado', $user->fresh()->name);
        $this->assertTrue(Hash::check('clave-original-123', $user->fresh()->password));

        $this->put("/usuarios-api/{$user->id}", ['name' => 'Renombrado', 'email' => 'tienda@example.com', 'password' => 'otra-clave-larga']);
        $this->assertTrue(Hash::check('otra-clave-larga', $user->fresh()->password));
    }

    /** @test */
    public function eliminar_borra_usuario_sucursales_y_tokens()
    {
        $this->logueadoConRol(4);
        $user = $this->usuarioApi();
        DB::table('relacion_users_sucursales')->insert(['sucursal_id' => 1, 'user_id' => $user->id]);
        DB::table('oauth_access_tokens')->insert(['id' => 'tok1', 'user_id' => $user->id, 'client_id' => 1, 'revoked' => false]);

        $this->delete("/usuarios-api/{$user->id}")->assertRedirect('/usuarios-api');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('relacion_users_sucursales', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('oauth_access_tokens', ['user_id' => $user->id]);
    }

    /** @test */
    public function no_se_pueden_tocar_los_usuarios_puente_del_login_legacy()
    {
        $this->logueadoConRol(4);
        $puente = User::where('email', 'operador@legacy.local')->firstOrFail();

        $this->delete("/usuarios-api/{$puente->id}")->assertStatus(404);
        $this->put("/usuarios-api/{$puente->id}", ['name' => 'X', 'email' => 'x@example.com', 'password' => 'una-clave-larga'])
            ->assertStatus(404);
        $this->assertDatabaseHas('users', ['id' => $puente->id, 'email' => 'operador@legacy.local']);
    }

    /** @test */
    public function asigna_y_quita_sucursales_sin_duplicar()
    {
        $this->logueadoConRol(4);
        $user = $this->usuarioApi();

        $this->post("/usuarios-api/{$user->id}/sucursales", ['sucursal_id' => 1])->assertRedirect('/usuarios-api');
        $this->post("/usuarios-api/{$user->id}/sucursales", ['sucursal_id' => 1]);
        $this->assertSame(1, DB::table('relacion_users_sucursales')->where('user_id', $user->id)->count());

        $this->post("/usuarios-api/{$user->id}/sucursales", ['sucursal_id' => 99])->assertSessionHasErrors('sucursal_id');

        $this->delete("/usuarios-api/{$user->id}/sucursales/1")->assertRedirect('/usuarios-api');
        $this->assertSame(0, DB::table('relacion_users_sucursales')->where('user_id', $user->id)->count());
    }
}
