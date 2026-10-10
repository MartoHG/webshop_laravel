<?php

namespace App\Http\Controllers\Tienda;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tienda\IniciarIntentoPagoRequest;
use App\Http\Requests\Tienda\ProcesarPagoTarjetaRequest;
use App\Models\Pago;
use App\Servicios\Pagos\PagoService;
use Illuminate\Http\JsonResponse;

class PagoController extends Controller
{
    public function mostrar(string $token, PagoService $servicio): JsonResponse
    {
        return response()->json($servicio->resumen($servicio->pedidoPorToken($token)));
    }

    public function iniciarIntento(string $token, IniciarIntentoPagoRequest $request, PagoService $servicio): JsonResponse
    {
        $intento = $servicio->iniciarIntento(
            $servicio->pedidoPorToken($token),
            (int) $request->validated('regla_precio_pago_id'),
        );

        return response()->json([
            'mensaje' => 'Elegiste la modalidad de pago. Completá los datos para pagar.',
            'intento' => $intento,
        ], 201);
    }

    public function pagarConTarjeta(string $token, Pago $pago, ProcesarPagoTarjetaRequest $request, PagoService $servicio): JsonResponse
    {
        return response()->json(
            $servicio->procesarConTarjeta($servicio->pedidoPorToken($token), $pago, $request->validated()),
        );
    }

    public function estado(string $token, PagoService $servicio): JsonResponse
    {
        return response()->json($servicio->estado($servicio->pedidoDelToken($token)));
    }
}
