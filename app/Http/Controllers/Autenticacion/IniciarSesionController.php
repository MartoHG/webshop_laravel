<?php

namespace App\Http\Controllers\Autenticacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Autenticacion\IniciarSesionRequest;
use App\Servicios\Autenticacion\AutenticacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class IniciarSesionController extends Controller
{
    public function __invoke(IniciarSesionRequest $request, AutenticacionService $servicio): JsonResponse|RedirectResponse
    {
        $datos = $request->validated();

        if (! $servicio->iniciarSesion($datos['email'], $datos['password'], (bool) ($datos['recordarme'] ?? false))) {
            if ($request->expectsJson()) {
                return response()->json(['mensaje' => 'Las credenciales son incorrectas o la cuenta está desactivada.'], 422);
            }

            return redirect('/')->withErrors(['email' => 'Las credenciales son incorrectas o la cuenta está desactivada.']);
        }

        $request->session()->regenerate();

        if ($request->expectsJson()) {
            return response()->json(['mensaje' => 'Sesión iniciada.']);
        }

        return redirect('/')->with('estado', 'Sesión iniciada.');
    }
}
