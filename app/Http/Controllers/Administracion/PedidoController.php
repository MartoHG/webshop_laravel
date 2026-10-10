<?php

namespace App\Http\Controllers\Administracion;

use App\Enums\MotivoCancelacion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administracion\Pedidos\CancelarPedidoRequest;
use App\Http\Requests\Administracion\Pedidos\ListarPedidosRequest;
use App\Models\Pedido;
use App\Models\Usuario;
use App\Servicios\Pedidos\PedidoService;
use Illuminate\Http\JsonResponse;

class PedidoController extends Controller
{
    public function index(ListarPedidosRequest $request, PedidoService $servicio): JsonResponse
    {
        return response()->json([
            'pedidos' => $servicio->listarParaAdministracion($request->validated())->through(
                fn (Pedido $pedido): array => $servicio->representarParaAdministracion($pedido),
            ),
        ]);
    }

    public function mostrar(Pedido $pedido, PedidoService $servicio): JsonResponse
    {
        return response()->json([
            'pedido' => $servicio->representarParaAdministracion($servicio->mostrarParaAdministracion($pedido)),
        ]);
    }

    public function cancelar(CancelarPedidoRequest $request, Pedido $pedido, PedidoService $servicio): JsonResponse
    {
        /** @var Usuario $administrador */
        $administrador = $request->user();
        $datos = $request->validated();

        $pedidoCancelado = $servicio->cancelar(
            $administrador,
            $pedido,
            MotivoCancelacion::from($datos['motivo']),
            $datos['detalle'] ?? null,
        );

        return response()->json([
            'mensaje' => 'Pedido cancelado.',
            'pedido' => $servicio->representarParaAdministracion($pedidoCancelado),
        ]);
    }
}
