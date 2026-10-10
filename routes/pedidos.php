<?php

use App\Http\Controllers\Administracion\CoordinacionPedidoController;
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

        Route::patch('/{pedido}/contacto', [CoordinacionPedidoController::class, 'actualizarContacto'])->name('contacto');
        Route::patch('/{pedido}/envio/cotizacion', [CoordinacionPedidoController::class, 'cotizarEnvio'])->name('envio.cotizacion');
        Route::post('/{pedido}/envio/aceptacion', [CoordinacionPedidoController::class, 'registrarAceptacion'])->name('envio.aceptacion');
        Route::patch('/{pedido}/envio/estado', [CoordinacionPedidoController::class, 'avanzarEnvio'])->name('envio.estado');
        Route::patch('/{pedido}/envio/datos', [CoordinacionPedidoController::class, 'actualizarDatosEnvio'])->name('envio.datos');
        Route::post('/{pedido}/pago/habilitar', [CoordinacionPedidoController::class, 'habilitarPago'])->name('pago.habilitar');
        Route::post('/{pedido}/importes/recalcular', [CoordinacionPedidoController::class, 'recalcularImportes'])->name('importes.recalcular');
        Route::get('/{pedido}/whatsapp/{tipo}', [CoordinacionPedidoController::class, 'whatsapp'])
            ->whereIn('tipo', ['coordinar-envio', 'link-pago'])
            ->name('whatsapp');
    });
