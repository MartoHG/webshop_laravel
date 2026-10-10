<?php

use App\Http\Controllers\Tienda\PagoController;
use Illuminate\Support\Facades\Route;

Route::get('/pago/{token}', [PagoController::class, 'mostrar'])
    ->whereAlphaNumeric('token')
    ->name('pago.mostrar');

Route::post('/pago/{token}/intentos', [PagoController::class, 'iniciarIntento'])
    ->whereAlphaNumeric('token')
    ->middleware('throttle:10,1')
    ->name('pago.intentos.crear');

Route::post('/pago/{token}/intentos/{pago}/tarjeta', [PagoController::class, 'pagarConTarjeta'])
    ->whereAlphaNumeric('token')
    ->whereNumber('pago')
    ->middleware('throttle:10,1')
    ->name('pago.intentos.tarjeta');

Route::get('/pago/{token}/estado', [PagoController::class, 'estado'])
    ->whereAlphaNumeric('token')
    ->name('pago.estado');
