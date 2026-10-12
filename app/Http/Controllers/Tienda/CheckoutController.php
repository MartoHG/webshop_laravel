<?php

namespace App\Http\Controllers\Tienda;

use App\Enums\Provincia;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tienda\ConfirmarCheckoutRequest;
use App\Models\Usuario;
use App\Servicios\Carrito\CarritoService;
use App\Servicios\Pedidos\CheckoutService;
use App\Servicios\Pedidos\PedidoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function resumen(Request $request, CarritoService $carrito, CheckoutService $servicio): JsonResponse|RedirectResponse|View|Response
    {
        if (! $carrito->estaConfigurado()) {
            return $request->expectsJson() ? $this->errorAjusteNoConfigurado() : $this->tiendaPreparandose();
        }

        /** @var Usuario $usuario */
        $usuario = $request->user();
        $checkout = $servicio->resumen($usuario);

        if ($request->expectsJson()) {
            return response()->json(['checkout' => $checkout]);
        }

        if ($checkout['carrito']['items'] === []) {
            return redirect()
                ->route('carrito.mostrar')
                ->with('error', 'Tu carrito está vacío. Agregá un mueble para continuar.');
        }

        if (! $checkout['carrito']['puede_continuar']) {
            return redirect()
                ->route('carrito.mostrar')
                ->with('error', 'Hay productos que ya no están disponibles. Quitalos para continuar.');
        }

        return view('tienda.checkout', [
            'checkout' => $checkout,
            'envioAnterior' => $servicio->envioDelUltimoPedido($usuario),
            'provincias' => $this->opcionesDeProvincia(),
        ]);
    }

    public function confirmar(ConfirmarCheckoutRequest $request, CarritoService $carrito, CheckoutService $checkout, PedidoService $pedidos): JsonResponse|RedirectResponse|Response
    {
        if (! $carrito->estaConfigurado()) {
            return $request->expectsJson() ? $this->errorAjusteNoConfigurado() : $this->tiendaPreparandose();
        }

        /** @var Usuario $usuario */
        $usuario = $request->user();

        try {
            $resultado = $checkout->confirmar(
                $usuario,
                $request->safe()->except('huella'),
                $request->validated('huella'),
            );
        } catch (ValidationException $excepcion) {
            if ($request->expectsJson()) {
                throw $excepcion;
            }

            return redirect()
                ->route('checkout.resumen')
                ->withInput()
                ->withErrors($excepcion->errors());
        }

        if (! $request->expectsJson()) {
            return redirect()->route('checkout.recibido', $resultado['pedido']->numero);
        }

        return response()->json([
            'mensaje' => 'Pedido creado. Te contactaremos para coordinar el envío.',
            'pedido' => $pedidos->representarParaCliente($resultado['pedido']),
        ], $resultado['creado'] ? 201 : 200);
    }

    public function recibido(Request $request, string $numero, PedidoService $pedidos): JsonResponse|View
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();
        $pedido = $pedidos->mostrarDelCliente($usuario, $numero);

        if ($request->expectsJson()) {
            return response()->json(['pedido' => $pedidos->representarParaCliente($pedido)]);
        }

        return view('tienda.pedido-recibido', ['pedido' => $pedido]);
    }

    /**
     * @return array<string, string>
     */
    private function opcionesDeProvincia(): array
    {
        $opciones = collect(Provincia::cases())
            ->mapWithKeys(fn (Provincia $provincia): array => [$provincia->value => $provincia->nombre()])
            ->all();

        return ['' => 'Elegí una provincia', ...$opciones];
    }

    private function errorAjusteNoConfigurado(): JsonResponse
    {
        return response()->json([
            'codigo' => 'ajuste_ecommerce_no_configurado',
            'mensaje' => 'El checkout no está disponible porque falta configurar el ajuste e-commerce.',
        ], 503);
    }

    private function tiendaPreparandose(): Response
    {
        return response()->view('errors.503', [], 503);
    }
}
