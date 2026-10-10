<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administracion\Contenido\AdministrarContenidoRequest;
use App\Models\ImagenContenido;
use App\Servicios\Imagenes\ImagenContenidoService;
use Illuminate\Http\JsonResponse;

class ImagenContenidoController extends Controller
{
    public function reintentar(AdministrarContenidoRequest $request, ImagenContenido $imagen, ImagenContenidoService $servicio): JsonResponse
    {
        $servicio->reencolar($imagen);

        return response()->json(['mensaje' => 'El procesamiento de la imagen se volvió a encolar.'], 202);
    }
}
