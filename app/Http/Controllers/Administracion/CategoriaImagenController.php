<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administracion\Categorias\MarcarCategoriaDestacadaRequest;
use App\Http\Requests\Administracion\Contenido\AdministrarContenidoRequest;
use App\Http\Requests\Administracion\Contenido\SubirImagenContenidoRequest;
use App\Models\Categoria;
use App\Servicios\Categorias\CategoriaImagenService;
use Illuminate\Http\JsonResponse;

class CategoriaImagenController extends Controller
{
    public function cambiarImagen(SubirImagenContenidoRequest $request, Categoria $categoria, CategoriaImagenService $servicio): JsonResponse
    {
        $imagen = $servicio->cambiarImagen($categoria, $request->validated('imagen'));

        return response()->json([
            'mensaje' => 'Imagen de categoría recibida y pendiente de procesamiento.',
            'imagen' => ['id' => $imagen->id, 'estado' => $imagen->estado],
            'categoria' => $categoria->refresh(),
        ], 202);
    }

    public function quitarImagen(AdministrarContenidoRequest $request, Categoria $categoria, CategoriaImagenService $servicio): JsonResponse
    {
        $servicio->quitarImagen($categoria);

        return response()->json([
            'mensaje' => 'Imagen de categoría quitada.',
            'categoria' => $categoria->refresh(),
        ]);
    }

    public function marcarDestacada(MarcarCategoriaDestacadaRequest $request, Categoria $categoria, CategoriaImagenService $servicio): JsonResponse
    {
        $servicio->marcarDestacada($categoria, $request->boolean('destacada_en_home'));

        return response()->json([
            'mensaje' => 'Destacado de categoría actualizado.',
            'categoria' => $categoria->refresh(),
        ]);
    }
}
