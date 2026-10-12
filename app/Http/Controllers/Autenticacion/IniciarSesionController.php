<?php

namespace App\Http\Controllers\Autenticacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Autenticacion\IniciarSesionRequest;
use App\Servicios\Autenticacion\AutenticacionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class IniciarSesionController extends Controller
{
    public function mostrar(): View
    {
        return view('tienda.iniciar-sesion');
    }

    public function __invoke(IniciarSesionRequest $request, AutenticacionService $servicio): JsonResponse|RedirectResponse
    {
        $datos = $request->validated();

        if (! $servicio->iniciarSesion($datos['email'], $datos['password'], (bool) ($datos['recordarme'] ?? false))) {
            if ($request->expectsJson()) {
                return response()->json(['mensaje' => 'Las credenciales son incorrectas o la cuenta está desactivada.'], 422);
            }

            return back()
                ->withErrors(['email' => 'El email o la contraseña no son correctos. Revisalos y probá de nuevo.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        if ($request->expectsJson()) {
            return response()->json(['mensaje' => 'Sesión iniciada.']);
        }

        return redirect()->intended(route('tienda.inicio'))->with('estado', 'Sesión iniciada.');
    }
}
