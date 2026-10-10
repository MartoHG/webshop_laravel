<?php

namespace App\Http\Controllers\Administracion;

use App\Enums\EstadoContacto;
use App\Enums\EstadoEnvio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administracion\Pedidos\ActualizarContactoPedidoRequest;
use App\Http\Requests\Administracion\Pedidos\ActualizarDatosEnvioPedidoRequest;
use App\Http\Requests\Administracion\Pedidos\AvanzarEnvioPedidoRequest;
use App\Http\Requests\Administracion\Pedidos\CotizarEnvioPedidoRequest;
use App\Models\Pedido;
use App\Servicios\Pedidos\CoordinacionEnvioService;
use App\Servicios\Pedidos\PedidoService;
use App\Servicios\Pedidos\WhatsAppEnlaceService;
use Illuminate\Http\JsonResponse;

class CoordinacionPedidoController extends Controller
{
    public function actualizarContacto(ActualizarContactoPedidoRequest $request, Pedido $pedido, CoordinacionEnvioService $servicio, PedidoService $pedidos): JsonResponse
    {
        $pedidoActualizado = $servicio->registrarContacto(
            $pedido,
            EstadoContacto::from($request->validated('estado_contacto')),
        );

        return $this->respuesta('Contacto actualizado.', $pedidoActualizado, $pedidos);
    }

    public function cotizarEnvio(CotizarEnvioPedidoRequest $request, Pedido $pedido, CoordinacionEnvioService $servicio, PedidoService $pedidos): JsonResponse
    {
        $pedidoCotizado = $servicio->cotizarEnvio(
            $pedido,
            $request->validated('expreso'),
            $request->validated('costo_envio'),
        );

        return $this->respuesta('Envío cotizado.', $pedidoCotizado, $pedidos);
    }

    public function registrarAceptacion(Pedido $pedido, CoordinacionEnvioService $servicio, PedidoService $pedidos): JsonResponse
    {
        return $this->respuesta(
            'Aceptación del envío registrada.',
            $servicio->registrarAceptacion($pedido),
            $pedidos,
        );
    }

    public function habilitarPago(Pedido $pedido, CoordinacionEnvioService $servicio, PedidoService $pedidos): JsonResponse
    {
        return $this->respuesta(
            'Pago habilitado. Ya podés enviar el link de pago por WhatsApp.',
            $servicio->habilitarPago($pedido),
            $pedidos,
        );
    }

    public function recalcularImportes(Pedido $pedido, CoordinacionEnvioService $servicio, PedidoService $pedidos): JsonResponse
    {
        return $this->respuesta(
            'Importes recalculados con los precios actuales.',
            $servicio->recalcularImportes($pedido),
            $pedidos,
        );
    }

    public function avanzarEnvio(AvanzarEnvioPedidoRequest $request, Pedido $pedido, CoordinacionEnvioService $servicio, PedidoService $pedidos): JsonResponse
    {
        $pedidoActualizado = $servicio->avanzarEnvio(
            $pedido,
            EstadoEnvio::from($request->validated('estado_envio')),
        );

        return $this->respuesta('Envío actualizado.', $pedidoActualizado, $pedidos);
    }

    public function actualizarDatosEnvio(ActualizarDatosEnvioPedidoRequest $request, Pedido $pedido, CoordinacionEnvioService $servicio, PedidoService $pedidos): JsonResponse
    {
        $pedidoActualizado = $servicio->actualizarDatosEnvio($pedido, $request->validated());

        return $this->respuesta('Datos de envío actualizados.', $pedidoActualizado, $pedidos);
    }

    public function whatsapp(Pedido $pedido, string $tipo, WhatsAppEnlaceService $whatsapp): JsonResponse
    {
        $enlace = $tipo === 'link-pago'
            ? $whatsapp->enlacePago($pedido)
            : $whatsapp->enlaceCoordinarEnvio($pedido);

        return response()->json([
            'tipo' => $tipo,
            'whatsapp' => $enlace,
        ]);
    }

    private function respuesta(string $mensaje, Pedido $pedido, PedidoService $pedidos): JsonResponse
    {
        return response()->json([
            'mensaje' => $mensaje,
            'pedido' => $pedidos->representarParaAdministracion($pedido),
        ]);
    }
}
