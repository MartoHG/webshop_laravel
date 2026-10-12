<?php

namespace App\Http\Controllers\Cuenta;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\Usuario;
use App\Servicios\Contenido\ContactoService;
use App\Servicios\Pedidos\PedidoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PedidoController extends Controller
{
    public function index(Request $request, PedidoService $servicio): JsonResponse|View
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();
        $pedidos = $servicio->listarDelCliente($usuario);

        if ($request->expectsJson()) {
            return response()->json([
                'pedidos' => $pedidos->through(
                    fn (Pedido $pedido): array => $servicio->representarParaCliente($pedido),
                ),
            ]);
        }

        return view('tienda.cuenta-pedidos', ['pedidos' => $pedidos]);
    }

    public function mostrar(Request $request, string $numero, PedidoService $servicio, ContactoService $contacto): JsonResponse|View
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();
        $pedido = $servicio->mostrarDelCliente($usuario, $numero);

        if ($request->expectsJson()) {
            return response()->json([
                'pedido' => $servicio->representarParaCliente($pedido),
            ]);
        }

        return view('tienda.cuenta-pedido', [
            'pedido' => $pedido,
            'whatsappUrl' => $contacto->publico()['whatsapp_url'],
        ]);
    }
}
