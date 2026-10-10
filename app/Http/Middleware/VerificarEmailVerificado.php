<?php

namespace App\Http\Middleware;

use App\Models\Usuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarEmailVerificado
{
    public const string MENSAJE = 'Verificá tu email para confirmar el pedido.';

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario instanceof Usuario && $usuario->hasVerifiedEmail()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['mensaje' => self::MENSAJE], 403);
        }

        return redirect('/')->with('error', self::MENSAJE);
    }
}
