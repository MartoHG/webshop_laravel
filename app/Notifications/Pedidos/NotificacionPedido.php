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

    /**
     * Formatea un importe como "$1.250,00" sin pasar el valor por flotantes.
     */
    protected function importe(string $importe): string
    {
        [$entero, $decimales] = array_pad(explode('.', $importe, 2), 2, '00');

        return '$'.preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $entero).','.str_pad($decimales, 2, '0');
    }

    protected function agregarMensajeDelAdministrador(MailMessage $mail, ?string $mensaje): void
    {
        if ($mensaje !== null) {
            $mail->line($this->textoPlano($mensaje));
        }
    }
}
