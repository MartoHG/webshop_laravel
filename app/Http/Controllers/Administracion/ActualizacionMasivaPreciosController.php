<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administracion\Precios\AplicarActualizacionMasivaRequest;
use App\Http\Requests\Administracion\Precios\PrevisualizarActualizacionMasivaRequest;
use App\Models\HistorialActualizacionPrecios;
use App\Models\Usuario;
use App\Servicios\Precios\ActualizacionMasivaPreciosService;
use Illuminate\Http\JsonResponse;

class ActualizacionMasivaPreciosController extends Controller
{
    public function previsualizar(PrevisualizarActualizacionMasivaRequest $request, ActualizacionMasivaPreciosService $servicio): JsonResponse
    {
        return response()->json($servicio->previsualizar($request->validated()));
    }

    public function aplicar(AplicarActualizacionMasivaRequest $request, ActualizacionMasivaPreciosService $servicio): JsonResponse
    {
        /** @var Usuario $administrador */
        $administrador = $request->user();
        $datos = $request->validated();
        $historial = $servicio->aplicar($administrador, $datos, $datos['huella']);

        return response()->json([
            'mensaje' => 'Actualización masiva de precios aplicada.',
            'historial' => $servicio->mostrarHistorial($historial),
        ], 201);
    }

    public function listarHistorial(ActualizacionMasivaPreciosService $servicio): JsonResponse
    {
        return response()->json(['historiales' => $servicio->listarHistorial()]);
    }

    public function mostrarHistorial(HistorialActualizacionPrecios $historial, ActualizacionMasivaPreciosService $servicio): JsonResponse
    {
        return response()->json(['historial' => $servicio->mostrarHistorial($historial)]);
    }
}
