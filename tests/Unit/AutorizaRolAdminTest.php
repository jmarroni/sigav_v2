<?php

namespace Tests\Unit;

use App\Http\Controllers\Concerns\AutorizaRolAdmin;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AutorizaRolAdminTest extends TestCase
{
    private function gate(): object
    {
        return new class {
            use AutorizaRolAdmin;

            public function chequear(int $rolMinimo = 2): void
            {
                $this->autorizar($rolMinimo);
            }

            public function chequearTieneRol(int $rolMinimo = 2): bool
            {
                return $this->tieneRol($rolMinimo);
            }
        };
    }

    protected function tearDown(): void
    {
        unset($_COOKIE['kiosco']);
        parent::tearDown();
    }

    /** @test */
    public function sin_cookie_kiosco_tiene_rol_devuelve_false()
    {
        unset($_COOKIE['kiosco']);

        $this->assertFalse($this->gate()->chequearTieneRol());
    }

    /** @test */
    public function sin_cookie_kiosco_autorizar_aborta_con_403()
    {
        unset($_COOKIE['kiosco']);

        $this->expectException(HttpException::class);
        try {
            $this->gate()->chequear();
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            throw $e;
        }
    }
}
