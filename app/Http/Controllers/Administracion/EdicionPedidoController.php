<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administracion\Pedidos\ActualizarItemPedidoRequest;
use App\Http\Requests\Administracion\Pedidos\AgregarItemPedidoRequest;
use App\Http\Requests\Administracion\Pedidos\QuitarItemPedidoRequest;
use App\Http\Requests\Administracion\Pedidos\ReemplazarPedidoRequest;
use App\Models\ItemPedido;
use App\Models\Pedido;
use App\Models\Usuario;
use App\Servicios\Pedidos\PedidoEdicionService;
use App\Servicios\Pedidos\PedidoReemplazoService;
use App\Servicios\Pedidos\PedidoService;
use Illuminate\Http\JsonResponse;

class EdicionPedidoController extends Controller
{
    public function agregarItem(AgregarItemPedidoRequest $request, Pedido $pedido, PedidoEdicionService $edicion, PedidoService $pedidos): JsonResponse
    {
        $pedidoEditado = $edicion->agregarItem(
            $pedido,
            (int) $request->validated('variante_producto_id'),
            (int) $request->validated('cantidad'),
        );

        return $this->respuesta('Producto agregado al pedido.', $pedidoEditado, $pedidos);
    }

    public function actualizarItem(ActualizarItemPedidoRequest $request, Pedido $pedido, ItemPedido $item, PedidoEdicionService $edicion, PedidoService $pedidos): JsonResponse
    {
        if ($request->has('cantidad')) {
            $pedidoEditado = $edicion->cambiarCantidad($pedido, $item, (int) $request->validated('cantidad'));

            return $this->respuesta('Cantidad actualizada.', $pedidoEditado, $pedidos);
        }

        $pedidoEditado = $edicion->cambiarVariante($pedido, $item, (int) $request->validated('variante_producto_id'));

        return $this->respuesta('Variante actualizada.', $pedidoEditado, $pedidos);
    }

    public function quitarItem(QuitarItemPedidoRequest $request, Pedido $pedido, ItemPedido $item, PedidoEdicionService $edicion, PedidoService $pedidos): JsonResponse
    {
        return $this->respuesta('Producto quitado del pedido.', $edicion->quitarItem($pedido, $item), $pedidos);
    }

    public function reemplazar(ReemplazarPedidoRequest $request, Pedido $pedido, PedidoReemplazoService $reemplazo, PedidoService $pedidos): JsonResponse
    {
        /** @var Usuario $administrador */
        $administrador = $request->user();

        $pedidoNuevo = $reemplazo->reemplazar($administrador, $pedido, $request->validated('nota'));

        return response()->json([
            'mensaje' => 'Pedido reemplazado. El pedido original quedó cancelado y el nuevo está pendiente de coordinación.',
            'pedido' => $pedidos->representarParaAdministracion($pedidoNuevo),
        ], 201);
    }

    private function respuesta(string $mensaje, Pedido $pedido, PedidoService $pedidos): JsonResponse
    {
        return response()->json([
            'mensaje' => $mensaje,
            'pedido' => $pedidos->representarParaAdministracion($pedido),
        ]);
    }
}
