<?php

namespace App\Servicios\Pedidos;

use App\Enums\EstadoEnvio;
use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Models\Pedido;
use App\Models\Usuario;
use App\Notifications\Pedidos\NoPudimosContactarte;
use App\Notifications\Pedidos\NotificacionPedido;
use App\Notifications\Pedidos\PedidoDespachado;
use App\Notifications\Pedidos\RecordatorioPedido;
use App\Servicios\Auditoria\AuditoriaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class EmailPedidoService
{
    private const string MENSAJE_YA_ENVIADO = 'Ya enviaste este email hace un momento.';

    private const int SEGUNDOS_ENTRE_EMAILS_IGUALES = 600;

    public function __construct(private AuditoriaService $auditoria) {}

    /**
     * Encola un email manual al email snapshot del pedido. Nunca se envía de forma automática.
     *
     * @param  'no-contactado'|'recordatorio'|'despachado'  $tipo
     *
     * @throws ValidationException
     */
    public function enviarManual(Usuario $administrador, Pedido $pedido, string $tipo, ?string $mensaje): void
    {
        $this->exigirPermitido($pedido, $tipo);

        if (RateLimiter::hit($this->claveLimite($pedido, $tipo), self::SEGUNDOS_ENTRE_EMAILS_IGUALES) > 1) {
            throw ValidationException::withMessages(['email' => self::MENSAJE_YA_ENVIADO]);
        }

        $mensaje = $this->normalizarMensaje($mensaje);

        DB::transaction(function () use ($administrador, $pedido, $tipo, $mensaje): void {
            $this->auditoria->registrar('pedido.email_enviado', $pedido, [], [
                'tipo' => $tipo,
                'mensaje' => $mensaje,
            ], $administrador);

            Notification::route('mail', $pedido->email_cliente)->notify($this->notificacion($pedido, $tipo, $mensaje));
        });
    }

    /**
     * @throws ValidationException
     */
    private function exigirPermitido(Pedido $pedido, string $tipo): void
    {
        if ($pedido->estado_pedido === EstadoPedido::Cancelado) {
            throw ValidationException::withMessages(['email' => 'El pedido está cancelado. No se envían emails.']);
        }

        $permitido = match ($tipo) {
            'no-contactado' => in_array($pedido->estado_pedido, [EstadoPedido::PendienteCoordinacion, EstadoPedido::Confirmado], true),
            'recordatorio' => $pedido->estado_pedido === EstadoPedido::PendienteCoordinacion
                && in_array($pedido->estado_pago, [EstadoPago::Deshabilitado, EstadoPago::Habilitado], true),
            'despachado' => $pedido->estado_envio === EstadoEnvio::Despachado,
            default => false,
        };

        if (! $permitido) {
            throw ValidationException::withMessages([
                'email' => 'Este email no corresponde al estado actual del pedido.',
            ]);
        }
    }

    private function notificacion(Pedido $pedido, string $tipo, ?string $mensaje): NotificacionPedido
    {
        return match ($tipo) {
            'no-contactado' => new NoPudimosContactarte($pedido, $mensaje),
            'recordatorio' => new RecordatorioPedido($pedido, $mensaje),
            'despachado' => new PedidoDespachado($pedido, $mensaje),
        };
    }

    private function claveLimite(Pedido $pedido, string $tipo): string
    {
        return "email-pedido:{$pedido->getKey()}:{$tipo}";
    }

    private function normalizarMensaje(?string $mensaje): ?string
    {
        $limpio = trim((string) $mensaje);

        return $limpio === '' ? null : $limpio;
    }
}
