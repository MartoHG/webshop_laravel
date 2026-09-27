<?php

use App\Http\Controllers\Catalogo\CatalogoController;
use Illuminate\Support\Facades\Route;

Route::prefix('catalogo')->name('catalogo.')->group(function (): void {
    Route::get('/', [CatalogoController::class, 'index'])->name('index');
    Route::get('/categorias', [CatalogoController::class, 'categorias'])->name('categorias');
    Route::get('/{slug}', [CatalogoController::class, 'show'])->name('mostrar');
});
