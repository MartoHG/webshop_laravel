<?php

namespace App\Http\Controllers\Tienda;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tienda\ActualizarItemCarritoRequest;
use App\Http\Requests\Tienda\AgregarItemCarritoRequest;
use App\Servicios\Carrito\CarritoService;
use Illuminate\Http\JsonResponse;

class CarritoController extends Controller
{
    public function mostrar(CarritoService $servicio): JsonResponse
    {
        if (! $servicio->estaConfigurado()) {
            return $this->errorAjusteNoConfigurado();
        }

        return response()->json(['carrito' => $servicio->obtener()]);
    }

    public function agregar(AgregarItemCarritoRequest $request, CarritoService $servicio): JsonResponse
    {
        if (! $servicio->estaConfigurado()) {
            return $this->errorAjusteNoConfigurado();
        }

        $datos = $request->validated();

        return response()->json([
            'mensaje' => 'Producto agregado al carrito.',
            'carrito' => $servicio->agregar((int) $datos['variante_producto_id'], (int) $datos['cantidad']),
        ]);
    }

    public function actualizar(ActualizarItemCarritoRequest $request, int $variante, CarritoService $servicio): JsonResponse
    {
        if (! $servicio->estaConfigurado()) {
            return $this->errorAjusteNoConfigurado();
        }

        return response()->json([
            'mensaje' => 'Cantidad actualizada.',
            'carrito' => $servicio->cambiarCantidad($variante, (int) $request->validated('cantidad')),
        ]);
    }

    public function quitar(int $variante, CarritoService $servicio): JsonResponse
    {
        if (! $servicio->estaConfigurado()) {
            return $this->errorAjusteNoConfigurado();
        }

        return response()->json([
            'mensaje' => 'Producto quitado del carrito.',
            'carrito' => $servicio->quitar($variante),
        ]);
    }

    public function vaciar(CarritoService $servicio): JsonResponse
    {
        if (! $servicio->estaConfigurado()) {
            return $this->errorAjusteNoConfigurado();
        }

        $servicio->vaciar();

        return response()->json([
            'mensaje' => 'Carrito vaciado.',
            'carrito' => $servicio->obtener(),
        ]);
    }

    private function errorAjusteNoConfigurado(): JsonResponse
    {
        return response()->json([
            'codigo' => 'ajuste_ecommerce_no_configurado',
            'mensaje' => 'El carrito no está disponible porque falta configurar el ajuste e-commerce.',
        ], 503);
    }
}
