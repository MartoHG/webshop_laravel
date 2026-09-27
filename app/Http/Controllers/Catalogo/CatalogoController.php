<?php

namespace App\Http\Controllers\Catalogo;

use App\Http\Controllers\Controller;
use App\Servicios\Catalogo\CatalogoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogoController extends Controller
{
    public function index(Request $request, CatalogoService $servicio): JsonResponse
    {
        $filtros = $request->validate([
            'contexto' => ['sometimes', 'string', 'in:inicio,catalogo'],
            'buscar' => ['sometimes', 'string', 'max:160'],
            'categoria' => ['sometimes', 'string', 'max:180'],
            'por_pagina' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        if (! $servicio->estaConfigurado()) {
            return $this->errorAjusteNoConfigurado();
        }

        $pagina = $servicio->listar($filtros);

        return response()->json([
            'productos' => $pagina->items(),
            'paginacion' => [
                'pagina_actual' => $pagina->currentPage(),
                'ultima_pagina' => $pagina->lastPage(),
                'por_pagina' => $pagina->perPage(),
                'total' => $pagina->total(),
            ],
        ]);
    }

    public function show(string $slug, CatalogoService $servicio): JsonResponse
    {
        if (! $servicio->estaConfigurado()) {
            return $this->errorAjusteNoConfigurado();
        }

        return response()->json(['producto' => $servicio->mostrar($slug)]);
    }

    public function categorias(CatalogoService $servicio): JsonResponse
    {
        if (! $servicio->estaConfigurado()) {
            return $this->errorAjusteNoConfigurado();
        }

        return response()->json(['categorias' => $servicio->listarCategorias()]);
    }

    private function errorAjusteNoConfigurado(): JsonResponse
    {
        return response()->json([
            'codigo' => 'ajuste_ecommerce_no_configurado',
            'mensaje' => 'El catálogo no está disponible porque falta configurar el ajuste e-commerce.',
        ], 503);
    }
}
