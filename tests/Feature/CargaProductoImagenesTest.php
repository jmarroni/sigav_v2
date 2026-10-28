<?php
// tests/Feature/CargaProductoImagenesTest.php

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
 * Alta de producto con imágenes (/carga):
 *  - la imagen se guarda con su miniatura y la URL persistida es relativa al host
 *  - una imagen demasiado grande se rechaza con un mensaje claro (no en silencio)
 *  - un POST que supera post_max_size no muestra la página de error genérica
 *  - la grilla y el JSON de edición exponen las imágenes del producto
 */
class CargaProductoImagenesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        foreach (['productos', 'imagen_producto', 'stock', 'stock_logs', 'usuarios', 'sucursales', 'categorias', 'proveedor'] as $tabla) {
            $this->crearTabla($tabla);
        }
        DB::table('sucursales')->insert(['id' => 1, 'nombre' => 'Central']);
        DB::table('proveedor')->insert(['id' => 10, 'nombre' => 'Prov', 'apellido' => 'Uno']);
        DB::table('categorias')->insert(['id' => 10, 'nombre' => 'Cat']);
    }

    protected function tearDown(): void
    {
        unset($_COOKIE['kiosco'], $_COOKIE['rol'], $_COOKIE['sucursal']);
        parent::tearDown();
    }

    /** @test */
    public function alta_guarda_el_tamano_de_envio_elegido()
    {
        $this->logueado()->post('/carga', $this->campos() + ['envio_tamano' => 2])->assertRedirect();

        $this->assertSame(2, (int) DB::table('productos')->where('nombre', 'Gorro')->value('envio_tamano'));
    }

    /** @test */
    public function tamano_de_envio_fuera_de_rango_no_guarda_el_producto()
    {
        $this->logueado()->post('/carga', $this->campos() + ['envio_tamano' => 9])->assertRedirect();

        $this->assertSame(0, DB::table('productos')->where('nombre', 'Gorro')->count());
    }

    /** @test */
    public function alta_con_imagen_guarda_archivo_miniatura_y_url_relativa()
    {
        $resp = $this->logueado()->post('/carga', $this->campos() + [
            'imagen1' => UploadedFile::fake()->image('foto.jpg', 800, 600),
        ]);

        $resp->assertRedirect();
        $this->assertStringContainsString('carga/mensaje/', $resp->headers->get('Location'));

        $producto = DB::table('productos')->where('nombre', 'Gorro')->first();
        $this->assertNotNull($producto);

        $fila = DB::table('imagen_producto')->where('productos_id', $producto->id)->first();
        $this->assertNotNull($fila, 'Debe quedar la fila en imagen_producto');
        $this->assertRegExp('#^/storage/productos/images/'.$producto->id.'/[A-Za-z0-9]+\.jpg$#', $fila->imagen_url,
            'La URL se guarda relativa al host, sin APP_URL');

        $relativo = substr($fila->imagen_url, strlen('/storage/'));
        Storage::disk('local')->assertExists('public/'.$relativo);
        Storage::disk('local')->assertExists('public/productos/images/'.$producto->id.'/thumb_300x300_'.basename($relativo));
    }

    /** @test */
    public function imagen_demasiado_grande_se_rechaza_con_mensaje_y_no_crea_el_producto()
    {
        $resp = $this->logueado()->from('/carga')->post('/carga', $this->campos() + [
            'imagen1' => UploadedFile::fake()->image('foto.jpg')->size(12 * 1024),
        ]);

        $resp->assertRedirect('/carga');
        $resp->assertSessionHas('error_carga');
        $this->assertStringContainsString('Imagen 1', session('error_carga'));
        $this->assertSame(0, DB::table('productos')->count());
    }

    /** @test */
    public function archivo_que_no_es_imagen_se_rechaza()
    {
        $resp = $this->logueado()->from('/carga')->post('/carga', $this->campos() + [
            'imagen2' => UploadedFile::fake()->create('listado.pdf', 100, 'application/pdf'),
        ]);

        $resp->assertRedirect('/carga');
        $resp->assertSessionHas('error_carga');
        $this->assertSame(0, DB::table('productos')->count());
    }

    /** @test */
    public function svg_se_rechaza_porque_gd_no_lo_procesa_y_puede_llevar_script()
    {
        $resp = $this->logueado()->from('/carga')->post('/carga', $this->campos() + [
            'imagen1' => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
        ]);

        $resp->assertRedirect('/carga');
        $resp->assertSessionHas('error_carga');
        $this->assertSame(0, DB::table('productos')->count());
    }

    /** @test */
    public function al_rechazar_una_imagen_se_conserva_lo_tipeado()
    {
        $resp = $this->logueado()->from('/carga')->post('/carga', $this->campos() + [
            'imagen1' => UploadedFile::fake()->image('foto.jpg')->size(12 * 1024),
        ]);

        // La vista no se puede renderizar en PHPUnit (el layout incluye public/header.php,
        // que llama a header()); el repoblado con old() se verificó a mano contra el contenedor.
        $resp->assertSessionHasInput('producto', 'Gorro');
        $resp->assertSessionHasInput('proveedor', '10');
        $resp->assertSessionMissing('_old_input.imagen1');
    }

    /** @test */
    public function post_demasiado_grande_en_la_api_sigue_devolviendo_413()
    {
        $this->call('POST', '/api/auth/login', [], [], [], ['CONTENT_LENGTH' => 999999999])
            ->assertStatus(413);
    }

    /** @test */
    public function post_que_supera_post_max_size_vuelve_al_formulario_con_mensaje()
    {
        $resp = $this->logueado()->from('/carga')->call('POST', '/carga', [], [], [], [
            'CONTENT_LENGTH' => 999999999,
            'HTTP_REFERER'   => '/carga',
        ]);

        $resp->assertRedirect('/carga');
        $resp->assertSessionHas('error_carga');
        $this->assertStringContainsString('tamaño', session('error_carga'));
    }

    /** @test */
    public function la_grilla_muestra_la_miniatura_de_la_primera_imagen()
    {
        DB::table('productos')->insert(['id' => 7, 'nombre' => 'Con foto', 'codigo_barras' => 'A1', 'proveedores_id' => 10, 'categorias_id' => 10]);
        DB::table('productos')->insert(['id' => 8, 'nombre' => 'Sin foto', 'codigo_barras' => 'A2', 'proveedores_id' => 10, 'categorias_id' => 10]);
        DB::table('imagen_producto')->insert(['productos_id' => 7, 'imagen_url' => '/storage/productos/images/7/abc.jpg']);
        DB::table('imagen_producto')->insert(['productos_id' => 7, 'imagen_url' => '/storage/productos/images/7/def.jpg']);

        $json = $this->logueado()->get('/productos/datatable?sucursal=1&length=25')->assertOk()->json();

        $conFoto = collect($json['data'])->firstWhere('DT_RowId', '7');
        $sinFoto = collect($json['data'])->firstWhere('DT_RowId', '8');
        $this->assertStringContainsString('/storage/productos/images/7/thumb_300x300_abc.jpg', $conFoto[0]);
        $this->assertStringContainsString('no-image-featured-image.png', $sinFoto[0]);
        $this->assertSame('A1', $conFoto[1], 'El código pasa a la columna 1');
    }

    /** @test */
    public function el_json_de_edicion_incluye_las_imagenes_del_producto()
    {
        DB::table('productos')->insert(['id' => 7, 'nombre' => 'Con foto']);
        DB::table('imagen_producto')->insert(['productos_id' => 7, 'imagen_url' => '/storage/productos/images/7/abc.jpg']);

        $json = $this->logueado()->get('/carga/7')->assertOk()->json();

        $this->assertSame('Con foto', $json['nombre']);
        $this->assertCount(1, $json['imagenes']);
        $this->assertSame('/storage/productos/images/7/abc.jpg', $json['imagenes'][0]['imagen_url']);
        $this->assertSame('/storage/productos/images/7/thumb_300x300_abc.jpg', $json['imagenes'][0]['miniatura']);
    }

    private function campos(): array
    {
        return [
            'id' => '', 'codigo_de_barras' => 'GOR-1', 'producto' => 'Gorro', 'precio_unidad' => '100', 'costo' => '50',
            'stock' => '1', 'stock_minimo' => '0', 'proveedor' => '10', 'categoria' => '10', 'descripcion' => 'desc',
            'descripcion_pr' => '', 'descripcion_en' => '', 'material' => '', 'precio_mayorista' => '90',
            'precio_reposicion' => '0', 'descuento' => '0',
        ];
    }

    /** Sesión Laravel + cookies legacy (kiosco / rol / sucursal 1). */
    private function logueado(): self
    {
        config(['app.legacy_semilla' => 'semilla-de-test']);
        if (! DB::table('usuarios')->where('usuario', 'operador')->exists()) {
            DB::table('usuarios')->insert(['usuario' => 'operador', 'rol_id' => 5, 'sucursal_id' => 1]);
        }
        $_COOKIE['kiosco'] = 'operador';
        $_COOKIE['rol'] = sha1('semilla-de-test5semilla-de-test');
        $_COOKIE['sucursal'] = sha1('semilla-de-test1semilla-de-test');
        $sesion = User::firstOrCreate(['email' => 'operador@legacy.local'], ['name' => 'op', 'password' => bcrypt('x')]);
        return $this->actingAs($sesion);
    }

    private function crearTabla(string $tabla): void
    {
        $defs = [
            'productos' => function (Blueprint $t) {
                $t->increments('id');
                $t->string('codigo_barras', 100)->nullable();
                $t->string('nombre', 200)->nullable();
                $t->string('precio_unidad', 50)->nullable();
                $t->string('costo', 50)->nullable();
                $t->integer('stock')->nullable();
                $t->integer('stock_minimo')->nullable();
                $t->integer('proveedores_id')->nullable();
                $t->string('categorias_id', 50)->nullable();
                $t->string('usuario', 100)->nullable();
                $t->string('fecha', 20)->nullable();
                $t->string('precio_mayorista', 50)->nullable();
                $t->integer('es_comodato')->nullable();
                $t->text('descripcion')->nullable();
                $t->text('descripcion_pr')->nullable();
                $t->text('descripcion_en')->nullable();
                $t->string('material', 200)->nullable();
                $t->string('precio_reposicion', 50)->nullable();
                $t->decimal('descuento', 5, 2)->default(0);
                $t->unsignedTinyInteger('envio_tamano')->nullable();
                $t->timestamps();
            },
            'imagen_producto' => function (Blueprint $t) {
                $t->increments('id');
                $t->string('imagen_url', 300);
                $t->integer('productos_id');
                $t->timestamps();
            },
            'stock' => function (Blueprint $t) {
                $t->increments('id');
                $t->integer('stock')->nullable();
                $t->integer('stock_minimo')->nullable();
                $t->integer('sucursal_id')->nullable();
                $t->string('usuario', 100)->nullable();
                $t->integer('productos_id')->nullable();
                $t->timestamps();
            },
            'stock_logs' => function (Blueprint $t) {
                $t->increments('id');
                $t->integer('stock_anterior')->nullable();
                $t->integer('stock_minimo_anterior')->nullable();
                $t->integer('stock')->nullable();
                $t->integer('stock_minimo')->nullable();
                $t->integer('sucursal_id')->nullable();
                $t->string('usuario', 100)->nullable();
                $t->integer('productos_id')->nullable();
                $t->string('tipo_operacion', 50)->nullable();
                $t->string('barra', 100)->nullable();
                $t->timestamps();
            },
            'usuarios' => function (Blueprint $t) {
                $t->increments('id');
                $t->string('usuario', 100);
                $t->string('nombre', 100)->nullable();
                $t->string('apellido', 100)->nullable();
                $t->integer('rol_id')->nullable();
                $t->integer('sucursal_id')->nullable();
            },
            'sucursales' => function (Blueprint $t) {
                $t->increments('id');
                $t->string('nombre', 100);
            },
            'categorias' => function (Blueprint $t) {
                $t->increments('id');
                $t->string('nombre', 100);
            },
            'proveedor' => function (Blueprint $t) {
                $t->increments('id');
                $t->string('nombre', 100)->nullable();
                $t->string('apellido', 100)->nullable();
            },
        ];
        if (! Schema::hasTable($tabla)) {
            Schema::create($tabla, $defs[$tabla]);
        }
    }
}
