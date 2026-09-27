<?php

use App\Http\Controllers\Administracion\ProductoController;
use App\Http\Controllers\Administracion\VarianteProductoController;
use App\Http\Middleware\VerificarRolAdministrador;
use App\Http\Middleware\VerificarUsuarioActivo;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', VerificarUsuarioActivo::class, VerificarRolAdministrador::class])
    ->prefix('administracion/productos')
    ->name('administracion.productos.')
    ->group(function (): void {
        Route::get('/', [ProductoController::class, 'index'])->name('index');
        Route::post('/', [ProductoController::class, 'store'])->name('crear');
        Route::get('/{producto}', [ProductoController::class, 'show'])->name('mostrar');
        Route::patch('/{producto}', [ProductoController::class, 'update'])->name('actualizar');
        Route::patch('/{producto}/estado', [ProductoController::class, 'cambiarEstado'])->name('estado');
        Route::post('/{producto}/variantes', [VarianteProductoController::class, 'store'])->name('variantes.crear');
        Route::patch('/{producto}/variantes/{varianteProducto}', [VarianteProductoController::class, 'update'])->name('variantes.actualizar');
        Route::patch('/{producto}/variantes/{varianteProducto}/estado', [VarianteProductoController::class, 'cambiarEstado'])->name('variantes.estado');
    });
