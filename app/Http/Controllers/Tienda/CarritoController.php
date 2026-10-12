<?php

namespace App\Http\Controllers\Tienda;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tienda\ActualizarItemCarritoRequest;
use App\Http\Requests\Tienda\AgregarItemCarritoRequest;
use App\Servicios\Carrito\CarritoService;
use App\Servicios\Carrito\RetiroEnLocalService;
use App\Servicios\Contenido\ContactoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class CarritoController extends Controller
{
    public function mostrar(Request $request, CarritoService $servicio, ContactoService $contacto): JsonResponse|View|Response
    {
        if (! $servicio->estaConfigurado()) {
            return $request->expectsJson() ? $this->errorAjusteNoConfigurado() : $this->tiendaPreparandose();
        }

        if ($request->expectsJson()) {
            return response()->json(['carrito' => $servicio->obtener()]);
        }

        return view('tienda.carrito', $this->datosDelCarrito($servicio, $contacto));
    }

    public function agregar(AgregarItemCarritoRequest $request, CarritoService $servicio): JsonResponse|RedirectResponse|Response
    {
        if (! $servicio->estaConfigurado()) {
            return $request->expectsJson() ? $this->errorAjusteNoConfigurado() : $this->tiendaPreparandose();
        }

        $datos = $request->validated();

        if ($request->expectsJson()) {
            return response()->json([
                'mensaje' => 'Producto agregado al carrito.',
                'carrito' => $servicio->agregar((int) $datos['variante_producto_id'], (int) $datos['cantidad']),
            ]);
        }

        $carrito = $servicio->agregar((int) $datos['variante_producto_id'], (int) $datos['cantidad']);
        $linea = collect($carrito['items'])->firstWhere('variante_producto_id', (int) $datos['variante_producto_id']);

        return redirect()
            ->route('carrito.mostrar')
            ->with('estado', 'Agregaste '.$datos['cantidad'].' × '.($linea['nombre_producto'] ?? 'el mueble'));
    }

    public function actualizar(ActualizarItemCarritoRequest $request, int $variante, CarritoService $servicio, ContactoService $contacto): JsonResponse|RedirectResponse|View|Response
    {
        if (! $servicio->estaConfigurado()) {
            return $request->expectsJson() ? $this->errorAjusteNoConfigurado() : $this->tiendaPreparandose();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'mensaje' => 'Cantidad actualizada.',
                'carrito' => $servicio->cambiarCantidad($variante, (int) $request->validated('cantidad')),
            ]);
        }

        return $this->responderCambio(
            $request,
            $servicio,
            $contacto,
            'Cantidad actualizada.',
            fn () => $servicio->cambiarCantidad($variante, (int) $request->validated('cantidad')),
        );
    }

    public function quitar(Request $request, int $variante, CarritoService $servicio, ContactoService $contacto): JsonResponse|RedirectResponse|View|Response
    {
        if (! $servicio->estaConfigurado()) {
            return $request->expectsJson() ? $this->errorAjusteNoConfigurado() : $this->tiendaPreparandose();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'mensaje' => 'Producto quitado del carrito.',
                'carrito' => $servicio->quitar($variante),
            ]);
        }

        return $this->responderCambio(
            $request,
            $servicio,
            $contacto,
            'Quitaste el mueble del carrito.',
            fn () => $servicio->quitar($variante),
        );
    }

    public function retiro(Request $request, CarritoService $servicio, RetiroEnLocalService $retiro): JsonResponse|RedirectResponse|Response
    {
        if (! $servicio->estaConfigurado()) {
            return $request->expectsJson() ? $this->errorAjusteNoConfigurado() : $this->tiendaPreparandose();
        }

        if ($request->expectsJson()) {
            return response()->json($retiro->enlace());
        }

        try {
            return redirect()->away($retiro->enlace()['url']);
        } catch (ValidationException $excepcion) {
            return redirect()
                ->route('carrito.mostrar')
                ->with('error', $excepcion->validator->errors()->first());
        }
    }

    public function vaciar(Request $request, CarritoService $servicio, ContactoService $contacto): JsonResponse|RedirectResponse|View|Response
    {
        if (! $servicio->estaConfigurado()) {
            return $request->expectsJson() ? $this->errorAjusteNoConfigurado() : $this->tiendaPreparandose();
        }

        if ($request->expectsJson()) {
            $servicio->vaciar();

            return response()->json([
                'mensaje' => 'Carrito vaciado.',
                'carrito' => $servicio->obtener(),
            ]);
        }

        return $this->responderCambio(
            $request,
            $servicio,
            $contacto,
            'Vaciaste el carrito.',
            fn () => $servicio->vaciar(),
        );
    }

    /**
     * Respuesta de un cambio hecho desde la página: con htmx devuelve solo el contenido del carrito;
     * en un formulario clásico redirige al carrito con el aviso.
     */
    private function responderCambio(Request $request, CarritoService $servicio, ContactoService $contacto, string $mensaje, callable $cambio): RedirectResponse|View
    {
        $conHtmx = $request->hasHeader('HX-Request');

        try {
            $cambio();
        } catch (ValidationException $excepcion) {
            if ($conHtmx) {
                return $this->fragmentoDelCarrito($servicio, $contacto, $excepcion->validator->errors()->all());
            }

            return redirect()
                ->route('carrito.mostrar')
                ->with('error', $excepcion->validator->errors()->first());
        }

        if ($conHtmx) {
            return $this->fragmentoDelCarrito($servicio, $contacto);
        }

        return redirect()
            ->route('carrito.mostrar')
            ->with('estado', $mensaje);
    }

    /**
     * @param  list<string>  $errores
     * @return array{carrito: array<string, mixed>, hayRetiro: bool, direccionRetiro: ?string, errores: list<string>}
     */
    private function datosDelCarrito(CarritoService $servicio, ContactoService $contacto, array $errores = []): array
    {
        $carrito = $servicio->obtener();

        return [
            'carrito' => $carrito,
            'hayRetiro' => collect($carrito['items'])->contains('disponible', true) && $contacto->whatsappDelNegocio() !== null,
            'direccionRetiro' => $contacto->publico()['direccion'],
            'errores' => $errores,
        ];
    }

    /**
     * @param  list<string>  $errores
     */
    private function fragmentoDelCarrito(CarritoService $servicio, ContactoService $contacto, array $errores = []): View
    {
        return view('tienda.partials.carrito-actualizado', $this->datosDelCarrito($servicio, $contacto, $errores));
    }

    private function errorAjusteNoConfigurado(): JsonResponse
    {
        return response()->json([
            'codigo' => 'ajuste_ecommerce_no_configurado',
            'mensaje' => 'El carrito no está disponible porque falta configurar el ajuste e-commerce.',
        ], 503);
    }

    private function tiendaPreparandose(): Response
    {
        return response()->view('errors.503', [], 503);
    }
}
