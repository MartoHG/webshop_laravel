<?php

namespace App\Notifications\Pedidos;

use App\Models\Pedido;
use Illuminate\Notifications\Messages\MailMessage;

class PedidoDespachado extends NotificacionPedido
{
    public function __construct(public Pedido $pedido, public ?string $mensaje = null) {}

    public function toMail(object $notifiable): MailMessage
    {
        $mail = $this->correo(
            'Despachamos tu pedido '.$this->pedido->numero,
            'Hola '.$this->pedido->nombre_cliente.',',
        )
            ->line('Despachamos tu pedido '.$this->pedido->numero.' por '.($this->pedido->expreso ?? 'un expreso').'.');

        $this->agregarMensajeDelAdministrador($mail, $this->mensaje);

        return $mail->action('Ver mi pedido', url('/cuenta/pedidos/'.$this->pedido->numero));
    }
}
