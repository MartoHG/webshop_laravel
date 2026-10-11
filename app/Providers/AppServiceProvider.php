<?php

namespace App\Providers;

use App\Servicios\Carrito\CarritoService;
use App\Servicios\Contenido\ContactoService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('components.layouts.tienda', function ($vista): void {
            $vista->with([
                'unidadesCarrito' => app(CarritoService::class)->cantidadDeUnidades(),
                'contactoTienda' => app(ContactoService::class)->publico(),
            ]);
        });
    }
}
