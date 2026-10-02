<?php
// tests/Feature/RegenerarPdfNotaCreditoTest.php

namespace Tests\Feature;

use App\Facturacion\ConsultaComprobanteAfip;
use App\Facturacion\RegeneradorPdfNotaCredito;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Regeneración del PDF de notas de crédito ya emitidas (CAE en la base):
 * comando nota-credito:regenerar-pdf y botón "Regenerar PDF" de /notas/credito.
 * La nota no guarda a qué factura anula ni la observación: la descripción se
 * arma con el comprobante asociado que informa AFIP (consulta simulada acá).
 */
class RegenerarPdfNotaCreditoTest extends TestCase
{
    use RefreshDatabase;

    /** @var string */
    private $publico;

    /** @var array respuestas de la consulta simulada, por "ptovta-numero" */
    private $afipRespuestas = [];

    /** @var array llamadas recibidas por la consulta simulada */
    private $afipLlamadas = [];

    /** @var \Throwable|null si se setea, la consulta simulada lanza esto */
    private $afipFalla = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->publico = sys_get_temp_dir().'/regenerar_nc_'.uniqid();
        mkdir($this->publico.'/notas_credito', 0777, true);
        mkdir($this->publico.'/assets/img/photos', 0777, true);
        copy(base_path('public/assets/img/photos/no-image-featured-image.png'), $this->publico.'/assets/img/photos/no-image-featured-image.png');
        $this->app->instance('path.public', $this->publico);

        $this->crearTablas();
        DB::table('afip_config')->insert([
            ['entorno' => 'prod', 'cuit' => '30715251988', 'ptovta' => '20', 'comprobante' => '11', 'condicion_iva' => 'IVA EXENTO', 'ingresos_brutos' => '46161295', 'emitir' => 1, 'solicitar_datos' => 0, 'activo' => 1],
        ]);
        DB::table('perfil')->insert(['id' => 1, 'nombre' => 'Mercado Artesanal', 'logo' => '']);
        DB::table('sucursales')->insert(['id' => 40, 'nombre' => 'Cerro Catedral', 'pto_vta' => 17, 'direccion' => 'Cerro Catedral San Carlos de Bariloche', 'codigo_postal' => '8401', 'provincia' => 'Río Negro']);

        $test = $this;
        $this->app->instance(ConsultaComprobanteAfip::class, new class($test) implements ConsultaComprobanteAfip {
            private $t;
            public function __construct($t) { $this->t = $t; }
            public function comprobanteAsociado(int $ptovta, int $numero, int $tipo): ?array
            {
                $this->t->registrarLlamada([$ptovta, $numero, $tipo]);
                if ($this->t->fallaAfip()) {
                    throw $this->t->fallaAfip();
                }
                return $this->t->respuestaAfip("$ptovta-$numero");
            }
        });
    }

    public function registrarLlamada(array $args): void { $this->afipLlamadas[] = $args; }
    public function fallaAfip(): ?\Throwable { return $this->afipFalla; }
    public function respuestaAfip(string $clave): ?array { return $this->afipRespuestas[$clave] ?? null; }

    protected function tearDown(): void
    {
        unset($_COOKIE['kiosco'], $_COOKIE['rol']);
        exec('rm -rf '.escapeshellarg($this->publico));
        parent::tearDown();
    }

    private function crearTablas(): void
    {
        Schema::create('nota_de_credito', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('sucursal_id')->nullable();
            $t->string('fecha', 20)->nullable();
            $t->string('usuario', 100)->nullable();
            $t->integer('numero')->nullable();
            $t->string('cae', 50)->nullable();
            $t->string('fechacae', 20)->default('');
            $t->string('total', 20)->nullable();
            $t->string('pdf', 200)->nullable();
            $t->integer('presupuesto')->nullable();
            $t->integer('nro_presupuesto')->nullable();
            $t->string('nombre', 200)->nullable();
            $t->string('direccion', 200)->nullable();
            $t->string('documento', 200)->nullable();
            $t->string('mail', 200)->nullable();
            $t->integer('tipo_documento')->nullable();
            $t->integer('iva')->nullable();
        });
        Schema::create('sucursales', function (Blueprint $t) {
            $t->increments('id');
            $t->string('nombre', 200);
            $t->integer('pto_vta')->nullable();
            $t->string('direccion', 200)->nullable();
            $t->string('codigo_postal', 20)->nullable();
            $t->string('provincia', 200)->nullable();
        });
        Schema::create('perfil', function (Blueprint $t) {
            $t->increments('id');
            $t->string('nombre', 200)->nullable();
            $t->string('logo', 200)->nullable();
        });
        Schema::create('usuarios', function (Blueprint $t) {
            $t->increments('id');
            $t->string('usuario');
            $t->string('clave')->nullable();
            $t->integer('rol_id');
            $t->string('nombre')->nullable();
            $t->string('apellido')->nullable();
        });
    }

    private function nota(int $numero = 111, string $cae = '86384356395368'): int
    {
        return DB::table('nota_de_credito')->insertGetId([
            'sucursal_id' => 40, 'fecha' => '2026-09-23 11:20:49', 'usuario' => 'MDIAZ_VENTA',
            'numero' => $numero, 'cae' => $cae, 'fechacae' => '03-10-2026', 'total' => '70000',
            'pdf' => sprintf('/notas_credito/20_%s_%06d.pdf', $cae, $numero), 'presupuesto' => 0,
            'nombre' => 'Consumidor Final', 'direccion' => '', 'documento' => '0', 'tipo_documento' => 99, 'iva' => 4,
        ]);
    }

    private function logueadoConRol(int $rol): self
    {
        config(['app.legacy_semilla' => 'semilla-de-test']);
        DB::table('usuarios')->insert(['usuario' => 'operador', 'rol_id' => $rol]);
        $_COOKIE['kiosco'] = 'operador';
        $_COOKIE['rol'] = sha1('semilla-de-test'.$rol.'semilla-de-test');
        return $this->actingAs(User::create(['name' => 'op', 'email' => 'operador@legacy.local', 'password' => bcrypt('x')]));
    }

    private function esPdfConImagen(string $ruta): bool
    {
        $c = (string) @file_get_contents($ruta);
        return strncmp($c, '%PDF', 4) === 0 && preg_match('#/Subtype\s*/Image#', $c) === 1;
    }

    /** @test */
    public function el_comando_regenera_el_pdf_con_la_factura_asociada_que_informa_afip()
    {
        $this->nota();
        $this->afipRespuestas['20-111'] = ['tipo' => 11, 'ptovta' => 17, 'nro' => 289];

        $this->artisan('nota-credito:regenerar-pdf', ['numero' => ['111']])
            ->expectsOutput('Nro 000111 (id 1, emitida 2026-09-23 11:20:49, /notas_credito/20_86384356395368_000111.pdf): PDF generado. Detalle: Nota de crédito s/ Factura C 00017-00000289')
            ->assertExitCode(0);

        $this->assertTrue($this->esPdfConImagen($this->publico.'/notas_credito/20_86384356395368_000111.pdf'));
        // Se consulta la NC (tipo 13) con el punto de venta del archivo y su número.
        $this->assertSame([[20, 111, 13]], $this->afipLlamadas);
    }

    /** @test */
    public function sin_afip_usa_una_descripcion_generica_y_no_consulta()
    {
        $this->nota();

        $this->artisan('nota-credito:regenerar-pdf', ['numero' => ['111'], '--sin-afip' => true])->assertExitCode(0);

        $this->assertSame([], $this->afipLlamadas);
        $this->assertTrue($this->esPdfConImagen($this->publico.'/notas_credito/20_86384356395368_000111.pdf'));
    }

    /** @test */
    public function si_afip_falla_genera_igual_con_descripcion_generica_y_avisa()
    {
        $this->nota();
        $this->afipFalla = new \RuntimeException('WSAA caído');

        $this->artisan('nota-credito:regenerar-pdf', ['numero' => ['111']])
            ->expectsOutput('Nro 000111: no se pudo consultar el comprobante asociado en AFIP (WSAA caído); se usa "Anulación de comprobante".')
            ->assertExitCode(0);

        $this->assertTrue($this->esPdfConImagen($this->publico.'/notas_credito/20_86384356395368_000111.pdf'));
    }

    /** @test */
    public function la_descripcion_se_arma_con_el_comprobante_asociado()
    {
        $id = $this->nota();
        $nota = DB::table('nota_de_credito')->find($id);
        $svc = $this->app->make(RegeneradorPdfNotaCredito::class);

        $this->afipRespuestas['20-111'] = ['tipo' => 11, 'ptovta' => 17, 'nro' => 289];
        $this->assertSame('Nota de crédito s/ Factura C 00017-00000289', $svc->descripcion($nota, true));

        $this->afipRespuestas['20-111'] = ['tipo' => 6, 'ptovta' => 3, 'nro' => 7];
        $this->assertSame('Nota de crédito s/ Factura B 00003-00000007', $svc->descripcion($nota, true));

        $this->afipRespuestas['20-111'] = null; // AFIP no informa asociado
        $this->assertSame('Anulación de comprobante', $svc->descripcion($nota, true));
        $this->assertSame('Anulación de comprobante', $svc->descripcion($nota, false));
    }

    /** @test */
    public function faltantes_y_dry_run_se_comportan_como_en_facturas()
    {
        $this->nota(111, '86384356395368');
        $this->nota(112, '86384356414548');
        file_put_contents($this->publico.'/notas_credito/20_86384356414548_000112.pdf', 'viejo');

        $this->artisan('nota-credito:regenerar-pdf', ['--faltantes' => true, '--dry-run' => true, '--sin-afip' => true])->assertExitCode(0);
        $this->assertFileNotExists($this->publico.'/notas_credito/20_86384356395368_000111.pdf');

        $this->artisan('nota-credito:regenerar-pdf', ['--faltantes' => true, '--sin-afip' => true])->assertExitCode(0);
        $this->assertTrue($this->esPdfConImagen($this->publico.'/notas_credito/20_86384356395368_000111.pdf'));
        $this->assertSame('viejo', file_get_contents($this->publico.'/notas_credito/20_86384356414548_000112.pdf'));
    }

    /** @test */
    public function el_boton_regenera_y_devuelve_el_link()
    {
        $id = $this->nota();
        $this->afipRespuestas['20-111'] = ['tipo' => 11, 'ptovta' => 17, 'nro' => 289];

        $this->logueadoConRol(4)->postJson("/notas-credito/$id/regenerar-pdf")
            ->assertOk()
            ->assertJson(['ok' => true, 'pdf' => '/notas_credito/20_86384356395368_000111.pdf']);

        $this->assertTrue($this->esPdfConImagen($this->publico.'/notas_credito/20_86384356395368_000111.pdf'));
    }

    /** @test */
    public function el_boton_exige_rol_admin_y_cae()
    {
        $id = $this->nota();

        $this->logueadoConRol(1)->postJson("/notas-credito/$id/regenerar-pdf")->assertStatus(403);

        // Mismo usuario, ahora con rol 5 (y cookie rol acorde) pero la nota sin CAE.
        DB::table('usuarios')->where('usuario', 'operador')->update(['rol_id' => 5]);
        $_COOKIE['rol'] = sha1('semilla-de-test'.'5'.'semilla-de-test');
        DB::table('nota_de_credito')->where('id', $id)->update(['cae' => '']);
        $this->postJson("/notas-credito/$id/regenerar-pdf")->assertStatus(422)->assertJson(['ok' => false]);
        $this->assertFileNotExists($this->publico.'/notas_credito/20_86384356395368_000111.pdf');
    }
}
