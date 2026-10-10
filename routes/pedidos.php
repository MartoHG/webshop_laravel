<?php

use App\Http\Controllers\Administracion\PedidoController;
use App\Http\Middleware\VerificarRolAdministrador;
use App\Http\Middleware\VerificarUsuarioActivo;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', VerificarUsuarioActivo::class, VerificarRolAdministrador::class])
    ->prefix('administracion/pedidos')
    ->name('administracion.pedidos.')
    ->group(function (): void {
        Route::get('/', [PedidoController::class, 'index'])->name('index');
        Route::get('/{pedido}', [PedidoController::class, 'mostrar'])->name('mostrar');
        Route::post('/{pedido}/cancelar', [PedidoController::class, 'cancelar'])->name('cancelar');
    });
