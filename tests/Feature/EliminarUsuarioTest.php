<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * El botón Eliminar de /usuario llama DELETE usuario/{id} (no GET usuario.delete/{id}).
 */
class EliminarUsuarioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('usuarios', function (Blueprint $t) {
            $t->increments('id');
            $t->string('usuario');
            $t->string('clave')->nullable();
            $t->string('nombre')->nullable();
            $t->string('apellido')->nullable();
            $t->string('telefono')->nullable();
            $t->integer('rol_id')->default(1);
            $t->integer('sucursal_id')->default(1);
        });
        DB::table('usuarios')->insert(['id' => 7, 'usuario' => 'viejo', 'rol_id' => 1, 'sucursal_id' => 1]);
    }

    /** @test */
    public function delete_de_usuario_borra_la_fila_y_responde_ok()
    {
        $sesion = User::create(['name' => 'op', 'email' => 'op@example.com', 'password' => bcrypt('x')]);

        $this->actingAs($sesion)->deleteJson('/usuario/7')
            ->assertOk()
            ->assertJsonPath('proceso', 'OK');

        $this->assertSame(0, DB::table('usuarios')->where('id', 7)->count());
    }

    /** @test */
    public function el_camino_viejo_get_usuario_delete_ya_no_existe()
    {
        $sesion = User::create(['name' => 'op', 'email' => 'op@example.com', 'password' => bcrypt('x')]);

        $this->actingAs($sesion)->get('/usuario.delete/7')->assertStatus(404);
        $this->assertSame(1, DB::table('usuarios')->where('id', 7)->count());
    }
}
