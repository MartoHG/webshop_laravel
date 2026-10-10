<?php

use App\Http\Controllers\Tienda\CheckoutController;
use App\Http\Middleware\VerificarEmailVerificado;
use App\Http\Middleware\VerificarUsuarioActivo;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', VerificarUsuarioActivo::class])->group(function (): void {
    Route::get('/checkout', [CheckoutController::class, 'resumen'])->name('checkout.resumen');
    Route::post('/checkout', [CheckoutController::class, 'confirmar'])
        ->middleware(['throttle:10,1', VerificarEmailVerificado::class])
        ->name('checkout.confirmar');
});
