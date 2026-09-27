<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administracion\Productos\ActualizarEstadoVarianteProductoRequest;
use App\Http\Requests\Administracion\Productos\ActualizarVarianteProductoRequest;
use App\Http\Requests\Administracion\Productos\CrearVarianteProductoRequest;
use App\Models\Producto;
use App\Models\VarianteProducto;
use App\Servicios\Productos\VarianteProductoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class VarianteProductoController extends Controller
{
    public function store(CrearVarianteProductoRequest $request, Producto $producto, VarianteProductoService $servicio): JsonResponse
    {
        $variante = $servicio->crear($producto, $request->validated());

        return response()->json(['mensaje' => 'Variante creada como borrador.', 'variante' => $variante], 201);
    }

    public function update(ActualizarVarianteProductoRequest $request, Producto $producto, VarianteProducto $varianteProducto, VarianteProductoService $servicio): JsonResponse
    {
        if ($varianteProducto->producto_id !== $producto->id) {
            throw ValidationException::withMessages(['variante' => __('La variante no pertenece a este producto.')]);
        }

        return response()->json([
            'mensaje' => 'Variante actualizada.',
            'variante' => $servicio->actualizar($varianteProducto, $request->validated()),
        ]);
    }

    public function cambiarEstado(ActualizarEstadoVarianteProductoRequest $request, Producto $producto, VarianteProducto $varianteProducto, VarianteProductoService $servicio): JsonResponse
    {
        if ($varianteProducto->producto_id !== $producto->id) {
            throw ValidationException::withMessages(['variante' => __('La variante no pertenece a este producto.')]);
        }

        return response()->json([
            'mensaje' => 'Estado de variante actualizado.',
            'variante' => $servicio->actualizarEstado($producto, $varianteProducto, $request->validated()),
        ]);
    }
}
