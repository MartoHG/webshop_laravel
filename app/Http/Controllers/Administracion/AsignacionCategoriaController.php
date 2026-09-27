<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administracion\Categorias\AsignarCategoriasProductoRequest;
use App\Servicios\Categorias\CategoriaService;
use Illuminate\Http\JsonResponse;

class AsignacionCategoriaController extends Controller
{
    public function sincronizar(AsignarCategoriasProductoRequest $request, string $producto, CategoriaService $servicio): JsonResponse
    {
        $servicio->sincronizarProducto((int) $producto, $request->validated('categoria_ids'));

        return response()->json(['mensaje' => 'Categorías del producto actualizadas.']);
    }

    public function asignar(string $producto, string $categoria, CategoriaService $servicio): JsonResponse
    {
        $servicio->asignarProducto((int) $producto, (int) $categoria);

        return response()->json(['mensaje' => 'Categoría asignada al producto.']);
    }

    public function desasignar(string $producto, string $categoria, CategoriaService $servicio): JsonResponse
    {
        $servicio->desasignarProducto((int) $producto, (int) $categoria);

        return response()->json(['mensaje' => 'Categoría quitada del producto.']);
    }
}
