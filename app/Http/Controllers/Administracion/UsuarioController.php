<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administracion\CambiarEstadoUsuarioRequest;
use App\Http\Requests\Administracion\CrearAdministradorRequest;
use App\Models\Usuario;
use App\Servicios\Administracion\UsuarioAdministracionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class UsuarioController extends Controller
{
    public function crearAdministrador(CrearAdministradorRequest $request, UsuarioAdministracionService $servicio): JsonResponse|RedirectResponse
    {
        $usuario = $servicio->crearAdministrador($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['mensaje' => 'Administrador creado. Se envió una invitación para establecer la clave.', 'usuario' => $usuario], 201);
        }

        return redirect('/')->with('estado', 'Administrador creado. Se envió una invitación para establecer la clave.');
    }

    public function cambiarEstado(CambiarEstadoUsuarioRequest $request, Usuario $usuario, UsuarioAdministracionService $servicio): JsonResponse|RedirectResponse
    {
        $usuario = $servicio->cambiarEstado($request->user(), $usuario, $request->boolean('activo'));

        if ($request->expectsJson()) {
            return response()->json(['mensaje' => 'Estado de usuario actualizado.', 'usuario' => $usuario]);
        }

        return redirect('/')->with('estado', 'Estado de usuario actualizado.');
    }
}
