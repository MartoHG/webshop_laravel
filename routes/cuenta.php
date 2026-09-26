<?php

use App\Http\Controllers\Cuenta\PerfilController;
use App\Http\Middleware\VerificarUsuarioActivo;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', VerificarUsuarioActivo::class])->prefix('cuenta')->name('cuenta.')->group(function (): void {
    Route::put('/perfil', [PerfilController::class, 'actualizar'])->name('perfil.actualizar');
});
