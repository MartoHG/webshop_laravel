<?php

namespace App\Jobs;

use App\Servicios\Pagos\ErrorMercadoPago;
use App\Servicios\Pagos\MercadoPagoCliente;
use App\Servicios\Pagos\PagoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
}
