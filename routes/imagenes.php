<?php

use App\Http\Controllers\Administracion\ImagenProductoController;
use App\Http\Middleware\VerificarRolAdministrador;
use App\Http\Middleware\VerificarUsuarioActivo;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', VerificarUsuarioActivo::class, VerificarRolAdministrador::class])
    ->prefix('administracion/imagenes')
    ->name('administracion.imagenes.')
    ->group(function (): void {
        Route::post('/', [ImagenProductoController::class, 'subir'])->name('subir');
        Route::get('/{imagen}', [ImagenProductoController::class, 'mostrar'])->whereNumber('imagen')->name('mostrar');
        Route::post('/asignaciones', [ImagenProductoController::class, 'asignar'])->name('asignar');
        Route::put('/orden', [ImagenProductoController::class, 'reordenar'])->name('orden');
        Route::post('/archivos/{archivo}/reintentar', [ImagenProductoController::class, 'reintentar'])->whereNumber('archivo')->name('reintentar');
        Route::patch('/{imagen}/principal', [ImagenProductoController::class, 'establecerPrincipal'])->whereNumber('imagen')->name('principal');
        Route::delete('/{imagen}', [ImagenProductoController::class, 'eliminar'])->whereNumber('imagen')->name('eliminar');
    });
