<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administracion\Precios\ActualizarAjusteEcommerceRequest;
use App\Servicios\Precios\AjusteEcommerceService;
use Illuminate\Http\JsonResponse;

class AjusteEcommerceController extends Controller
{
    public function show(AjusteEcommerceService $servicio): JsonResponse
    {
        $porcentaje = $servicio->obtenerPorcentaje();

        return response()->json([
            'ajuste_configurado' => $porcentaje !== null,
            'porcentaje' => $porcentaje,
        ]);
    }

    public function update(ActualizarAjusteEcommerceRequest $request, AjusteEcommerceService $servicio): JsonResponse
    {
        $porcentaje = $servicio->guardarPorcentaje($request->validated('porcentaje'));

        return response()->json([
            'mensaje' => 'Ajuste e-commerce actualizado.',
            'ajuste_configurado' => true,
            'porcentaje' => $porcentaje,
        ]);
    }
}
