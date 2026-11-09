<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * ABM de categorías y proveedores (/categoria, /proveedor):
 *  - las acciones de la grilla usan los verbos y URLs reales de routes/web.php
 *  - sin sesión no se borra nada (ya no existe el bypass por apiKey)
 *  - el alta de categoría valida nombre y abreviatura en el servidor
 */
class CategoriaProveedorAbmTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearSiFalta('usuarios', function (Blueprint $t) {
            $t->increments('id');
            $t->string('usuario');
            $t->integer('rol_id')->default(1);
            $t->integer('sucursal_id')->default(1);
        });
        $this->crearSiFalta('categorias', function (Blueprint $t) {
            $t->increments('id');
            $t->string('nombre');
            $t->string('abreviatura')->nullable();
            $t->string('usuario')->nullable();
            $t->integer('habilitada')->default(1);
        });
        $this->crearSiFalta('categorias_logs', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('categoria_id');
            $t->string('usuario')->nullable();
            $t->string('tipo_operacion')->nullable();
            $t->timestamps();
        });
        $this->crearSiFalta('proveedor', function (Blueprint $t) {
            $t->increments('id');
            $t->string('nombre');
        });
        $this->crearSiFalta('relacion_categoria_proveedor', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('proveedor_id');
            $t->integer('categoria_id');
        });
        $this->crearSiFalta('proveedor_imagen', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('proveedor_id');
            $t->integer('orden');
            $t->string('imagen_url');
        });

        DB::table('categorias')->insert(['id' => 1, 'nombre' => 'Textil', 'abreviatura' => 'TX', 'habilitada' => 1]);
        DB::table('proveedor')->insert(['id' => 1, 'nombre' => 'Zoe']);
        DB::table('relacion_categoria_proveedor')->insert(['proveedor_id' => 1, 'categoria_id' => 1]);
    }

    private function crearSiFalta(string $tabla, \Closure $definicion): void
    {
        if (! Schema::hasTable($tabla)) {
            Schema::create($tabla, $definicion);
        }
    }

    protected function tearDown(): void
    {
        unset($_COOKIE['kiosco'], $_COOKIE['rol'], $_COOKIE['sucursal']);
        parent::tearDown();
    }

    private function logueado(): self
    {
        config(['app.legacy_semilla' => 'semilla-de-test']);
        DB::table('usuarios')->insert(['usuario' => 'admin', 'rol_id' => 1, 'sucursal_id' => 1]);
        $_COOKIE['kiosco'] = 'admin';
        $_COOKIE['rol'] = sha1('semilla-de-test1semilla-de-test');
        $_COOKIE['sucursal'] = sha1('semilla-de-test1semilla-de-test');
        $sesion = User::firstOrCreate(['email' => 'admin@legacy.local'], ['name' => 'admin', 'password' => bcrypt('x')]);

        return $this->actingAs($sesion);
    }

    /** @test */
    public function eliminar_categoria_por_delete_la_borra_y_deja_log()
    {
        DB::table('relacion_categoria_proveedor')->delete();

        $this->logueado()->deleteJson('/categoria/1')->assertOk()->assertJson(['proceso' => 'OK']);

        $this->assertSame(0, DB::table('categorias')->count());
        $this->assertSame('Baja', DB::table('categorias_logs')->value('tipo_operacion'));
    }

    /** @test */
    public function cambiar_estado_de_categoria_por_post_alterna_habilitada()
    {
        $this->logueado()->postJson('/categoria.changeStatus/1')->assertOk()->assertJson(['proceso' => 'OK', 'status' => 0]);
        $this->assertSame(0, (int) DB::table('categorias')->where('id', 1)->value('habilitada'));

        $this->postJson('/categoria.changeStatus/1')->assertOk()->assertJson(['status' => 1]);
        $this->assertSame(1, (int) DB::table('categorias')->where('id', 1)->value('habilitada'));
    }

    /** @test */
    public function alta_de_categoria_exige_nombre_y_abreviatura()
    {
        $this->logueado()->post('/categoria.save', ['nombre' => '', 'abreviatura' => ''])
            ->assertSessionHasErrors(['nombre', 'abreviatura']);

        $this->assertSame(1, DB::table('categorias')->count());
    }

    /** @test */
    public function alta_de_categoria_valida_guarda_y_redirige()
    {
        $this->logueado()->post('/categoria.save', ['id_categoria' => '', 'nombre' => 'Cerámica', 'abreviatura' => 'CER'])
            ->assertRedirect();

        $this->assertSame('CER', DB::table('categorias')->where('nombre', 'Cerámica')->value('abreviatura'));
    }

    /** @test */
    public function eliminar_proveedor_por_delete_borra_proveedor_y_relaciones()
    {
        $this->logueado()->deleteJson('/proveedor/1')->assertOk()->assertJson(['proceso' => 'OK']);

        $this->assertSame(0, DB::table('proveedor')->count());
        $this->assertSame(0, DB::table('relacion_categoria_proveedor')->count());
    }

    /** @test */
    public function modificar_proveedor_con_celda_latin1_no_da_500()
    {
        // Mercado tiene celdas en latin1 real ("PE\xD1A"): json_encode fallaba con Malformed UTF-8.
        DB::table('proveedor')->where('id', 1)->update(['nombre' => "PE\xD1A"]);

        $this->logueado()->getJson('/proveedor.getProveedor/1')
            ->assertOk()
            ->assertJson(['id' => 1, 'nombre' => 'PEÑA']);
    }

    /** @test */
    public function modificar_categoria_con_celda_latin1_no_da_500()
    {
        DB::table('categorias')->where('id', 1)->update(['nombre' => "CER\xC1MICA"]);

        $this->logueado()->getJson('/categoria.getCategoria/1')
            ->assertOk()
            ->assertJson(['id' => 1, 'nombre' => 'CERÁMICA']);
    }

    /** @test */
    public function sin_sesion_la_apikey_vieja_no_permite_borrar()
    {
        $this->delete('/categoria/1?apiKey=a0a035dc5213448bb1a130c27f2494c5');
        $this->delete('/proveedor/1?apiKey=a0a035dc5213448bb1a130c27f2494c5');

        $this->assertSame(1, DB::table('categorias')->count());
        $this->assertSame(1, DB::table('proveedor')->count());
    }

    /** @test */
    public function los_controllers_no_tienen_apikey_hardcodeada()
    {
        foreach (['CategoriaController', 'ProveedorController'] as $controller) {
            $codigo = file_get_contents(app_path("Http/Controllers/{$controller}.php"));
            $this->assertStringNotContainsString('a0a035dc5213448bb1a130c27f2494c5', $codigo, $controller);
        }
    }

    /** @test */
    public function el_js_de_la_grilla_usa_las_rutas_reales()
    {
        $categorias = file_get_contents(public_path('assets/js/categorias/categorias_accion.js'));
        $this->assertStringNotContainsString('/categoria.delete/', $categorias);
        $this->assertRegExp("#url:\s*'/categoria/'\s*\+\s*id,\s*type:\s*'DELETE'#", $categorias);
        $this->assertRegExp("#url:\s*'/categoria.changeStatus/'\s*\+\s*id,\s*type:\s*'POST'#", $categorias);

        $proveedores = file_get_contents(public_path('assets/js/proveedores/proveedores_accion.js'));
        $this->assertStringNotContainsString('/proveedor.delete/', $proveedores);
        $this->assertRegExp("#url:\s*'/proveedor/'\s*\+\s*id,\s*type:\s*'DELETE'#", $proveedores);
    }
}
