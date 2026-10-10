<?php

namespace App\Http\Controllers\Cuenta;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\Usuario;
use App\Servicios\Pedidos\PedidoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PedidoController extends Controller
{
    public function index(Request $request, PedidoService $servicio): JsonResponse
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();

        return response()->json([
            'pedidos' => $servicio->listarDelCliente($usuario)->through(
                fn (Pedido $pedido): array => $servicio->representarParaCliente($pedido),
            ),
        ]);
    }

    public function mostrar(Request $request, string $numero, PedidoService $servicio): JsonResponse
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();

        return response()->json([
            'pedido' => $servicio->representarParaCliente($servicio->mostrarDelCliente($usuario, $numero)),
        ]);
    }
}
