<?php

namespace App\Http\Controllers\Catalogo;

use App\Http\Controllers\Controller;
use App\Servicios\Catalogo\CatalogoService;
use App\Servicios\Catalogo\DetalleProductoService;
use App\Servicios\Contenido\ContactoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CatalogoController extends Controller
{
    public function index(Request $request, CatalogoService $servicio): JsonResponse|View|Response
    {
        $filtros = $request->validate([
            'contexto' => ['sometimes', 'string', 'in:inicio,catalogo'],
            'buscar' => ['sometimes', 'string', 'max:160'],
            'categoria' => ['sometimes', 'string', 'max:180'],
            'por_pagina' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        if (! $servicio->estaConfigurado()) {
            return $request->expectsJson() ? $this->errorAjusteNoConfigurado() : $this->tiendaPreparandose();
        }

        $pagina = $servicio->listar($filtros);

        if ($request->expectsJson()) {
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

        $categorias = $servicio->listarCategorias();
        $categoriaActiva = collect($categorias)->firstWhere('slug', $filtros['categoria'] ?? null);
        $buscar = $filtros['buscar'] ?? '';

        return view('tienda.catalogo', [
            'titulo' => $this->tituloDelCatalogo($categoriaActiva, $buscar),
            'productos' => $pagina->withQueryString(),
            'categorias' => $categorias,
            'categoriaActiva' => $categoriaActiva,
            'buscar' => $buscar,
        ]);
    }

    public function show(Request $request, string $slug, CatalogoService $servicio, DetalleProductoService $detalles, ContactoService $contacto): JsonResponse|View|Response
    {
        if (! $servicio->estaConfigurado()) {
            return $request->expectsJson() ? $this->errorAjusteNoConfigurado() : $this->tiendaPreparandose();
        }

        $producto = $servicio->mostrar($slug);

        if ($request->expectsJson()) {
            return response()->json(['producto' => $producto]);
        }

        $detalle = $detalles->componer($producto, $request->integer('variante') ?: null, $contacto->publico()['direccion']);

        if ($request->hasHeader('HX-Request')) {
            return view('tienda.partials.producto-variante', ['detalle' => $detalle]);
        }

        return view('tienda.producto', ['detalle' => $detalle]);
    }

    public function categorias(CatalogoService $servicio): JsonResponse
    {
        if (! $servicio->estaConfigurado()) {
            return $this->errorAjusteNoConfigurado();
        }

        return response()->json(['categorias' => $servicio->listarCategorias()]);
    }

    /**
     * @param  ?array{nombre: string, slug: string, destacada_en_home: bool, imagen_url: ?string, imagen_miniatura_url: ?string}  $categoriaActiva
     */
    private function tituloDelCatalogo(?array $categoriaActiva, string $buscar): string
    {
        if ($categoriaActiva !== null) {
            return $categoriaActiva['nombre'];
        }

        if ($buscar !== '') {
            return 'Resultados para «'.$buscar.'»';
        }

        return 'Muebles';
    }

    private function errorAjusteNoConfigurado(): JsonResponse
    {
        return response()->json([
            'codigo' => 'ajuste_ecommerce_no_configurado',
            'mensaje' => 'El catálogo no está disponible porque falta configurar el ajuste e-commerce.',
        ], 503);
    }

    private function tiendaPreparandose(): Response
    {
        return response()->view('errors.503', [], 503);
    }
}
