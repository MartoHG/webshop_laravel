<?php

use App\Http\Controllers\Administracion\ImagenContenidoController;
use App\Http\Controllers\Administracion\PortadaController;
use App\Http\Middleware\VerificarRolAdministrador;
use App\Http\Middleware\VerificarUsuarioActivo;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', VerificarUsuarioActivo::class, VerificarRolAdministrador::class])
    ->prefix('administracion/contenido')
    ->name('administracion.contenido.')
    ->group(function (): void {
        Route::get('/portada', [PortadaController::class, 'mostrar'])->name('portada.mostrar');
        Route::patch('/portada', [PortadaController::class, 'actualizarTextos'])->name('portada.actualizar');
        Route::post('/portada/imagen', [PortadaController::class, 'cambiarImagen'])->name('portada.imagen');
        Route::delete('/portada', [PortadaController::class, 'quitar'])->name('portada.quitar');
        Route::post('/imagenes/{imagen}/reintentar', [ImagenContenidoController::class, 'reintentar'])->whereNumber('imagen')->name('imagenes.reintentar');
    });
