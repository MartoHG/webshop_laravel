<?php

use App\Http\Controllers\Administracion\AjusteEcommerceController;
use App\Http\Middleware\VerificarRolAdministrador;
use App\Http\Middleware\VerificarUsuarioActivo;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', VerificarUsuarioActivo::class, VerificarRolAdministrador::class])
    ->prefix('administracion/precios')
    ->name('administracion.precios.')
    ->group(function (): void {
        Route::get('/ajuste-ecommerce', [AjusteEcommerceController::class, 'show'])->name('ajuste-ecommerce.mostrar');
        Route::put('/ajuste-ecommerce', [AjusteEcommerceController::class, 'update'])->name('ajuste-ecommerce.actualizar');
    });
