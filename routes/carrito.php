<?php

use App\Http\Controllers\Tienda\CarritoController;
use Illuminate\Support\Facades\Route;

Route::prefix('carrito')->name('carrito.')->group(function (): void {
    Route::get('/', [CarritoController::class, 'mostrar'])->name('mostrar');
    Route::post('/items', [CarritoController::class, 'agregar'])->middleware('throttle:60,1')->name('items.agregar');
    Route::patch('/items/{variante}', [CarritoController::class, 'actualizar'])->whereNumber('variante')->middleware('throttle:60,1')->name('items.actualizar');
    Route::delete('/items/{variante}', [CarritoController::class, 'quitar'])->whereNumber('variante')->middleware('throttle:60,1')->name('items.quitar');
    Route::delete('/', [CarritoController::class, 'vaciar'])->middleware('throttle:60,1')->name('vaciar');
});
