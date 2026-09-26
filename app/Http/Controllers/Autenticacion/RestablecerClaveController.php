<?php

namespace App\Http\Controllers\Autenticacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Autenticacion\RestablecerClaveRequest;
use App\Servicios\Autenticacion\AutenticacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;

class RestablecerClaveController extends Controller
{
    public function mostrar(string $token): JsonResponse
    {
        return response()->json([
            'mensaje' => 'Enlace de restablecimiento listo. Envía el token, el correo y la nueva clave a password.update.',
            'token' => $token,
            'email' => request()->query('email'),
        ]);
    }

    public function __invoke(RestablecerClaveRequest $request, AutenticacionService $servicio): JsonResponse|RedirectResponse
    {
        $estado = $servicio->restablecerClave($request->validated());

        if ($estado !== Password::PASSWORD_RESET) {
            if ($request->expectsJson()) {
                return response()->json(['mensaje' => 'El enlace no es válido o ya venció.'], 422);
            }

            return redirect('/')->withErrors(['email' => 'El enlace no es válido o ya venció.']);
        }

        if ($request->expectsJson()) {
            return response()->json(['mensaje' => 'La clave se actualizó correctamente.']);
        }

        return redirect('/')->with('estado', 'La clave se actualizó correctamente.');
    }
}
