<?php

use App\Http\Controllers\Administracion\BeneficioHomeController;
use App\Http\Controllers\Administracion\ContactoController;
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

        Route::get('/beneficios', [BeneficioHomeController::class, 'index'])->name('beneficios.listar');
        Route::post('/beneficios', [BeneficioHomeController::class, 'store'])->name('beneficios.crear');
        Route::put('/beneficios/orden', [BeneficioHomeController::class, 'ordenar'])->name('beneficios.ordenar');
        Route::patch('/beneficios/{beneficio}', [BeneficioHomeController::class, 'update'])->whereNumber('beneficio')->name('beneficios.actualizar');
        Route::patch('/beneficios/{beneficio}/estado', [BeneficioHomeController::class, 'cambiarEstado'])->whereNumber('beneficio')->name('beneficios.estado');
        Route::delete('/beneficios/{beneficio}', [BeneficioHomeController::class, 'eliminar'])->whereNumber('beneficio')->name('beneficios.eliminar');

        Route::get('/contacto', [ContactoController::class, 'mostrar'])->name('contacto.mostrar');
        Route::put('/contacto', [ContactoController::class, 'actualizar'])->name('contacto.actualizar');
    });
