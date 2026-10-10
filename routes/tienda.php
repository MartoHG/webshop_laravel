<?php

use App\Http\Controllers\Tienda\InicioController;
use Illuminate\Support\Facades\Route;

Route::prefix('tienda')->name('tienda.')->group(function (): void {
    Route::get('/inicio', [InicioController::class, 'mostrar'])->name('inicio');
});
