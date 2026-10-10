<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administracion\Pedidos\EnviarEmailPedidoRequest;
use App\Models\Pedido;
use App\Models\Usuario;
use App\Servicios\Pedidos\EmailPedidoService;
use Illuminate\Http\JsonResponse;

class EmailPedidoController extends Controller
{
    public function enviar(EnviarEmailPedidoRequest $request, Pedido $pedido, string $tipo, EmailPedidoService $servicio): JsonResponse
    {
        /** @var Usuario $administrador */
        $administrador = $request->user();

        $servicio->enviarManual($administrador, $pedido, $tipo, $request->validated('mensaje'));

        return response()->json(['mensaje' => 'El email quedó en cola para enviarse.'], 202);
    }
}
