<?php

namespace App\Http\Controllers\Cuenta;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cuenta\ActualizarPerfilRequest;
use App\Models\Usuario;
use App\Servicios\Cuenta\CuentaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class PerfilController extends Controller
{
    public function actualizar(ActualizarPerfilRequest $request, CuentaService $cuentaService): JsonResponse|RedirectResponse
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();
        $usuario = $cuentaService->actualizarPerfil($usuario, $request->validated());

        if ($request->expectsJson()) {
            return response()->json(['mensaje' => 'Perfil actualizado.', 'usuario' => $usuario]);
        }

        return back()->with('estado', 'Perfil actualizado.');
    }
}
