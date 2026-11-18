<?php

namespace App\Notifications\Pedidos;

use App\Models\Pago;
use App\Models\Pedido;
use Illuminate\Notifications\Messages\MailMessage;

class AlertaPagoEnRevision extends NotificacionPedido
{
    public function __construct(public Pedido $pedido, public Pago $pago) {}

    public function toMail(object $notifiable): MailMessage
    {
        return $this->correo('Pago en revisión del pedido '.$this->pedido->numero, 'Hola,')
            ->line('Un pago del pedido '.$this->pedido->numero.' quedó en revisión. El pedido todavía no cambió de estado.')
            ->line('Pago número '.$this->pago->id.'. Motivo: '.$this->motivo().'.')
            ->line('Resolvelo desde el panel de administración: podés aceptarlo o descartarlo, dejando una nota.');
    }

    private function motivo(): string
    {
        return match ($this->pago->detalle_estado) {
            'pedido_cancelado' => 'el pedido está cancelado',
            'pedido_ya_pagado' => 'el pedido ya tiene otro pago aprobado',
            'pedido_no_pagable' => 'el pedido no admite pagos en este momento',
            'moneda_distinta' => 'la moneda no coincide con el pedido',
            'importe_distinto' => 'el importe no coincide con el del intento',
            'cuotas_distintas' => 'la cantidad de cuotas no coincide con el intento',
            'medio_distinto' => 'el medio de pago no coincide con el del intento',
            'id_distinto' => 'Mercado Pago informó otra operación para este intento',
            'contracargo' => 'Mercado Pago informó un contracargo',
            'reembolsado' => 'el pago fue reembolsado',
            'en_mediacion' => 'el comprador abrió un reclamo en Mercado Pago',
            'reembolso_parcial' => 'se devolvió una parte del pago',
            default => $this->pago->detalle_estado ?? 'sin detalle',
        };
    }
}
