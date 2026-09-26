<?php

use App\Http\Controllers\Administracion\UsuarioController;
use App\Http\Middleware\VerificarRolAdministrador;
use App\Http\Middleware\VerificarUsuarioActivo;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', VerificarUsuarioActivo::class, VerificarRolAdministrador::class])
    ->prefix('administracion/usuarios')
    ->name('administracion.usuarios.')
    ->group(function (): void {
        Route::post('/administradores', [UsuarioController::class, 'crearAdministrador'])->name('administradores.crear');
        Route::patch('/{usuario}/estado', [UsuarioController::class, 'cambiarEstado'])->name('estado');
    });
