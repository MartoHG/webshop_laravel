<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administracion\Pagos\ResolverRevisionPagoRequest;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Usuario;
use App\Servicios\Pagos\PagoService;
use Illuminate\Http\JsonResponse;

class PagoController extends Controller
{
    public function index(Pedido $pedido, PagoService $servicio): JsonResponse
    {
        return response()->json(['pagos' => $servicio->listarIntentos($pedido)]);
    }

    public function resolverRevision(ResolverRevisionPagoRequest $request, Pedido $pedido, Pago $pago, PagoService $servicio): JsonResponse
    {
        /** @var Usuario $administrador */
        $administrador = $request->user();

        $resuelto = $servicio->resolverRevision(
            $administrador,
            $pedido,
            $pago,
            $request->validated('decision'),
            $request->validated('nota'),
        );

        return response()->json([
            'mensaje' => 'Revisión resuelta.',
            'pago' => $servicio->representarIntento($resuelto),
        ]);
    }
}
