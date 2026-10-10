<?php

namespace App\Notifications\Pedidos;

use App\Models\Pedido;
use Illuminate\Notifications\Messages\MailMessage;

class NoPudimosContactarte extends NotificacionPedido
{
    public function __construct(public Pedido $pedido, public ?string $mensaje = null) {}

    public function toMail(object $notifiable): MailMessage
    {
        $mail = $this->correo(
            'No pudimos contactarte por tu pedido '.$this->pedido->numero,
            'Hola '.$this->pedido->nombre_cliente.',',
        )
            ->line('Intentamos escribirte por WhatsApp al '.$this->pedido->telefono_cliente.' para coordinar el envío de tu pedido '.$this->pedido->numero.', pero no pudimos comunicarnos.')
            ->line('Respondé a este email o revisá que el número de WhatsApp sea el correcto, así podemos seguir con tu pedido.');

        $this->agregarMensajeDelAdministrador($mail, $this->mensaje);

        return $mail->action('Ver mi pedido', url('/cuenta/pedidos/'.$this->pedido->numero));
    }
}
