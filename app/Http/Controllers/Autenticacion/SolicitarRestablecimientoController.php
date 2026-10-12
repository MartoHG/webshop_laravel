<?php

namespace App\Http\Controllers\Autenticacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Autenticacion\SolicitarRestablecimientoRequest;
use App\Servicios\Autenticacion\AutenticacionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class SolicitarRestablecimientoController extends Controller
{
    public function mostrar(): View
    {
        return view('tienda.clave-olvide');
    }

    public function __invoke(SolicitarRestablecimientoRequest $request, AutenticacionService $servicio): JsonResponse|RedirectResponse
    {
        $servicio->solicitarRestablecimiento($request->validated('email'));
        $mensaje = 'Si la cuenta existe, enviaremos instrucciones para restablecer la clave.';

        if ($request->expectsJson()) {
            return response()->json(['mensaje' => $mensaje]);
        }

        return back()->with('estado', 'Si el email está registrado, te mandamos un link para crear una contraseña nueva.');
    }
}
