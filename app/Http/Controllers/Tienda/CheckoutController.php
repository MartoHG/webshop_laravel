<?php

namespace App\Http\Controllers\Tienda;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tienda\ConfirmarCheckoutRequest;
use App\Models\Usuario;
use App\Servicios\Carrito\CarritoService;
use App\Servicios\Pedidos\CheckoutService;
use App\Servicios\Pedidos\PedidoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function resumen(Request $request, CarritoService $carrito, CheckoutService $servicio): JsonResponse
    {
        if (! $carrito->estaConfigurado()) {
            return $this->errorAjusteNoConfigurado();
        }

        /** @var Usuario $usuario */
        $usuario = $request->user();

        return response()->json(['checkout' => $servicio->resumen($usuario)]);
    }

    public function confirmar(ConfirmarCheckoutRequest $request, CarritoService $carrito, CheckoutService $checkout, PedidoService $pedidos): JsonResponse
    {
        if (! $carrito->estaConfigurado()) {
            return $this->errorAjusteNoConfigurado();
        }

        /** @var Usuario $usuario */
        $usuario = $request->user();
        $resultado = $checkout->confirmar(
            $usuario,
            $request->safe()->except('huella'),
            $request->validated('huella'),
        );

        return response()->json([
            'mensaje' => 'Pedido creado. Te contactaremos para coordinar el envío.',
            'pedido' => $pedidos->representarParaCliente($resultado['pedido']),
        ], $resultado['creado'] ? 201 : 200);
    }

    private function errorAjusteNoConfigurado(): JsonResponse
    {
        return response()->json([
            'codigo' => 'ajuste_ecommerce_no_configurado',
            'mensaje' => 'El checkout no está disponible porque falta configurar el ajuste e-commerce.',
        ], 503);
    }
}
