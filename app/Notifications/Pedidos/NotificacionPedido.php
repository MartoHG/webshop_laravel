<?php

namespace App\Notifications\Pedidos;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class NotificacionPedido extends Notification implements ShouldQueue
{
    public $afterCommit = true;

    public $tries = 5;

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [60, 300, 900, 3600];
    }

    protected function correo(string $asunto, string $saludo): MailMessage
    {
        return (new MailMessage)
            ->subject('El Refugio · '.$asunto)
            ->greeting($saludo)
            ->salutation('Saludos, El Refugio');
    }

    /**
     * Escapa la sintaxis de markdown para que el texto libre se muestre tal cual, sin formato ni enlaces.
     */
    protected function textoPlano(string $texto): string
    {
        return preg_replace('/([\\\\`*_\[\]()<>#!~|&])/', '\\\\$1', $texto) ?? $texto;
    }

    protected function agregarMensajeDelAdministrador(MailMessage $mail, ?string $mensaje): void
    {
        if ($mensaje !== null) {
            $mail->line($this->textoPlano($mensaje));
        }
    }
}
