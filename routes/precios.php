<?php

use App\Http\Controllers\Administracion\AjusteEcommerceController;
use App\Http\Controllers\Administracion\ReglaPrecioPagoController;
use App\Http\Middleware\VerificarRolAdministrador;
use App\Http\Middleware\VerificarUsuarioActivo;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', VerificarUsuarioActivo::class, VerificarRolAdministrador::class])
    ->prefix('administracion/precios')
    ->name('administracion.precios.')
    ->group(function (): void {
        Route::get('/ajuste-ecommerce', [AjusteEcommerceController::class, 'show'])->name('ajuste-ecommerce.mostrar');
        Route::put('/ajuste-ecommerce', [AjusteEcommerceController::class, 'update'])->name('ajuste-ecommerce.actualizar');
        Route::get('/reglas-pago', [ReglaPrecioPagoController::class, 'index'])->name('reglas-pago.listar');
        Route::post('/reglas-pago', [ReglaPrecioPagoController::class, 'store'])->name('reglas-pago.crear');
        Route::patch('/reglas-pago/{regla}', [ReglaPrecioPagoController::class, 'update'])->name('reglas-pago.actualizar');
    });
