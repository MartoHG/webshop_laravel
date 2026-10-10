<?php

use App\Http\Controllers\Webhooks\MercadoPagoController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/mercadopago', [MercadoPagoController::class, 'recibir'])
    ->middleware('throttle:120,1')
    ->name('webhooks.mercadopago');
