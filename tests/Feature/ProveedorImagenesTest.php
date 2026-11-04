<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Alta y edición de artesanos (/proveedor.save) con ficha pública:
 *  - título y descripción son obligatorios
 *  - hasta 3 imágenes, cada una en su slot (orden 1..3); solo se reemplaza el slot subido
 *  - un archivo que no es imagen se rechaza con mensaje, sin guardar nada
 */
class ProveedorImagenesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        Schema::create('usuarios', function (Blueprint $t) {
            $t->increments('id');
            $t->string('usuario');
            $t->integer('rol_id')->default(5);
            $t->integer('sucursal_id')->default(1);
        });
        Schema::create('categorias', function (Blueprint $t) {
            $t->increments('id');
            $t->string('nombre');
        });
        Schema::create('proveedor', function (Blueprint $t) {
            $t->increments('id');
            $t->string('nombre');
            $t->string('apellido')->nullable();
            $t->string('direccion')->nullable();
            $t->string('ciudad')->nullable();
            $t->string('provincia')->nullable();
            $t->string('telefono')->nullable();
            $t->string('mail')->nullable();
            $t->string('sitio_web')->nullable();
            $t->string('usuario')->nullable();
            $t->string('titulo', 150)->nullable();
            $t->text('descripcion')->nullable();
        });
        Schema::create('relacion_categoria_proveedor', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('proveedor_id');
            $t->integer('categoria_id');
        });

        DB::table('categorias')->insert(['id' => 1, 'nombre' => 'Textil']);
    }

    protected function tearDown(): void
    {
        unset($_COOKIE['kiosco'], $_COOKIE['rol'], $_COOKIE['sucursal']);
        parent::tearDown();
    }

    private function logueado(): self
    {
        config(['app.legacy_semilla' => 'semilla-de-test']);
        DB::table('usuarios')->insert(['usuario' => 'operador', 'rol_id' => 5, 'sucursal_id' => 1]);
        $_COOKIE['kiosco'] = 'operador';
        $_COOKIE['rol'] = sha1('semilla-de-test5semilla-de-test');
        $_COOKIE['sucursal'] = sha1('semilla-de-test1semilla-de-test');
        $sesion = User::firstOrCreate(['email' => 'operador@legacy.local'], ['name' => 'op', 'password' => bcrypt('x')]);
        return $this->actingAs($sesion);
    }

    private function campos(array $extra = []): array
    {
        return $extra + [
            'nombre' => 'Zoe',
            'apellido' => 'Pérez',
            'direccion' => 'Alvear 453',
            'ciudad' => 'Viedma',
            'provincia' => 'Río Negro',
            'telefono' => '2920000000',
            'categoria' => [1],
            'titulo' => 'Tejidos en telar',
            'descripcion' => 'Trabaja con lana de oveja patagónica.',
        ];
    }

    /** @test */
    public function alta_guarda_titulo_descripcion_y_cada_imagen_en_su_slot()
    {
        $this->logueado()->post('/proveedor.save', $this->campos([
            'imagen1' => UploadedFile::fake()->image('a.jpg', 800, 600),
            'imagen3' => UploadedFile::fake()->image('c.png', 800, 600),
        ]))->assertRedirect();

        $proveedor = DB::table('proveedor')->where('nombre', 'Zoe')->first();
        $this->assertSame('Tejidos en telar', $proveedor->titulo);
        $this->assertSame('Trabaja con lana de oveja patagónica.', $proveedor->descripcion);

        $imagenes = DB::table('proveedor_imagen')->where('proveedor_id', $proveedor->id)->orderBy('orden')->get();
        $this->assertSame([1, 3], $imagenes->pluck('orden')->map(fn ($o) => (int) $o)->all());
        foreach ($imagenes as $imagen) {
            $this->assertRegExp('#^/storage/proveedores/'.$proveedor->id.'/[A-Za-z0-9]+\.(jpg|png)$#', $imagen->imagen_url);
            Storage::disk('local')->assertExists('public/'.substr($imagen->imagen_url, strlen('/storage/')));
        }
    }

    /** @test */
    public function sin_titulo_no_guarda_el_artesano()
    {
        $this->logueado()->post('/proveedor.save', $this->campos(['titulo' => '']))
            ->assertSessionHasErrors('titulo');

        $this->assertSame(0, DB::table('proveedor')->count());
    }

    /** @test */
    public function sin_descripcion_no_guarda_el_artesano()
    {
        $this->logueado()->post('/proveedor.save', $this->campos(['descripcion' => '']))
            ->assertSessionHasErrors('descripcion');

        $this->assertSame(0, DB::table('proveedor')->count());
    }

    /** @test */
    public function un_archivo_que_no_es_imagen_se_rechaza_sin_guardar_nada()
    {
        $this->logueado()->post('/proveedor.save', $this->campos([
            'imagen2' => UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf'),
        ]))->assertSessionHasErrors('imagen2');

        $this->assertSame(0, DB::table('proveedor')->count());
        $this->assertSame(0, DB::table('proveedor_imagen')->count());
    }

    /** @test */
    public function editar_reemplaza_solo_el_slot_subido_y_borra_su_archivo_anterior()
    {
        $this->logueado()->post('/proveedor.save', $this->campos([
            'imagen1' => UploadedFile::fake()->image('uno.jpg', 800, 600),
            'imagen2' => UploadedFile::fake()->image('dos.jpg', 800, 600),
        ]))->assertRedirect();

        $proveedor = DB::table('proveedor')->where('nombre', 'Zoe')->first();
        $uno = DB::table('proveedor_imagen')->where('proveedor_id', $proveedor->id)->where('orden', 1)->value('imagen_url');
        $dosAnterior = DB::table('proveedor_imagen')->where('proveedor_id', $proveedor->id)->where('orden', 2)->value('imagen_url');

        $this->logueado()->post('/proveedor.save', $this->campos([
            'id_proveedor' => $proveedor->id,
            'imagen2' => UploadedFile::fake()->image('nueva.png', 800, 600),
        ]))->assertRedirect();

        $this->assertSame($uno, DB::table('proveedor_imagen')->where('proveedor_id', $proveedor->id)->where('orden', 1)->value('imagen_url'));

        $dosNueva = DB::table('proveedor_imagen')->where('proveedor_id', $proveedor->id)->where('orden', 2)->value('imagen_url');
        $this->assertNotSame($dosAnterior, $dosNueva);
        Storage::disk('local')->assertMissing('public/'.substr($dosAnterior, strlen('/storage/')));
        Storage::disk('local')->assertExists('public/'.substr($dosNueva, strlen('/storage/')));
        Storage::disk('local')->assertExists('public/'.substr($uno, strlen('/storage/')));
    }

    /** @test */
    public function eliminar_artesano_borra_sus_imagenes_y_sus_filas()
    {
        $this->logueado()->post('/proveedor.save', $this->campos([
            'imagen1' => UploadedFile::fake()->image('uno.jpg', 800, 600),
        ]))->assertRedirect();
        $proveedor = DB::table('proveedor')->where('nombre', 'Zoe')->first();
        $url = DB::table('proveedor_imagen')->where('proveedor_id', $proveedor->id)->value('imagen_url');

        $this->logueado()->delete('/proveedor/'.$proveedor->id)->assertOk();

        $this->assertSame(0, DB::table('proveedor_imagen')->where('proveedor_id', $proveedor->id)->count());
        Storage::disk('local')->assertMissing('public/'.substr($url, strlen('/storage/')));
    }

    /** @test */
    public function una_edicion_con_error_de_validacion_conserva_el_id_y_muestra_el_mensaje_del_campo()
    {
        $this->logueado()->post('/proveedor.save', $this->campos())->assertRedirect();
        $proveedor = DB::table('proveedor')->where('nombre', 'Zoe')->first();

        $this->logueado()->post('/proveedor.save', $this->campos([
            'id_proveedor' => $proveedor->id,
            'titulo' => str_repeat('a', 151),
        ]))->assertSessionHasErrors(['titulo' => 'El título no puede superar los 150 caracteres.']);

        $this->assertSame((string) $proveedor->id, (string) session()->getOldInput('id_proveedor'));
        $this->assertSame(1, DB::table('proveedor')->count(), 'No debe crear un duplicado');
    }
}
