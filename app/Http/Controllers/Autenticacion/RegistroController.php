<?php

namespace App\Http\Controllers\Autenticacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Autenticacion\RegistroRequest;
use App\Servicios\Autenticacion\AutenticacionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class RegistroController extends Controller
{
    public function mostrar(): View
    {
        return view('tienda.registro');
    }

    public function __invoke(RegistroRequest $request, AutenticacionService $servicio): JsonResponse|RedirectResponse
    {
        $usuario = $servicio->registrar($request->validated());
        $request->session()->regenerate();

        if ($request->expectsJson()) {
            return response()->json([
                'mensaje' => 'Cuenta creada. Revisá tu correo para verificar la dirección.',
                'usuario' => $usuario->only(['id', 'nombre', 'apellido', 'email', 'rol']),
            ], 201);
        }

        return redirect()
            ->intended(route('tienda.inicio'))
            ->with('estado', 'Creamos tu cuenta. Te mandamos un email a '.$usuario->email.' para confirmarla.');
    }
}
