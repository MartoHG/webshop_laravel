<?php

namespace App\Http\Controllers\Tienda;

use App\Http\Controllers\Controller;
use App\Servicios\Contenido\InicioTiendaService;
use Illuminate\Http\JsonResponse;

class InicioController extends Controller
{
    public function mostrar(InicioTiendaService $servicio): JsonResponse
    {
        return response()->json($servicio->componer());
    }
}
