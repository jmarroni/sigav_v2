<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ApiProveedoresTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Tablas legacy que no tienen migración (vienen del dump).
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

        $this->artisan('passport:client', ['--personal' => true, '--name' => 'test']);
    }

    private function token(): string
    {
        User::create([
            'name' => 'Integración',
            'email' => 'api@example.com',
            'password' => bcrypt('clave-segura-123'),
        ]);

        return $this->postJson('/api/auth/login', [
            'email' => 'api@example.com',
            'password' => 'clave-segura-123',
        ])->assertOk()->json('access_token');
    }

    private function cargarProveedores(): void
    {
        DB::table('categorias')->insert([
            ['id' => 1, 'nombre' => 'Textil'],
            ['id' => 2, 'nombre' => 'Cuero'],
        ]);
        DB::table('proveedor')->insert([
            ['id' => 1, 'nombre' => 'Zoe', 'apellido' => 'Pérez', 'ciudad' => 'Viedma',
                'provincia' => 'Río Negro', 'telefono' => '2920000000', 'mail' => 'zoe@example.com',
                'sitio_web' => 'https://zoe.example.com', 'usuario' => 'interno',
                'titulo' => 'Tejidos en telar', 'descripcion' => 'Trabaja con lana de oveja patagónica.'],
            ['id' => 2, 'nombre' => 'Ana', 'apellido' => 'Gómez', 'ciudad' => 'Bariloche',
                'provincia' => 'Río Negro', 'telefono' => '2940000000', 'mail' => null,
                'sitio_web' => null, 'usuario' => 'interno', 'titulo' => null, 'descripcion' => null],
        ]);
        DB::table('relacion_categoria_proveedor')->insert([
            ['proveedor_id' => 1, 'categoria_id' => 1],
            ['proveedor_id' => 1, 'categoria_id' => 2],
        ]);
        // Cargadas fuera de orden: la API tiene que devolverlas por `orden`.
        DB::table('proveedor_imagen')->insert([
            ['proveedor_id' => 1, 'orden' => 2, 'imagen_url' => '/storage/proveedores/1/b.jpg'],
            ['proveedor_id' => 1, 'orden' => 1, 'imagen_url' => '/storage/proveedores/1/a.jpg'],
        ]);
    }

    /** @test */
    public function proveedores_requiere_token()
    {
        $this->postJson('/api/auth/proveedores')->assertStatus(401);
    }

    /** @test */
    public function proveedores_devuelve_los_artesanos_con_sus_categorias()
    {
        $this->cargarProveedores();
        $bearer = ['Authorization' => 'Bearer ' . $this->token()];

        $respuesta = $this->withHeaders($bearer)
            ->postJson('/api/auth/proveedores')
            ->assertOk()
            ->json();

        $this->assertCount(2, $respuesta);
        $this->assertSame('Ana', $respuesta[0]['nombre']);
        $this->assertSame([], $respuesta[0]['categorias']);

        $zoe = $respuesta[1];
        $this->assertSame(1, $zoe['id']);
        $this->assertSame('Pérez', $zoe['apellido']);
        $this->assertSame('Río Negro', $zoe['provincia']);
        $this->assertSame(['Cuero', 'Textil'], $zoe['categorias']);
    }

    /** @test */
    public function proveedores_devuelve_titulo_descripcion_e_imagenes_absolutas_en_orden()
    {
        $this->cargarProveedores();
        $bearer = ['Authorization' => 'Bearer ' . $this->token()];

        $respuesta = $this->withHeaders($bearer)
            ->postJson('/api/auth/proveedores')
            ->assertOk()
            ->json();

        $zoe = $respuesta[1];
        $this->assertSame('Tejidos en telar', $zoe['titulo']);
        $this->assertSame('Trabaja con lana de oveja patagónica.', $zoe['descripcion']);
        $this->assertSame([
            url('/storage/proveedores/1/a.jpg'),
            url('/storage/proveedores/1/b.jpg'),
        ], $zoe['imagenes']);

        $this->assertNull($respuesta[0]['titulo']);
        $this->assertSame([], $respuesta[0]['imagenes']);
    }

    /** @test */
    public function proveedores_no_expone_el_usuario_interno()
    {
        $this->cargarProveedores();
        $bearer = ['Authorization' => 'Bearer ' . $this->token()];

        $respuesta = $this->withHeaders($bearer)
            ->postJson('/api/auth/proveedores')
            ->assertOk()
            ->json();

        $this->assertArrayNotHasKey('usuario', $respuesta[0]);
    }
}
