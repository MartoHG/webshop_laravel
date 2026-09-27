<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administracion\Categorias\ActualizarCategoriaRequest;
use App\Http\Requests\Administracion\Categorias\CambiarEstadoCategoriaRequest;
use App\Http\Requests\Administracion\Categorias\CrearCategoriaRequest;
use App\Http\Requests\Administracion\Categorias\OrdenarCategoriasRequest;
use App\Servicios\Categorias\CategoriaService;
use Illuminate\Http\JsonResponse;

class CategoriaController extends Controller
{
    public function index(CategoriaService $servicio): JsonResponse
    {
        return response()->json(['categorias' => $servicio->listar()]);
    }

    public function store(CrearCategoriaRequest $request, CategoriaService $servicio): JsonResponse
    {
        $categoria = $servicio->crear($request->validated());

        return response()->json(['mensaje' => 'Categoría creada.', 'categoria' => $categoria], 201);
    }

    public function update(ActualizarCategoriaRequest $request, string $categoria, CategoriaService $servicio): JsonResponse
    {
        return response()->json([
            'mensaje' => 'Categoría actualizada.',
            'categoria' => $servicio->actualizar((int) $categoria, $request->validated()),
        ]);
    }

    public function cambiarEstado(CambiarEstadoCategoriaRequest $request, string $categoria, CategoriaService $servicio): JsonResponse
    {
        return response()->json([
            'mensaje' => 'Estado de categoría actualizado.',
            'categoria' => $servicio->cambiarEstado((int) $categoria, $request->boolean('activo')),
        ]);
    }

    public function ordenar(OrdenarCategoriasRequest $request, CategoriaService $servicio): JsonResponse
    {
        $servicio->ordenar($request->validated('categoria_ids'));

        return response()->json(['mensaje' => 'Orden de categorías actualizado.']);
    }
}
