<?php

namespace App\Http\Controllers\Autenticacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Autenticacion\RestablecerClaveRequest;
use App\Servicios\Autenticacion\AutenticacionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class RestablecerClaveController extends Controller
{
    public function mostrar(Request $request, string $token): JsonResponse|View
    {
        if ($request->expectsJson()) {
            return response()->json([
                'mensaje' => 'Enlace de restablecimiento listo. Envía el token, el correo y la nueva clave a password.update.',
                'token' => $token,
                'email' => $request->query('email'),
            ]);
        }

        $email = $request->query('email');

        return view('tienda.clave-restablecer', [
            'token' => $token,
            'email' => is_string($email) ? $email : '',
        ]);
    }

    public function __invoke(RestablecerClaveRequest $request, AutenticacionService $servicio): JsonResponse|RedirectResponse
    {
        $estado = $servicio->restablecerClave($request->validated());

        if ($estado !== Password::PASSWORD_RESET) {
            if ($request->expectsJson()) {
                return response()->json(['mensaje' => 'El enlace no es válido o ya venció.'], 422);
            }

            return back()
                ->withErrors(['email' => 'El enlace no es válido o ya venció.'])
                ->onlyInput('email');
        }

        if ($request->expectsJson()) {
            return response()->json(['mensaje' => 'La clave se actualizó correctamente.']);
        }

        return redirect()->route('login.formulario')->with('estado', 'Tu contraseña se actualizó. Ya podés ingresar.');
    }
}
