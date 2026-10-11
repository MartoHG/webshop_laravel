<?php

namespace App\Http\Controllers\Tienda;

use App\Http\Controllers\Controller;
use App\Servicios\Contenido\InicioTiendaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InicioController extends Controller
{
    public function mostrar(Request $request, InicioTiendaService $servicio): JsonResponse|View
    {
        $datos = $servicio->componer();

        if ($request->expectsJson()) {
            return response()->json($datos);
        }

        return view('tienda.inicio', $datos);
    }
}
