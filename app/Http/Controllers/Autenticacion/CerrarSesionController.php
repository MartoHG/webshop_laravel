<?php

namespace App\Http\Controllers\Autenticacion;

use App\Http\Controllers\Controller;
use App\Servicios\Autenticacion\AutenticacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CerrarSesionController extends Controller
{
    public function __invoke(Request $request, AutenticacionService $servicio): JsonResponse|RedirectResponse
    {
        $servicio->cerrarSesion($request);

        if ($request->expectsJson()) {
            return response()->json(['mensaje' => 'Sesión cerrada.']);
        }

        return redirect('/')->with('estado', 'Sesión cerrada.');
    }
}
