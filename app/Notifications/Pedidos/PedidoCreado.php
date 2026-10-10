<?php

namespace App\Notifications\Pedidos;

use App\Models\Pedido;
use Illuminate\Notifications\Messages\MailMessage;

class PedidoCreado extends NotificacionPedido
{
    public function __construct(public Pedido $pedido) {}

    public function toMail(object $notifiable): MailMessage
    {
        $mail = $this->correo(
            'Recibimos tu pedido '.$this->pedido->numero,
            'Hola '.$this->pedido->nombre_cliente.',',
        )
            ->line('¡Gracias por tu compra! Recibimos tu pedido '.$this->pedido->numero.'.')
            ->line('Esto es lo que pediste:');

        foreach ($this->pedido->items as $item) {
            $detalle = $item->cantidad.' × '.$item->nombre_producto;

            $mail->line(filled($item->color) ? $detalle.', color '.$item->color : $detalle);
        }

        return $mail
            ->line('Subtotal de productos: '.$this->importe((string) $this->pedido->subtotal_productos).'.')
            ->line('El envío se cotiza aparte: te escribimos por WhatsApp al '.$this->pedido->telefono_cliente.' para acordarlo. Todavía no pagaste nada.')
            ->action('Ver mi pedido', url('/cuenta/pedidos/'.$this->pedido->numero));
    }
}
