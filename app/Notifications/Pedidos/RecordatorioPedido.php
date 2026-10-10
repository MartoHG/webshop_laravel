<?php

namespace App\Notifications\Pedidos;

use App\Enums\EstadoPago;
use App\Models\Pedido;
use App\Servicios\Precios\FormatoMoneda;
use Illuminate\Notifications\Messages\MailMessage;

class RecordatorioPedido extends NotificacionPedido
{
    public function __construct(public Pedido $pedido, public ?string $mensaje = null) {}

    public function toMail(object $notifiable): MailMessage
    {
        $mail = $this->correo(
            'Tu pedido '.$this->pedido->numero.' sigue esperando',
            'Hola '.$this->pedido->nombre_cliente.',',
        );

        if ($this->pedido->estado_pago === EstadoPago::Habilitado) {
            $mail->line('Tu pedido '.$this->pedido->numero.' ya está listo para pagar. El total con envío es '.FormatoMoneda::formatear((string) $this->pedido->total_base).'.')
                ->line('Podés pagarlo desde el botón de abajo cuando quieras.');

            $this->agregarMensajeDelAdministrador($mail, $this->mensaje);

            return $mail->action('Pagar mi pedido', url('/pago/'.$this->pedido->token_pago));
        }

        $mail->line('Tu pedido '.$this->pedido->numero.' todavía está esperando que coordinemos el envío.')
            ->line('Si tenés alguna duda, respondé a este email.');

        $this->agregarMensajeDelAdministrador($mail, $this->mensaje);

        return $mail->action('Ver mi pedido', url('/cuenta/pedidos/'.$this->pedido->numero));
    }
}
