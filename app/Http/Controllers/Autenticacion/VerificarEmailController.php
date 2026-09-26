<?php

namespace App\Http\Controllers\Autenticacion;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Servicios\Autenticacion\AutenticacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerificarEmailController extends Controller
{
    public function __invoke(Request $request, Usuario $usuario, string $hash, AutenticacionService $servicio): JsonResponse|RedirectResponse
    {
        if (! $servicio->verificarEmail($usuario, $hash)) {
            if ($usuario->hasVerifiedEmail()) {
                $mensaje = 'El correo ya estaba verificado.';
            } else {
                abort(403);
            }
        } else {
            $mensaje = 'Correo verificado correctamente.';
        }

        if ($request->expectsJson()) {
            return response()->json(['mensaje' => $mensaje]);
        }

        return redirect('/')->with('estado', $mensaje);
    }
}
