<?php

namespace App\Http\Controllers\Autenticacion;

use App\Http\Controllers\Controller;
use App\Servicios\Autenticacion\AutenticacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReenviarVerificacionController extends Controller
{
    public function __invoke(Request $request, AutenticacionService $servicio): JsonResponse|RedirectResponse
    {
        $servicio->reenviarVerificacion($request->user());
        $mensaje = 'Si el correo todavía necesita verificación, enviaremos un nuevo enlace.';

        if ($request->expectsJson()) {
            return response()->json(['mensaje' => $mensaje]);
        }

        return redirect('/')->with('estado', $mensaje);
    }
}
