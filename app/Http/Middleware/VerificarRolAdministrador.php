<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarRolAdministrador
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->rol !== 'administrador') {
            if ($request->expectsJson()) {
                return response()->json(['mensaje' => 'No tienes autorización para esta acción.'], 403);
            }

            return redirect('/')->with('error', 'No tienes autorización para esta acción.');
        }

        return $next($request);
    }
}
