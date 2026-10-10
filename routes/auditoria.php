<?php

use App\Http\Controllers\Administracion\AuditoriaController;
use App\Http\Middleware\VerificarRolAdministrador;
use App\Http\Middleware\VerificarUsuarioActivo;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', VerificarUsuarioActivo::class, VerificarRolAdministrador::class])
    ->prefix('administracion')
    ->name('administracion.')
    ->group(function (): void {
        Route::get('/auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');
    });
