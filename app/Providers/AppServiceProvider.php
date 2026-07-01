<?php

namespace App\Providers;

use App\Services\MercadoPago\MercadoPagoService;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Sin este binding, el contenedor auto-resuelve el parámetro opcional
        // `?Client $client = null` de MercadoPagoService construyendo un
        // `new Client()` vacío (sin base_uri), porque Client es una clase
        // instanciable y el contenedor prioriza resolverla por sobre usar el
        // valor por defecto `null`. Eso rompe toda llamada real (no las que
        // instancian el service directo, como los tests).
        $this->app->when(MercadoPagoService::class)
            ->needs(Client::class)
            ->give(function () {
                return new Client([
                    'base_uri' => 'https://api.mercadopago.com/',
                    'timeout' => 10,
                    'connect_timeout' => 5,
                ]);
            });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
