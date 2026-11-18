<?php

namespace App\Console\Commands;

use App\Enums\EstadoIntentoPago;
use App\Enums\TipoMedioPago;
use App\Models\Pago;
use App\Servicios\Pagos\MercadoPagoCliente;
use App\Servicios\Pagos\PagoService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Signature('pagos:conciliar')]
#[Description('Consulta en Mercado Pago los pagos pendientes y las preferencias de dinero en cuenta desde hace más de 10 minutos.')]
class ConciliarPagos extends Command
{
    private const int MINUTOS_ANTES_DE_CONCILIAR = 10;

    /**
     * Pasado este plazo, un intento sin id de Mercado Pago y sin operaciones con su referencia se cancela.
     */
    private const int MINUTOS_SIN_PAGO_EN_MERCADO_PAGO = 30;

    private const int MINUTOS_MARGEN_PREFERENCIA = 30;

    public function handle(MercadoPagoCliente $cliente, PagoService $pagos): int
    {
        $conciliados = 0;
        $cancelados = 0;
        $errores = 0;
        $hasta = now()->subMinutes(self::MINUTOS_ANTES_DE_CONCILIAR);

        Pago::query()
            ->with('pedido')
            ->where(function (Builder $consulta) use ($hasta): void {
                $consulta->where(fn (Builder $pendientes): Builder => $pendientes
                    ->where('estado', EstadoIntentoPago::Pendiente->value)
                    ->where('updated_at', '<', $hasta))
                    ->orWhere(fn (Builder $preferencias): Builder => $preferencias
                        ->where('estado', EstadoIntentoPago::Creado->value)
                        ->where('tipo_medio_pago', TipoMedioPago::DineroEnCuenta->value)
                        ->whereNotNull('id_orden_mercado_pago')
                        ->where('created_at', '<', $hasta));
            })
            ->lazyById()
            ->each(function (Pago $pago) use ($cliente, $pagos, &$conciliados, &$cancelados, &$errores): void {
                try {
                    $this->conciliarIntento($cliente, $pagos, $pago, $conciliados, $cancelados);
                } catch (Throwable $error) {
                    $errores++;

                    Log::error('Falló la conciliación de un intento de pago.', [
                        'pago_id' => $pago->getKey(),
                        'error' => $error->getMessage(),
                    ]);
                }
            });

        $this->info("Pagos conciliados: {$conciliados}. Con error: {$errores}.");
        $this->info("Pagos cancelados por no tener pago en Mercado Pago: {$cancelados}.");

        return self::SUCCESS;
    }

    private function conciliarIntento(MercadoPagoCliente $cliente, PagoService $pagos, Pago $pago, int &$conciliados, int &$cancelados): void
    {
        $pagoMp = $this->ultimoResultado($cliente, $pago);

        if ($pagoMp !== null) {
            $pagos->aplicarPagoMercadoPago($pagoMp);
            $conciliados++;

            return;
        }

        $cancelado = $pago->estado === EstadoIntentoPago::Pendiente
            ? $this->sinPagoEnMercadoPagoHaceMuchoTiempo($pago) && $pagos->cancelarSinPagoEnMercadoPago($pago)
            : $this->preferenciaFueraDelMargen($pago) && $pagos->cancelarPreferenciaVencida($pago);

        if ($cancelado) {
            $cancelados++;
        }
    }

    /**
     * @return ?array<string, mixed>
     */
    private function ultimoResultado(MercadoPagoCliente $cliente, Pago $pago): ?array
    {
        if ($pago->estado === EstadoIntentoPago::Pendiente && $pago->id_pago_mercado_pago !== null) {
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

    private function preferenciaFueraDelMargen(Pago $pago): bool
    {
        $limite = now()->subMinutes(PagoService::MINUTOS_VIGENCIA_PREFERENCIA + self::MINUTOS_MARGEN_PREFERENCIA);

        return $pago->created_at !== null && $pago->created_at->lt($limite);
    }
}
