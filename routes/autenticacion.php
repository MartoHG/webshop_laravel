<?php

use App\Http\Controllers\Autenticacion\CerrarSesionController;
use App\Http\Controllers\Autenticacion\IniciarSesionController;
use App\Http\Controllers\Autenticacion\ReenviarVerificacionController;
use App\Http\Controllers\Autenticacion\RegistroController;
use App\Http\Controllers\Autenticacion\RestablecerClaveController;
use App\Http\Controllers\Autenticacion\SolicitarRestablecimientoController;
use App\Http\Controllers\Autenticacion\VerificarEmailController;
use App\Http\Middleware\VerificarUsuarioActivo;
use Illuminate\Support\Facades\Route;

Route::post('/registro', RegistroController::class)
    ->middleware('guest', 'throttle:5,1')
    ->name('registro');

Route::post('/iniciar-sesion', IniciarSesionController::class)
    ->middleware('guest', 'throttle:5,1')
    ->name('login');

Route::post('/cerrar-sesion', CerrarSesionController::class)
    ->middleware('auth')
    ->name('logout');

Route::post('/clave/solicitar-restablecimiento', SolicitarRestablecimientoController::class)
    ->middleware('guest', 'throttle:5,1')
    ->name('password.email');

Route::get('/clave/restablecer/{token}', [RestablecerClaveController::class, 'mostrar'])
    ->middleware('guest')
    ->name('password.reset');

Route::post('/clave/restablecer', RestablecerClaveController::class)
    ->middleware('guest', 'throttle:5,1')
    ->name('password.update');

Route::get('/email/verificar/{usuario}/{hash}', VerificarEmailController::class)
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::post('/email/verificacion/reenviar', ReenviarVerificacionController::class)
    ->middleware(['auth', VerificarUsuarioActivo::class, 'throttle:6,1'])
    ->name('verification.send');
