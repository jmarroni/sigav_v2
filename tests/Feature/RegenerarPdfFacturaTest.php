<?php
// tests/Feature/RegenerarPdfFacturaTest.php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * `factura:regenerar-pdf` vuelve a generar el PDF de una factura ya emitida
 * (CAE en la base) cuyo archivo no está en public/facturas: PDFs perdidos
 * en una migración o que nunca se escribieron (incidente del logo 2026-09-23).
 */
class RegenerarPdfFacturaTest extends TestCase
{
    use RefreshDatabase;

    /** @var string */
    private $publico;

    protected function setUp(): void
    {
        parent::setUp();

        $this->publico = sys_get_temp_dir().'/regenerar_pdf_'.uniqid();
        mkdir($this->publico.'/facturas', 0777, true);
        mkdir($this->publico.'/assets/img/photos', 0777, true);
        copy(base_path('public/assets/img/photos/no-image-featured-image.png'), $this->publico.'/assets/img/photos/no-image-featured-image.png');
        $this->app->instance('path.public', $this->publico);

        $this->crearTablasLegacy();
        DB::table('afip_config')->insert([
            ['entorno' => 'prod', 'cuit' => '30715251988', 'ptovta' => '20', 'comprobante' => '11', 'condicion_iva' => 'IVA EXENTO', 'ingresos_brutos' => '46161295', 'emitir' => 1, 'solicitar_datos' => 0, 'activo' => 1],
        ]);
        DB::table('perfil')->insert(['id' => 1, 'nombre' => 'Mercado Artesanal', 'logo' => '/assets/perfil/no-existe.png']);
        DB::table('sucursales')->insert(['id' => 40, 'nombre' => 'Los menucos', 'pto_vta' => 20, 'direccion' => 'Ruta 23', 'codigo_postal' => '8424', 'provincia' => 'Río Negro']);
        DB::table('productos')->insert(['id' => 100854, 'nombre' => 'CHAL']);
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->publico));
        parent::tearDown();
    }

    private function crearTablasLegacy(): void
    {
        Schema::create('factura', function ($t) {
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
        });
        Schema::create('ventas', function ($t) {
            $t->increments('id');
            $t->integer('productos_id');
            $t->integer('cantidad');
            $t->string('precio', 20);
            $t->integer('factura_id')->nullable();
            $t->integer('tipo_pago')->nullable();
            $t->decimal('descuento', 5, 2)->default(0);
        });
        Schema::create('productos', function ($t) {
            $t->increments('id');
            $t->string('nombre', 200);
        });
        Schema::create('sucursales', function ($t) {
            $t->increments('id');
            $t->string('nombre', 200);
            $t->integer('pto_vta')->nullable();
            $t->string('direccion', 200)->nullable();
            $t->string('codigo_postal', 20)->nullable();
            $t->string('provincia', 200)->nullable();
        });
        Schema::create('perfil', function ($t) {
            $t->increments('id');
            $t->string('nombre', 200)->nullable();
            $t->string('logo', 200)->nullable();
        });
    }

    private function factura(int $numero, string $cae, array $extra = []): int
    {
        $id = DB::table('factura')->insertGetId(array_merge([
            'sucursal_id' => 40, 'fecha' => '2026-09-30 09:21:11', 'usuario' => 'C_FIGUEROA',
            'numero' => $numero, 'cae' => $cae, 'fechacae' => '10-10-2026', 'total' => '150000',
            'pdf' => sprintf('/facturas/20_%s_%06d.pdf', $cae, $numero), 'presupuesto' => 0,
            'nombre' => 'Consumidor Final', 'direccion' => '', 'documento' => '0', 'tipo_documento' => 99, 'iva' => 4,
        ], $extra));
        DB::table('ventas')->insert(['productos_id' => 100854, 'cantidad' => 1, 'precio' => '150000', 'factura_id' => $id, 'tipo_pago' => 1612]);
        return $id;
    }

    private function esPdf(string $ruta): bool
    {
        return is_file($ruta) && strncmp((string) file_get_contents($ruta, false, null, 0, 4), '%PDF', 4) === 0;
    }

    /** TCPDF escribe cada imagen embebida como un objeto /Subtype /Image. */
    private function tieneImagen(string $ruta): bool
    {
        return is_file($ruta) && preg_match('#/Subtype\s*/Image#', (string) file_get_contents($ruta)) === 1;
    }

    /** @test */
    public function regenera_el_pdf_de_una_factura_por_numero()
    {
        $this->factura(590, '86395342432866');

        $this->artisan('factura:regenerar-pdf', ['numero' => ['590']])
            ->assertExitCode(0);

        $ruta = $this->publico.'/facturas/20_86395342432866_000590.pdf';
        $this->assertTrue($this->esPdf($ruta));
        // El logo del perfil (o el placeholder) tiene que quedar embebido: la copia de
        // Html2Pdf de Laravel ignora en silencio los src file:// y dejaba el PDF sin imagen.
        $this->assertTrue($this->tieneImagen($ruta), 'El PDF regenerado no tiene el logo embebido');
    }

    /** @test */
    public function usa_el_logo_cargado_en_el_perfil()
    {
        mkdir($this->publico.'/assets/perfil', 0777, true);
        copy(base_path('public/assets/img/photos/no-image-featured-image.png'), $this->publico.'/assets/perfil/logo.png');
        DB::table('perfil')->where('id', 1)->update(['logo' => '/assets/perfil/logo.png']);
        unlink($this->publico.'/assets/img/photos/no-image-featured-image.png'); // sin placeholder: solo puede salir del perfil
        $this->factura(590, '86395342432866');

        $this->artisan('factura:regenerar-pdf', ['numero' => ['590']])->assertExitCode(0);

        $this->assertTrue($this->tieneImagen($this->publico.'/facturas/20_86395342432866_000590.pdf'));
    }

    /** @test */
    public function acepta_el_id_de_la_tabla_factura()
    {
        $id = $this->factura(591, '86395342469163');

        $this->artisan('factura:regenerar-pdf', ['--id' => [$id]])->assertExitCode(0);

        $this->assertTrue($this->esPdf($this->publico.'/facturas/20_86395342469163_000591.pdf'));
    }

    /** @test */
    public function no_pisa_un_pdf_existente_salvo_con_forzar()
    {
        $this->factura(590, '86395342432866');
        $ruta = $this->publico.'/facturas/20_86395342432866_000590.pdf';
        file_put_contents($ruta, 'viejo');

        $this->artisan('factura:regenerar-pdf', ['numero' => ['590']])->assertExitCode(0);
        $this->assertSame('viejo', file_get_contents($ruta));

        $this->artisan('factura:regenerar-pdf', ['numero' => ['590'], '--forzar' => true])->assertExitCode(0);
        $this->assertTrue($this->esPdf($ruta));
    }

    /** @test */
    public function faltantes_regenera_solo_las_que_no_estan_en_disco()
    {
        $this->factura(590, '86395342432866');
        $this->factura(591, '86395342469163');
        file_put_contents($this->publico.'/facturas/20_86395342469163_000591.pdf', 'viejo');

        $this->artisan('factura:regenerar-pdf', ['--faltantes' => true])->assertExitCode(0);

        $this->assertTrue($this->esPdf($this->publico.'/facturas/20_86395342432866_000590.pdf'));
        $this->assertSame('viejo', file_get_contents($this->publico.'/facturas/20_86395342469163_000591.pdf'));
    }

    /** @test */
    public function dry_run_no_escribe_nada()
    {
        $this->factura(590, '86395342432866');

        $this->artisan('factura:regenerar-pdf', ['numero' => ['590'], '--dry-run' => true])->assertExitCode(0);

        $this->assertFileNotExists($this->publico.'/facturas/20_86395342432866_000590.pdf');
    }

    /** @test */
    public function sin_renglones_de_venta_avisa_y_no_genera()
    {
        $id = $this->factura(590, '86395342432866');
        DB::table('ventas')->where('factura_id', $id)->delete();

        $this->artisan('factura:regenerar-pdf', ['numero' => ['590']])->assertExitCode(1);

        $this->assertFileNotExists($this->publico.'/facturas/20_86395342432866_000590.pdf');
    }

    /** @test */
    public function la_fecha_de_emision_puede_indicarse_cuando_difiere_del_alta()
    {
        $this->factura(590, '86395342432866');

        $this->artisan('factura:regenerar-pdf', ['numero' => ['590'], '--fecha' => '30/09/2026'])->assertExitCode(1);
        $this->assertFileNotExists($this->publico.'/facturas/20_86395342432866_000590.pdf');

        $this->artisan('factura:regenerar-pdf', ['numero' => ['590'], '--fecha' => '2026-09-28'])->assertExitCode(0);
        $this->assertTrue($this->esPdf($this->publico.'/facturas/20_86395342432866_000590.pdf'));
    }

    /** @test */
    public function por_numero_no_toma_presupuestos_con_el_mismo_correlativo()
    {
        $this->factura(590, '', ['presupuesto' => 1, 'nro_presupuesto' => 590, 'pdf' => '/presupuesto/20__590.pdf']);
        mkdir($this->publico.'/presupuesto');

        $this->artisan('factura:regenerar-pdf', ['numero' => ['590']])->assertExitCode(1);
        $this->assertFileNotExists($this->publico.'/presupuesto/20__590.pdf');
    }

    /** @test */
    public function un_numero_inexistente_falla()
    {
        $this->artisan('factura:regenerar-pdf', ['numero' => ['999']])->assertExitCode(1);
    }

    /** @test */
    public function sin_argumentos_explica_el_uso()
    {
        $this->artisan('factura:regenerar-pdf')->assertExitCode(1);
    }
}
