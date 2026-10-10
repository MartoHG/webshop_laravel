<?php

namespace App\Servicios\Precios;

use App\Enums\TipoMedioPago;
use App\Models\ReglaPrecioPago;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CompensacionPagoService
{
    private const int ESCALA_CALCULO = 12;

    /**
     * Devuelve una combinación de medio de pago y cuotas activa. Una combinación inexistente o inactiva
     * nunca se trata como 0%: se rechaza.
     *
     * @throws ValidationException
     */
    public function obtenerReglaActiva(TipoMedioPago $tipo, int $cuotas): ReglaPrecioPago
    {
        $regla = ReglaPrecioPago::query()
            ->where('tipo_medio_pago', $tipo)
            ->where('cuotas', $cuotas)
            ->first();

        if (! $regla instanceof ReglaPrecioPago || ! $regla->activo) {
            throw ValidationException::withMessages([
                'modalidad' => __('La modalidad de pago seleccionada no está disponible.'),
            ]);
        }

        return $regla;
    }

    /**
     * Importe a cobrar para que el negocio reciba el total base tras descontar el porcentaje.
     * Se redondea hacia arriba al centavo para que nunca reciba menos que el total base.
     *
     * @throws InvalidArgumentException
     */
    public function calcularImporte(string $totalBase, string $porcentaje): string
    {
        if (bccomp($totalBase, '0', self::ESCALA_CALCULO) <= 0) {
            throw new InvalidArgumentException('El total base debe ser mayor a cero.');
        }

        if (bccomp($porcentaje, '0', self::ESCALA_CALCULO) < 0 || bccomp($porcentaje, '100', self::ESCALA_CALCULO) >= 0) {
            throw new InvalidArgumentException('El porcentaje debe ser mayor o igual a 0 y menor a 100.');
        }

        $fraccionCobrada = bcsub('1', bcdiv($porcentaje, '100', self::ESCALA_CALCULO), self::ESCALA_CALCULO);
        $importe = bcdiv($totalBase, $fraccionCobrada, self::ESCALA_CALCULO);

        return bcadd(bcround($importe, 2, \RoundingMode::PositiveInfinity), '0', 2);
    }

    /**
     * @return list<array{regla_id: int, tipo_medio_pago: string, cuotas: int, porcentaje: string, importe: string}>
     */
    public function opcionesDePago(string $totalBase): array
    {
        return ReglaPrecioPago::query()
            ->where('activo', true)
            ->orderBy('tipo_medio_pago')
            ->orderBy('cuotas')
            ->get()
            ->map(fn (ReglaPrecioPago $regla): array => [
                'regla_id' => (int) $regla->id,
                'tipo_medio_pago' => $regla->tipo_medio_pago->value,
                'cuotas' => $regla->cuotas,
                'porcentaje' => $regla->porcentaje,
                'importe' => $this->calcularImporte($totalBase, $regla->porcentaje),
            ])
            ->values()
            ->all();
    }
}
