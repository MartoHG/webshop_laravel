<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administracion\Productos\ActualizarEstadoProductoRequest;
use App\Http\Requests\Administracion\Productos\ActualizarProductoRequest;
use App\Http\Requests\Administracion\Productos\CrearProductoRequest;
use App\Models\Producto;
use App\Servicios\Productos\ProductoService;
use Illuminate\Http\JsonResponse;

class ProductoController extends Controller
{
    public function index(ProductoService $servicio): JsonResponse
    {
        return response()->json(['productos' => $servicio->listar()]);
    }

    public function show(Producto $producto, ProductoService $servicio): JsonResponse
    {
        return response()->json(['producto' => $servicio->mostrar($producto)]);
    }

    public function store(CrearProductoRequest $request, ProductoService $servicio): JsonResponse
    {
        $producto = $servicio->crear($request->validated());

        return response()->json(['mensaje' => 'Producto creado como borrador.', 'producto' => $producto], 201);
    }

    public function update(ActualizarProductoRequest $request, Producto $producto, ProductoService $servicio): JsonResponse
    {
        return response()->json(['mensaje' => 'Producto actualizado.', 'producto' => $servicio->actualizar($producto, $request->validated())]);
    }

    public function cambiarEstado(ActualizarEstadoProductoRequest $request, Producto $producto, ProductoService $servicio): JsonResponse
    {
        return response()->json([
            'mensaje' => 'Estado del producto actualizado.',
            'producto' => $servicio->actualizarEstado($producto, $request->validated()),
        ]);
    }
}
