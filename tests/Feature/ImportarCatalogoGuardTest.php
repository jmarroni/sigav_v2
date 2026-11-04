<?php
// tests/Feature/ImportarCatalogoGuardTest.php

namespace Tests\Feature;

use Tests\TestCase;

class ImportarCatalogoGuardTest extends TestCase
{
    /** @test */
    public function con_force_y_sin_permiso_aborta_antes_de_leer_nada()
    {
        config(['app.catalogo_importar_permitido' => false]);

        $this->artisan('catalogo:importar', ['--force' => true, '--path' => '/no/existe.csv'])
            ->expectsOutput('Importación bloqueada: CATALOGO_IMPORTAR_PERMITIDO no es true en esta instancia. Este comando BORRA productos, stock, ventas y facturas.')
            ->assertExitCode(2);
    }

    /** @test */
    public function el_dry_run_sigue_disponible_sin_permiso()
    {
        config(['app.catalogo_importar_permitido' => false]);

        // Sin --force no borra nada, así que el guard no aplica: llega a la validación del CSV.
        $this->artisan('catalogo:importar', ['--path' => '/no/existe.csv'])
            ->expectsOutput('No se encontró el CSV en: /no/existe.csv')
            ->assertExitCode(1);
    }
}
