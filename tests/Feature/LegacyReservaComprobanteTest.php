<?php
// tests/Feature/LegacyReservaComprobanteTest.php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * La reserva atómica de comprobantes (legacy_comprobantes.php) usa mysqli y
 * un índice UNIQUE: se prueba contra el MySQL del entorno Docker (DB_HOST)
 * con una tabla temporal propia. Se omite si no hay MySQL alcanzable.
 */
class LegacyReservaComprobanteTest extends TestCase
{
    /** @var \mysqli */
    private $conn;
    private $tabla;

    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__.'/../../public/legacy_comprobantes.php';
        $host = getenv('DB_HOST') ?: 'db';
        $user = getenv('DB_USERNAME') ?: 'root';
        $pass = getenv('DB_PASSWORD') ?: '';
        $db = getenv('DB_DATABASE') ?: 'laravel';
        mysqli_report(MYSQLI_REPORT_OFF);
        $this->conn = @new \mysqli($host, $user, $pass, $db);
        if ($this->conn->connect_errno) {
            $this->markTestSkipped('Sin MySQL para probar la reserva: '.$this->conn->connect_error);
        }
        $this->tabla = 'zz_test_reserva_'.substr(md5(uniqid()), 0, 8);
        $this->conn->query("CREATE TABLE `{$this->tabla}` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sucursal_id INT NULL, factura_id INT NULL, fecha VARCHAR(20) NULL, usuario VARCHAR(100) NULL,
            numero INT NULL, cae VARCHAR(50) NULL, fechacae VARCHAR(20) NOT NULL, total VARCHAR(20) NULL,
            pdf VARCHAR(200) NULL, nombre VARCHAR(200) NULL, tipo_documento INT NULL, iva INT NULL,
            UNIQUE KEY u_factura (factura_id)
        ) ENGINE=InnoDB");
    }

    protected function tearDown(): void
    {
        if ($this->conn && ! $this->conn->connect_errno) {
            $this->conn->query("DROP TABLE IF EXISTS `{$this->tabla}`");
            $this->conn->close();
        }
        parent::tearDown();
    }

    private function fila(array $extra = []): array
    {
        return $extra + ['sucursal_id' => 2, 'fecha' => date('Y-m-d H:i:s'), 'usuario' => 'op', 'total' => '500', 'nombre' => 'Cliente', 'tipo_documento' => '', 'iva' => 4];
    }

    private function cae(): array
    {
        return ['numero' => '000005', 'cae' => '86400962218528', 'fechacae' => '17-10-2026', 'pdf' => '/notas_credito/20_86400962218528_000005.pdf'];
    }

    /** @test */
    public function la_segunda_reserva_de_la_misma_factura_es_duplicada_y_la_de_otra_pasa()
    {
        $a = legacy_reserva_tomar($this->conn, $this->tabla, 'factura_id', 1, $this->fila());
        $b = legacy_reserva_tomar($this->conn, $this->tabla, 'factura_id', 1, $this->fila());
        $c = legacy_reserva_tomar($this->conn, $this->tabla, 'factura_id', 2, $this->fila());

        $this->assertArrayHasKey('id', $a);
        $this->assertArrayHasKey('duplicada', $b);
        $this->assertSame('', $b['duplicada']['cae']);
        $this->assertArrayHasKey('id', $c);
    }

    /** @test */
    public function tipo_documento_vacio_se_guarda_como_null_y_no_rompe_el_insert()
    {
        $r = legacy_reserva_tomar($this->conn, $this->tabla, 'factura_id', 1, $this->fila(['tipo_documento' => '']));

        $this->assertArrayHasKey('id', $r);
        $row = $this->conn->query("SELECT tipo_documento FROM `{$this->tabla}` WHERE id = {$r['id']}")->fetch_assoc();
        $this->assertNull($row['tipo_documento']);
    }

    /** @test */
    public function liberar_borra_el_placeholder_y_la_factura_vuelve_a_estar_disponible()
    {
        $a = legacy_reserva_tomar($this->conn, $this->tabla, 'factura_id', 1, $this->fila());

        $this->assertSame(1, legacy_reserva_liberar($this->conn, $this->tabla, $a['id']));
        $this->assertArrayHasKey('id', legacy_reserva_tomar($this->conn, $this->tabla, 'factura_id', 1, $this->fila()));
    }

    /** @test */
    public function confirmar_graba_el_cae_y_despues_ni_se_libera_ni_se_duplica()
    {
        $a = legacy_reserva_tomar($this->conn, $this->tabla, 'factura_id', 1, $this->fila());

        $this->assertTrue(legacy_reserva_confirmar($this->conn, $this->tabla, $a['id'], $this->cae(), 'factura_id', 1, $this->fila()));
        $row = $this->conn->query("SELECT numero, cae, pdf FROM `{$this->tabla}` WHERE id = {$a['id']}")->fetch_assoc();
        $this->assertSame('86400962218528', $row['cae']);
        $this->assertSame(5, (int) $row['numero']);
        $this->assertSame(0, legacy_reserva_liberar($this->conn, $this->tabla, $a['id']));
        $b = legacy_reserva_tomar($this->conn, $this->tabla, 'factura_id', 1, $this->fila());
        $this->assertSame('86400962218528', $b['duplicada']['cae']);
    }

    /** @test */
    public function confirmar_reinserta_si_la_reserva_fue_borrada_por_la_limpieza()
    {
        $a = legacy_reserva_tomar($this->conn, $this->tabla, 'factura_id', 1, $this->fila());
        $this->conn->query("DELETE FROM `{$this->tabla}` WHERE id = {$a['id']}");

        $this->assertTrue(legacy_reserva_confirmar($this->conn, $this->tabla, $a['id'], $this->cae(), 'factura_id', 1, $this->fila()));
        $this->assertSame(1, (int) $this->conn->query("SELECT COUNT(*) c FROM `{$this->tabla}` WHERE factura_id = 1 AND cae = '86400962218528'")->fetch_assoc()['c']);
    }

    /** @test */
    public function confirmar_nunca_pierde_el_cae_aunque_otro_haya_tomado_la_factura()
    {
        $a = legacy_reserva_tomar($this->conn, $this->tabla, 'factura_id', 1, $this->fila());
        $this->conn->query("DELETE FROM `{$this->tabla}` WHERE id = {$a['id']}");
        legacy_reserva_tomar($this->conn, $this->tabla, 'factura_id', 1, $this->fila());

        $this->assertTrue(legacy_reserva_confirmar($this->conn, $this->tabla, $a['id'], $this->cae(), 'factura_id', 1, $this->fila()));
        $row = $this->conn->query("SELECT factura_id FROM `{$this->tabla}` WHERE cae = '86400962218528'")->fetch_assoc();
        $this->assertNull($row['factura_id']);
    }

    /** @test */
    public function la_limpieza_solo_toca_placeholders_viejos_con_comprobante_asociado()
    {
        $vieja = date('Y-m-d H:i:s', time() - 3600);
        legacy_reserva_tomar($this->conn, $this->tabla, 'factura_id', 1, $this->fila(['fecha' => $vieja]));   // colgada
        legacy_reserva_tomar($this->conn, $this->tabla, 'factura_id', 2, $this->fila());                       // viva
        $c = legacy_reserva_tomar($this->conn, $this->tabla, 'factura_id', 3, $this->fila(['fecha' => $vieja]));
        legacy_reserva_confirmar($this->conn, $this->tabla, $c['id'], $this->cae(), 'factura_id', 3, $this->fila()); // emitida
        $this->conn->query("INSERT INTO `{$this->tabla}` (factura_id, fecha, cae, fechacae) VALUES (NULL, '$vieja', '', '')"); // histórica

        $this->assertSame(1, legacy_reserva_limpiar($this->conn, $this->tabla, 'factura_id', 30));
        $this->assertSame(3, (int) $this->conn->query("SELECT COUNT(*) c FROM `{$this->tabla}`")->fetch_assoc()['c']);
    }

    /** @test */
    public function sin_la_columna_del_comprobante_asociado_la_reserva_falla_cerrada()
    {
        $r = legacy_reserva_tomar($this->conn, $this->tabla, 'nota_credito_id', 1, $this->fila());

        $this->assertArrayHasKey('errno', $r);
        $this->assertSame(1054, $r['errno']);
    }
}
