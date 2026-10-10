<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'webhooks/mercadopago',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (AuthenticationException $excepcion, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Iniciá sesión para continuar.'], 401);
            }
        });

        $exceptions->render(function (AccessDeniedHttpException $excepcion, Request $request) {
            if ($request->expectsJson() && in_array($excepcion->getMessage(), ['', 'This action is unauthorized.'], true)) {
                return response()->json(['message' => 'No tenés permiso para hacer esto.'], 403);
            }
        });
    })->create();
