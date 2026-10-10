<?php

use App\Http\Controllers\Administracion\AsignacionCategoriaController;
use App\Http\Controllers\Administracion\CategoriaController;
use App\Http\Controllers\Administracion\CategoriaImagenController;
use App\Http\Middleware\VerificarRolAdministrador;
use App\Http\Middleware\VerificarUsuarioActivo;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', VerificarUsuarioActivo::class, VerificarRolAdministrador::class])
    ->prefix('administracion')
    ->name('administracion.')
    ->group(function (): void {
        Route::get('/categorias', [CategoriaController::class, 'index'])->name('categorias.index');
        Route::post('/categorias', [CategoriaController::class, 'store'])->name('categorias.store');
        Route::patch('/categorias/orden', [CategoriaController::class, 'ordenar'])->name('categorias.ordenar');
        Route::patch('/categorias/{categoria}/estado', [CategoriaController::class, 'cambiarEstado'])->name('categorias.estado');
        Route::patch('/categorias/{categoria}', [CategoriaController::class, 'update'])->name('categorias.update');
        Route::post('/categorias/{categoria}/imagen', [CategoriaImagenController::class, 'cambiarImagen'])->name('categorias.imagen.cambiar');
        Route::delete('/categorias/{categoria}/imagen', [CategoriaImagenController::class, 'quitarImagen'])->name('categorias.imagen.quitar');
        Route::patch('/categorias/{categoria}/destacada', [CategoriaImagenController::class, 'marcarDestacada'])->name('categorias.destacada');
        Route::put('/productos/{producto}/categorias', [AsignacionCategoriaController::class, 'sincronizar'])->name('productos.categorias.sincronizar');
        Route::post('/productos/{producto}/categorias/{categoria}', [AsignacionCategoriaController::class, 'asignar'])->name('productos.categorias.asignar');
        Route::delete('/productos/{producto}/categorias/{categoria}', [AsignacionCategoriaController::class, 'desasignar'])->name('productos.categorias.desasignar');
    });
