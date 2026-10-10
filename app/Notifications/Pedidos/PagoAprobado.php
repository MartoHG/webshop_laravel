<?php

namespace App\Notifications\Pedidos;

use App\Models\Pago;
use App\Models\Pedido;
use App\Servicios\Precios\FormatoMoneda;
use Illuminate\Notifications\Messages\MailMessage;

class PagoAprobado extends NotificacionPedido
{
    public function __construct(public Pedido $pedido, public Pago $pago) {}

    public function toMail(object $notifiable): MailMessage
    {
        return $this->correo(
            'Confirmamos tu pago del pedido '.$this->pedido->numero,
            'Hola '.$this->pedido->nombre_cliente.',',
        )
            ->line('¡Listo! Recibimos tu pago del pedido '.$this->pedido->numero.'.')
            ->line('Importe cobrado: '.FormatoMoneda::formatear((string) $this->pago->importe_cobrado).'.')
            ->line('Ya estamos preparando tu pedido. Te avisamos cuando lo despachemos.')
            ->action('Ver mi pedido', url('/cuenta/pedidos/'.$this->pedido->numero));
    }
}
