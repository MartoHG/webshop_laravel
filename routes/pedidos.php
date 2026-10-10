<?php

use App\Http\Controllers\Administracion\CoordinacionPedidoController;
use App\Http\Controllers\Administracion\EdicionPedidoController;
use App\Http\Controllers\Administracion\EmailPedidoController;
use App\Http\Controllers\Administracion\PagoController;
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
        Route::post('/{pedido}/items', [EdicionPedidoController::class, 'agregarItem'])->name('items.agregar');
        Route::patch('/{pedido}/items/{item}', [EdicionPedidoController::class, 'actualizarItem'])->name('items.actualizar');
        Route::delete('/{pedido}/items/{item}', [EdicionPedidoController::class, 'quitarItem'])->name('items.quitar');
        Route::post('/{pedido}/reemplazar', [EdicionPedidoController::class, 'reemplazar'])->name('reemplazar');
        Route::get('/{pedido}/pagos', [PagoController::class, 'index'])->name('pagos');
        Route::post('/{pedido}/pagos/{pago}/resolver', [PagoController::class, 'resolverRevision'])->name('pagos.resolver');

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
        Route::post('/{pedido}/emails/{tipo}', [EmailPedidoController::class, 'enviar'])
            ->whereIn('tipo', ['no-contactado', 'recordatorio', 'despachado'])
            ->name('emails.enviar');
    });
