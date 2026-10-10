<?php

namespace App\Console\Commands;

use App\Enums\EstadoIntentoPago;
use App\Models\Pago;
use App\Servicios\Pagos\MercadoPagoCliente;
use App\Servicios\Pagos\PagoService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Signature('pagos:conciliar')]
#[Description('Consulta en Mercado Pago los pagos pendientes desde hace más de 10 minutos y aplica su resultado.')]
class ConciliarPagos extends Command
{
    private const int MINUTOS_PENDIENTE = 10;

    /**
     * Pasado este plazo, un intento sin id de Mercado Pago y sin operaciones con su referencia se cancela.
     */
    private const int MINUTOS_SIN_PAGO_EN_MERCADO_PAGO = 30;

    public function handle(MercadoPagoCliente $cliente, PagoService $pagos): int
    {
        $conciliados = 0;
        $cancelados = 0;
        $errores = 0;

        Pago::query()
            ->with('pedido')
            ->where('estado', EstadoIntentoPago::Pendiente->value)
            ->where('updated_at', '<', now()->subMinutes(self::MINUTOS_PENDIENTE))
            ->lazyById()
            ->each(function (Pago $pago) use ($cliente, $pagos, &$conciliados, &$cancelados, &$errores): void {
                try {
                    $pagoMp = $this->ultimoResultado($cliente, $pago);

                    if ($pagoMp !== null) {
                        $pagos->aplicarPagoMercadoPago($pagoMp);
                        $conciliados++;

                        return;
                    }

                    if ($this->sinPagoEnMercadoPagoHaceMuchoTiempo($pago) && $pagos->cancelarSinPagoEnMercadoPago($pago)) {
                        $cancelados++;
                    }
                } catch (Throwable $error) {
                    $errores++;

                    Log::error('Falló la conciliación de un pago pendiente.', [
                        'pago_id' => $pago->getKey(),
                        'error' => $error->getMessage(),
                    ]);
                }
            });

        $this->info("Pagos conciliados: {$conciliados}. Con error: {$errores}.");
        $this->info("Pagos cancelados por no tener pago en Mercado Pago: {$cancelados}.");

        return self::SUCCESS;
    }

    /**
     * @return ?array<string, mixed>
     */
    private function ultimoResultado(MercadoPagoCliente $cliente, Pago $pago): ?array
    {
        if ($pago->id_pago_mercado_pago !== null) {
            return $cliente->obtenerPago($pago->id_pago_mercado_pago);
        }

        $resultados = $cliente->buscarPagosPorReferencia("{$pago->pedido->numero}:{$pago->getKey()}");

        return $resultados[0] ?? null;
    }

    private function sinPagoEnMercadoPagoHaceMuchoTiempo(Pago $pago): bool
    {
        return $pago->id_pago_mercado_pago === null
            && $pago->updated_at !== null
            && $pago->updated_at->lte(now()->subMinutes(self::MINUTOS_SIN_PAGO_EN_MERCADO_PAGO));
    }
}
