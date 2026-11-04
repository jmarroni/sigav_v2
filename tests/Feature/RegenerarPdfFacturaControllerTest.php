<?php
// tests/Feature/RegenerarPdfFacturaControllerTest.php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Botón "Regenerar PDF" del reporte de facturación: POST /facturas/{id}/regenerar-pdf.
 * Regenera el PDF desde la base (misma lógica que factura:regenerar-pdf) y
 * devuelve JSON para que la grilla actualice el link sin recargar.
 */
class RegenerarPdfFacturaControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var string */
    private $publico;

    protected function setUp(): void
    {
        parent::setUp();

        $this->publico = sys_get_temp_dir().'/regenerar_pdf_ctl_'.uniqid();
        mkdir($this->publico.'/facturas', 0777, true);
        mkdir($this->publico.'/assets/img/photos', 0777, true);
        copy(base_path('public/assets/img/photos/no-image-featured-image.png'), $this->publico.'/assets/img/photos/no-image-featured-image.png');
        $this->app->instance('path.public', $this->publico);

        foreach (['factura', 'ventas', 'productos', 'sucursales', 'perfil', 'usuarios'] as $tabla) {
            $this->crearTabla($tabla);
        }
        DB::table('afip_config')->insert([
            ['entorno' => 'prod', 'cuit' => '30715251988', 'ptovta' => '20', 'comprobante' => '11', 'condicion_iva' => 'IVA EXENTO', 'ingresos_brutos' => '46161295', 'emitir' => 1, 'solicitar_datos' => 0, 'activo' => 1],
        ]);
        DB::table('perfil')->insert(['id' => 1, 'nombre' => 'Mercado Artesanal', 'logo' => '']);
        DB::table('sucursales')->insert(['id' => 40, 'nombre' => 'Los menucos', 'pto_vta' => 20, 'direccion' => 'Ruta 23', 'codigo_postal' => '8424', 'provincia' => 'Río Negro']);
        DB::table('productos')->insert(['id' => 100854, 'nombre' => 'CHAL']);
    }

    protected function tearDown(): void
    {
        unset($_COOKIE['kiosco'], $_COOKIE['rol']);
        exec('rm -rf '.escapeshellarg($this->publico));
        parent::tearDown();
    }

    private function crearTabla(string $tabla): void
    {
        $defs = [
            'factura' => function (Blueprint $t) {
                $t->increments('id');
                $t->integer('sucursal_id')->nullable();
                $t->string('fecha', 20)->nullable();
                $t->string('usuario', 100)->nullable();
                $t->integer('numero')->nullable();
                $t->string('cae', 50)->nullable();
                $t->string('fechacae', 20)->default('');
                $t->string('total', 20)->nullable();
                $t->string('pdf', 200)->nullable();
                $t->integer('presupuesto')->default(0);
                $t->integer('nro_presupuesto')->nullable();
                $t->string('nombre', 200)->nullable();
                $t->string('direccion', 200)->nullable();
                $t->string('documento', 200)->nullable();
                $t->integer('tipo_documento')->nullable();
                $t->integer('iva')->nullable();
                $t->decimal('descuento_total', 5, 2)->default(0);
            },
            'ventas' => function (Blueprint $t) {
                $t->increments('id');
                $t->integer('productos_id');
                $t->integer('cantidad');
                $t->string('precio', 20);
                $t->integer('factura_id')->nullable();
                $t->integer('tipo_pago')->nullable();
                $t->decimal('descuento', 5, 2)->default(0);
            },
            'productos' => function (Blueprint $t) {
                $t->increments('id');
                $t->string('nombre', 200);
            },
            'sucursales' => function (Blueprint $t) {
                $t->increments('id');
                $t->string('nombre', 200);
                $t->integer('pto_vta')->nullable();
                $t->string('direccion', 200)->nullable();
                $t->string('codigo_postal', 20)->nullable();
                $t->string('provincia', 200)->nullable();
            },
            'perfil' => function (Blueprint $t) {
                $t->increments('id');
                $t->string('nombre', 200)->nullable();
                $t->string('logo', 200)->nullable();
            },
            'usuarios' => function (Blueprint $t) {
                $t->increments('id');
                $t->string('usuario');
                $t->string('clave')->nullable();
                $t->integer('rol_id');
                $t->string('nombre')->nullable();
                $t->string('apellido')->nullable();
            },
        ];
        Schema::create($tabla, $defs[$tabla]);
    }

    /** Sesión Laravel + cookie legacy de un usuario con el rol dado. */
    private function logueadoConRol(int $rol): self
    {
        config(['app.legacy_semilla' => 'semilla-de-test']);
        DB::table('usuarios')->insert(['usuario' => 'operador', 'rol_id' => $rol]);
        $_COOKIE['kiosco'] = 'operador';
        $_COOKIE['rol'] = sha1('semilla-de-test'.$rol.'semilla-de-test');
        $sesion = User::create(['name' => 'op', 'email' => 'operador@legacy.local', 'password' => bcrypt('x')]);
        return $this->actingAs($sesion);
    }

    private function factura(int $numero = 590, string $cae = '86395342432866'): int
    {
        $id = DB::table('factura')->insertGetId([
            'sucursal_id' => 40, 'fecha' => '2026-09-30 09:21:11', 'usuario' => 'C_FIGUEROA',
            'numero' => $numero, 'cae' => $cae, 'fechacae' => '10-10-2026', 'total' => '150000',
            'pdf' => sprintf('/facturas/20_%s_%06d.pdf', $cae, $numero), 'presupuesto' => 0,
            'nombre' => 'Consumidor Final', 'direccion' => '', 'documento' => '0', 'tipo_documento' => 99, 'iva' => 4,
        ]);
        DB::table('ventas')->insert(['productos_id' => 100854, 'cantidad' => 1, 'precio' => '150000', 'factura_id' => $id, 'tipo_pago' => 1612]);
        return $id;
    }

    /** @test */
    public function sin_sesion_redirige_al_login()
    {
        $id = $this->factura();

        $this->post("/facturas/$id/regenerar-pdf")->assertRedirect();
        $this->assertFileNotExists($this->publico.'/facturas/20_86395342432866_000590.pdf');
    }

    /** @test */
    public function un_vendedor_no_puede_regenerar()
    {
        $id = $this->factura();

        $this->logueadoConRol(1)->postJson("/facturas/$id/regenerar-pdf")->assertStatus(403);
        $this->assertFileNotExists($this->publico.'/facturas/20_86395342432866_000590.pdf');
    }

    /** @test */
    public function un_admin_regenera_y_recibe_el_link()
    {
        $id = $this->factura();

        $r = $this->logueadoConRol(4)->postJson("/facturas/$id/regenerar-pdf");

        $r->assertOk()->assertJson(['ok' => true, 'pdf' => '/facturas/20_86395342432866_000590.pdf']);
        $ruta = $this->publico.'/facturas/20_86395342432866_000590.pdf';
        $this->assertFileExists($ruta);
        $this->assertStringStartsWith('%PDF', (string) file_get_contents($ruta, false, null, 0, 4));
    }

    /** @test */
    public function sobreescribe_un_pdf_existente()
    {
        $id = $this->factura();
        $ruta = $this->publico.'/facturas/20_86395342432866_000590.pdf';
        file_put_contents($ruta, 'viejo');

        $this->logueadoConRol(5)->postJson("/facturas/$id/regenerar-pdf")->assertOk();

        $this->assertStringStartsWith('%PDF', (string) file_get_contents($ruta, false, null, 0, 4));
    }

    /** @test */
    public function con_sesion_abierta_no_alcanza_forjar_la_cookie_kiosco_de_un_admin()
    {
        $id = $this->factura();
        // Vendedor legítimo (rol 1) con sesión Laravel y cookie rol válida para rol 1...
        $this->logueadoConRol(1);
        // ...que cambia la cookie kiosco por el nombre de un admin.
        DB::table('usuarios')->insert(['usuario' => 'admin', 'rol_id' => 5]);
        $_COOKIE['kiosco'] = 'admin';

        $this->postJson("/facturas/$id/regenerar-pdf")->assertStatus(403);
        $this->assertFileNotExists($this->publico.'/facturas/20_86395342432866_000590.pdf');
    }

    /** @test */
    public function sin_semilla_configurada_no_autoriza_a_nadie()
    {
        $id = $this->factura();
        $this->logueadoConRol(5);
        config(['app.legacy_semilla' => '']);

        $this->postJson("/facturas/$id/regenerar-pdf")->assertStatus(403);
    }

    /** @test */
    public function una_factura_sin_cae_no_se_regenera()
    {
        $id = $this->factura();
        DB::table('factura')->where('id', $id)->update(['cae' => '']);

        $r = $this->logueadoConRol(4)->postJson("/facturas/$id/regenerar-pdf");

        $r->assertStatus(422)->assertJson(['ok' => false]);
        $this->assertStringContainsString('CAE', $r->json('mensaje'));
        $this->assertFileNotExists($this->publico.'/facturas/20_86395342432866_000590.pdf');
    }

    /** @test */
    public function una_factura_inexistente_da_404()
    {
        $this->logueadoConRol(4)->postJson('/facturas/999/regenerar-pdf')->assertStatus(404);
    }

    /** @test */
    public function sin_renglones_de_venta_responde_422_con_el_motivo()
    {
        $id = $this->factura();
        DB::table('ventas')->where('factura_id', $id)->delete();

        $r = $this->logueadoConRol(4)->postJson("/facturas/$id/regenerar-pdf");

        $r->assertStatus(422)->assertJson(['ok' => false]);
        $this->assertStringContainsString('renglones', $r->json('mensaje'));
        $this->assertFileNotExists($this->publico.'/facturas/20_86395342432866_000590.pdf');
    }
}
