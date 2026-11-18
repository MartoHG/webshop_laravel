<?php

namespace App\Notifications\Pedidos;

use Illuminate\Notifications\Messages\MailMessage;

class AlertaAvisoMercadoPagoFallido extends NotificacionPedido
{
    public function __construct(public string $idPagoMercadoPago) {}

    public function toMail(object $notifiable): MailMessage
    {
        return $this->correo('Aviso de Mercado Pago sin procesar', 'Hola,')
            ->line('No pudimos procesar un aviso de Mercado Pago del pago número '.$this->textoPlano($this->idPagoMercadoPago).' después de varios intentos.')
            ->line('Revisá el pedido en el panel de administración y el pago en tu cuenta de Mercado Pago.');
    }
}
