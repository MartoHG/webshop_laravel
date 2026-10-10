<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administracion\Contenido\ActualizarBeneficioHomeRequest;
use App\Http\Requests\Administracion\Contenido\AdministrarContenidoRequest;
use App\Http\Requests\Administracion\Contenido\CambiarEstadoBeneficioRequest;
use App\Http\Requests\Administracion\Contenido\CrearBeneficioHomeRequest;
use App\Http\Requests\Administracion\Contenido\OrdenarBeneficiosRequest;
use App\Models\BeneficioHome;
use App\Servicios\Contenido\BeneficioHomeService;
use Illuminate\Http\JsonResponse;

class BeneficioHomeController extends Controller
{
    public function index(BeneficioHomeService $servicio): JsonResponse
    {
        return response()->json(['beneficios' => $servicio->listar()]);
    }

    public function store(CrearBeneficioHomeRequest $request, BeneficioHomeService $servicio): JsonResponse
    {
        return response()->json([
            'mensaje' => 'Beneficio creado.',
            'beneficio' => $servicio->crear($request->validated()),
        ], 201);
    }

    public function update(ActualizarBeneficioHomeRequest $request, BeneficioHome $beneficio, BeneficioHomeService $servicio): JsonResponse
    {
        return response()->json([
            'mensaje' => 'Beneficio actualizado.',
            'beneficio' => $servicio->actualizar($beneficio, $request->validated()),
        ]);
    }

    public function cambiarEstado(CambiarEstadoBeneficioRequest $request, BeneficioHome $beneficio, BeneficioHomeService $servicio): JsonResponse
    {
        return response()->json([
            'mensaje' => 'Estado del beneficio actualizado.',
            'beneficio' => $servicio->cambiarEstado($beneficio, $request->boolean('activo')),
        ]);
    }

    public function ordenar(OrdenarBeneficiosRequest $request, BeneficioHomeService $servicio): JsonResponse
    {
        $servicio->ordenar($request->validated('ids'));

        return response()->json([
            'mensaje' => 'Orden de beneficios actualizado.',
            'beneficios' => $servicio->listar(),
        ]);
    }

    public function eliminar(AdministrarContenidoRequest $request, BeneficioHome $beneficio, BeneficioHomeService $servicio): JsonResponse
    {
        $servicio->eliminar($beneficio);

        return response()->json(['mensaje' => 'Beneficio eliminado.']);
    }
}
