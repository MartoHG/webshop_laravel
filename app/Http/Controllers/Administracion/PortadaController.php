<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administracion\Contenido\ActualizarPortadaRequest;
use App\Http\Requests\Administracion\Contenido\AdministrarContenidoRequest;
use App\Http\Requests\Administracion\Contenido\SubirImagenContenidoRequest;
use App\Servicios\Contenido\PortadaService;
use Illuminate\Http\JsonResponse;

class PortadaController extends Controller
{
    public function mostrar(AdministrarContenidoRequest $request, PortadaService $servicio): JsonResponse
    {
        return response()->json(['portada' => $servicio->obtenerParaAdministracion()]);
    }

    public function actualizarTextos(ActualizarPortadaRequest $request, PortadaService $servicio): JsonResponse
    {
        $servicio->actualizarTextos($request->validated());

        return response()->json([
            'mensaje' => 'Portada actualizada.',
            'portada' => $servicio->obtenerParaAdministracion(),
        ]);
    }

    public function cambiarImagen(SubirImagenContenidoRequest $request, PortadaService $servicio): JsonResponse
    {
        $imagen = $servicio->cambiarImagen($request->validated('imagen'));

        return response()->json([
            'mensaje' => 'Imagen de portada recibida y pendiente de procesamiento.',
            'imagen' => ['id' => $imagen->id, 'estado' => $imagen->estado],
        ], 202);
    }

    public function quitar(AdministrarContenidoRequest $request, PortadaService $servicio): JsonResponse
    {
        $servicio->quitar();

        return response()->json(['mensaje' => 'Portada quitada.']);
    }
}
