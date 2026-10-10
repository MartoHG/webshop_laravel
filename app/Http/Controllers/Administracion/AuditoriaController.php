<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administracion\Auditoria\ListarRegistrosAuditoriaRequest;
use App\Servicios\Auditoria\AuditoriaService;
use Illuminate\Http\JsonResponse;

class AuditoriaController extends Controller
{
    public function index(ListarRegistrosAuditoriaRequest $request, AuditoriaService $servicio): JsonResponse
    {
        return response()->json(['registros' => $servicio->listar($request->validated())]);
    }
}
