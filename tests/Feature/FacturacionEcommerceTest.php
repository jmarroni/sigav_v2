<?php

namespace Tests\Feature;

use App\Exceptions\VentaRechazada;
use App\Facturacion\EmisorComprobanteAfip;
use App\Facturacion\FacturacionEcommerce;
use App\Facturacion\RegeneradorPdfFactura;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FacturacionEcommerceTest extends TestCase
{
    use RefreshDatabase;

    private const SUCURSAL = 1;

    /** @var array<int, array> datos que recibió el emisor AFIP */
    public $emitidos = [];

    /** @var \Throwable|null si está seteado, el emisor falla con esta excepción */
    public $falloAfip = null;

    /** @var bool si es true, la generación del PDF falla */
    public $falloPdf = false;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('sucursales', function (Blueprint $t) {
            $t->increments('id');
            $t->string('nombre');
            $t->integer('pto_vta')->nullable();
        });
        Schema::create('ventas', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('productos_id');
            $t->integer('cantidad');
            $t->string('precio');
            $t->string('costo');
            $t->string('fecha');
            $t->string('usuario');
            $t->integer('sucursal_id');
            $t->integer('estado')->nullable();
            $t->integer('factura_id')->nullable();
            $t->integer('tipo_pago')->nullable();
            $t->integer('lista_precio')->nullable();
            $t->decimal('descuento', 5, 2)->default(0);
            $t->string('referencia_api', 100)->nullable();
        });
        Schema::create('productos', function (Blueprint $t) {
            $t->increments('id');
            $t->string('nombre');
        });
        Schema::create('factura', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('sucursal_id');
            $t->string('fecha');
            $t->string('usuario');
            $t->string('numero');
            $t->string('cae')->nullable();
            $t->string('fechacae')->nullable();
            $t->string('total');
            $t->string('pdf')->nullable();
            $t->integer('presupuesto')->default(0);
            $t->integer('nro_presupuesto')->default(0);
            $t->string('nombre')->nullable();
            $t->string('direccion')->nullable();
            $t->string('documento')->nullable();
            $t->string('tipo_documento')->nullable();
            $t->string('iva')->nullable();
            $t->decimal('descuento_total', 5, 2)->default(0);
        });

        DB::table('sucursales')->insert(['id' => self::SUCURSAL, 'nombre' => 'e-commerce', 'pto_vta' => 20]);
        // afip_config la crea una migración del proyecto: sólo se carga el entorno activo.
        DB::table('afip_config')->insert(['entorno' => 'prod', 'activo' => true, 'comprobante' => 11]);
        DB::table('productos')->insert([['id' => 1, 'nombre' => 'Mate'], ['id' => 2, 'nombre' => 'Llaveros']]);

        config(['ecommerce.sucursal_id' => self::SUCURSAL]);

        $test = $this;
        $this->app->instance(EmisorComprobanteAfip::class, new class($test) extends EmisorComprobanteAfip {
            private $test;
            public function __construct($test) { $this->test = $test; }
            public function emitir(array $data): array
            {
                $this->test->registrarEmision($data);
                return ['CAE' => '74111111111111', 'CAEFchVto' => '2026-11-07', 'voucher_number' => 12];
            }
        });
        $this->app->instance(RegeneradorPdfFactura::class, new class($test) extends RegeneradorPdfFactura {
            private $test;
            public function __construct($test) { $this->test = $test; }
            public function regenerar($factura, ?string $fechaEmision = null): string
            {
                if ($this->test->falloPdf) {
                    throw new \RuntimeException('pdf roto');
                }
                return '';
            }
        });
    }

    /** Llamado por el doble del emisor. */
    public function registrarEmision(array $data): void
    {
        $this->emitidos[] = $data;
        if ($this->falloAfip) {
            throw $this->falloAfip;
        }
    }

    /**
     * Pedido con dos renglones: 2 × 60000.00 + 1 × 15000.00 = 135000.00.
     */
    private function pedido(string $referencia = 'PED-1', array $cliente = []): int
    {
        $id = DB::table('api_ventas_referencias')->insertGetId(array_merge([
            'user_id' => 1, 'referencia' => $referencia, 'created_at' => now(),
        ], $cliente));
        DB::table('ventas')->insert([
            ['productos_id' => 1, 'cantidad' => 2, 'precio' => '60000.00', 'costo' => '400', 'fecha' => now()->toDateTimeString(),
                'usuario' => 'API e-commerce', 'sucursal_id' => self::SUCURSAL, 'estado' => 1, 'lista_precio' => 1, 'referencia_api' => $referencia],
            ['productos_id' => 2, 'cantidad' => 1, 'precio' => '15000.00', 'costo' => '90', 'fecha' => now()->toDateTimeString(),
                'usuario' => 'API e-commerce', 'sucursal_id' => self::SUCURSAL, 'estado' => 1, 'lista_precio' => 1, 'referencia_api' => $referencia],
        ]);

        return $id;
    }

    private function facturar(int $pedidoId): array
    {
        return app(FacturacionEcommerce::class)->facturar($pedidoId);
    }

    /** @test */
    public function factura_el_pedido_como_factura_c_a_consumidor_final()
    {
        $id = $this->pedido();

        $r = $this->facturar($id);

        $this->assertCount(1, $this->emitidos);
        $data = $this->emitidos[0];
        $this->assertSame(11, $data['CbteTipo']);
        $this->assertSame(20, $data['PtoVta']);
        $this->assertSame(99, $data['DocTipo']);
        $this->assertSame(0, $data['DocNro']);
        $this->assertSame(5, $data['CondicionIVAReceptorId']);
        $this->assertSame('135000.00', $data['ImpTotal']);
        $this->assertSame('135000.00', $data['ImpNeto']);
        $this->assertSame(0, $data['ImpIVA']);

        $this->assertDatabaseHas('factura', [
            'id' => $r['factura_id'], 'sucursal_id' => self::SUCURSAL, 'numero' => '000012',
            'cae' => '74111111111111', 'total' => '135000.00', 'nombre' => 'CONSUMIDOR FINAL',
            'pdf' => '/facturas/20_74111111111111_000012.pdf',
        ]);
        $this->assertSame(2, DB::table('ventas')->where('factura_id', $r['factura_id'])->where('estado', 2)->count());
        $this->assertDatabaseHas('api_ventas_referencias', ['id' => $id, 'factura_id' => $r['factura_id'], 'facturando_at' => null]);
        $this->assertNull($r['aviso']);
    }

    /** @test */
    public function con_cliente_usa_su_documento_y_su_condicion_iva()
    {
        $this->pedido('PED-2', [
            'cliente_tipo_documento' => '80', 'cliente_documento' => '20123456786',
            'cliente_nombre' => 'Juan Perez', 'cliente_condicion_iva' => 1,
        ]);
        $id = DB::table('api_ventas_referencias')->where('referencia', 'PED-2')->value('id');

        $this->facturar($id);

        $this->assertSame(80, $this->emitidos[0]['DocTipo']);
        $this->assertSame(20123456786, $this->emitidos[0]['DocNro']);
        $this->assertSame(1, $this->emitidos[0]['CondicionIVAReceptorId']);
        $this->assertDatabaseHas('factura', ['nombre' => 'Juan Perez', 'documento' => '20123456786', 'tipo_documento' => '80']);
    }

    /** @test */
    public function sin_punto_de_venta_no_llama_a_afip_y_no_deja_bloqueo()
    {
        DB::table('sucursales')->where('id', self::SUCURSAL)->update(['pto_vta' => 0]);
        $id = $this->pedido();

        try {
            $this->facturar($id);
            $this->fail('debía fallar');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('punto de venta', $e->getMessage());
        }

        $this->assertCount(0, $this->emitidos);
        $this->assertDatabaseHas('api_ventas_referencias', ['id' => $id, 'facturando_at' => null]);
    }

    /** @test */
    public function un_pedido_ya_facturado_no_se_factura_otra_vez()
    {
        $id = $this->pedido();
        $this->facturar($id);

        $this->expectException(\RuntimeException::class);
        $this->facturar($id);
    }

    /** @test */
    public function si_afip_falla_no_guarda_factura_y_deja_el_bloqueo_para_verificar()
    {
        $id = $this->pedido();
        $this->falloAfip = new \RuntimeException('timeout de AFIP');

        try {
            $this->facturar($id);
            $this->fail('debía fallar');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('no confirmó', $e->getMessage());
        }

        $this->assertSame(0, DB::table('factura')->count());
        $this->assertSame(0, DB::table('ventas')->where('estado', 2)->count());
        $this->assertDatabaseHas('api_ventas_referencias', ['id' => $id, 'factura_id' => null]);
        $this->assertNotNull(DB::table('api_ventas_referencias')->where('id', $id)->value('facturando_at'));

        // Mientras esté bloqueado no se reintenta sin revisar.
        $this->falloAfip = null;
        try {
            $this->facturar($id);
            $this->fail('debía fallar');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('bloqueado', $e->getMessage());
        }
        $this->assertCount(1, $this->emitidos);

        // Revisado en AFIP y confirmado que no se emitió: se libera y se puede facturar.
        app(FacturacionEcommerce::class)->liberar($id);
        $this->facturar($id);
        $this->assertCount(2, $this->emitidos);
    }

    /** @test */
    public function si_falla_el_pdf_la_factura_queda_guardada_con_aviso()
    {
        $id = $this->pedido();
        $this->falloPdf = true;

        $r = $this->facturar($id);

        $this->assertDatabaseHas('factura', ['id' => $r['factura_id']]);
        $this->assertNotNull($r['aviso']);
    }

    /** @test */
    public function el_listado_muestra_total_renglones_y_estado()
    {
        $facturado = $this->pedido('PED-A');
        $this->pedido('PED-B');
        $this->facturar($facturado);

        $pedidos = collect(app(FacturacionEcommerce::class)->pedidos())->keyBy('referencia');

        $this->assertSame('135.000,00', $pedidos['PED-B']['total']);
        $this->assertSame(['2 × Mate', '1 × Llaveros'], $pedidos['PED-B']['renglones']);
        $this->assertFalse($pedidos['PED-B']['facturado']);
        $this->assertTrue($pedidos['PED-A']['facturado']);
    }
}
