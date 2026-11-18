<?php

namespace App\Jobs;

use App\Models\Usuario;
use App\Notifications\Pedidos\AlertaAvisoMercadoPagoFallido;
use App\Servicios\Pagos\ErrorMercadoPago;
use App\Servicios\Pagos\MercadoPagoCliente;
use App\Servicios\Pagos\PagoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ProcesarNotificacionMercadoPago implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 60, 300, 900, 3600];

    public function __construct(public readonly string $idPago) {}

    /**
     * Un 4xx definitivo (por ejemplo, un pago inexistente) no se reintenta: la conciliación lo cubre si hace falta.
     *
     * @throws ErrorMercadoPago
     */
    public function handle(MercadoPagoCliente $cliente, PagoService $pagos): void
    {
        try {
            $pagoMp = $cliente->obtenerPago($this->idPago);
        } catch (ErrorMercadoPago $error) {
            if ($error->status !== null && $error->status >= 400 && $error->status < 500 && ! in_array($error->status, [408, 425, 429], true)) {
                Log::warning('Notificación de Mercado Pago descartada: la consulta fue rechazada.', [
                    'id_pago' => $this->idPago,
                    'status' => $error->status,
                ]);

                return;
            }

            throw $error;
        }

        $pagos->aplicarPagoMercadoPago($pagoMp);
    }

    /**
     * El registro no lleva el mensaje de la excepción: el de una QueryException trae el SQL y puede tener datos personales.
     */
    public function failed(?Throwable $excepcion): void
    {
        $contexto = [
            'id_pago_mercado_pago' => $this->idPago,
            'excepcion' => $excepcion !== null ? $excepcion::class : null,
        ];

        if ($excepcion instanceof ErrorMercadoPago) {
            $contexto['status'] = $excepcion->status;
        }

        Log::error('No se pudo procesar un aviso de Mercado Pago después de todos los reintentos.', $contexto);

        $administradores = Usuario::query()->administradoresActivos()->get();

        Notification::send($administradores, new AlertaAvisoMercadoPagoFallido($this->idPago));
    }
}
