<?php

namespace App\View;

use App\Enums\EstadoEnvio;
use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Enums\MotivoCancelacion;
use App\Models\Pedido;

/**
 * Traduce los estados internos de un pedido a lo que ve el cliente: etiqueta, descripción,
 * paso del avance (1 a 5) y color de la etiqueta.
 */
class EstadoVisiblePedido
{
    /** @var list<string> */
    public const array ETAPAS = ['Recibido', 'Envío acordado', 'Pagado', 'Despachado', 'Entregado'];

    /**
     * @return array{etiqueta: string, descripcion: string, paso: int, color: string}
     */
    public static function paraPedido(Pedido $pedido): array
    {
        if ($pedido->estado_pedido === EstadoPedido::Cancelado) {
            return self::estado('Cancelado', 'Este pedido ya no sigue adelante.', 1, 'grafito');
        }

        $envio = $pedido->estado_envio;

        if ($envio === EstadoEnvio::Entregado) {
            return self::estado('Entregado', '¡Esperamos que lo disfrutes!', 5, 'amarillo');
        }

        if ($envio === EstadoEnvio::Despachado) {
            $descripcion = $pedido->expreso !== null && $pedido->expreso !== ''
                ? 'Lo enviamos por '.$pedido->expreso.'.'
                : 'Ya lo despachamos.';

            return self::estado('Despachado', $descripcion, 4, 'amarillo');
        }

        if ($envio === EstadoEnvio::Preparado) {
            return self::estado('Preparando tu pedido', 'Lo estamos armando para despacharlo.', 3, 'celeste');
        }

        return match (true) {
            $pedido->estado_pago === EstadoPago::Pagado => self::estado('Pagado', 'Ya lo estamos preparando.', 3, 'celeste'),
            $pedido->estado_pago === EstadoPago::Pendiente => self::estado('Confirmando tu pago', 'Mercado Pago todavía está confirmando el pago. Puede demorar unos minutos.', 2, 'celeste'),
            $pedido->estado_pago === EstadoPago::Habilitado => self::estado('Listo para pagar', 'Ya podés pagarlo.', 2, 'naranja'),
            $envio === EstadoEnvio::Aceptado => self::estado('Envío acordado', 'Pronto te mandamos el link para pagar.', 2, 'celeste'),
            $envio === EstadoEnvio::Cotizado => self::estado('Envío cotizado', 'Te escribimos por WhatsApp con el costo.', 1, 'celeste'),
            default => self::estado('Recibido', 'Te vamos a escribir por WhatsApp para coordinar el envío.', 1, 'celeste'),
        };
    }

    /**
     * Motivo de cancelación en lenguaje llano. Es null cuando el pedido no está cancelado.
     */
    public static function motivoCancelacion(Pedido $pedido): ?string
    {
        if ($pedido->estado_pedido !== EstadoPedido::Cancelado) {
            return null;
        }

        return match ($pedido->motivo_cancelacion) {
            MotivoCancelacion::ClienteDesistio => 'Se canceló porque ya no seguiste con la compra.',
            MotivoCancelacion::NoRespondio => 'Se canceló porque no pudimos comunicarnos con vos por WhatsApp.',
            MotivoCancelacion::ProblemaConPago => 'Se canceló por un problema con el pago.',
            MotivoCancelacion::ProblemaLogistico => 'Se canceló por un problema con el envío.',
            MotivoCancelacion::Reemplazado => 'Lo reemplazamos por un pedido nuevo con los datos actualizados.',
            default => 'Se canceló. Si tenés dudas, escribinos por WhatsApp.',
        };
    }

    /**
     * @return array{etiqueta: string, descripcion: string, paso: int, color: string}
     */
    private static function estado(string $etiqueta, string $descripcion, int $paso, string $color): array
    {
        return [
            'etiqueta' => $etiqueta,
            'descripcion' => $descripcion,
            'paso' => $paso,
            'color' => $color,
        ];
    }
}
