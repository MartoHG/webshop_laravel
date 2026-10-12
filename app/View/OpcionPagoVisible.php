<?php

namespace App\View;

use App\Enums\TipoMedioPago;
use App\Servicios\Precios\FormatoMoneda;

/**
 * Textos de cada modalidad de pago en el link de pago. La cuota se redondea hacia arriba al centavo, con bcmath,
 * para que la suma de las cuotas nunca quede por debajo del importe.
 */
class OpcionPagoVisible
{
    public static function etiqueta(string $tipo, int $cuotas): string
    {
        return match (TipoMedioPago::from($tipo)) {
            TipoMedioPago::Debito => 'Débito',
            TipoMedioPago::DineroEnCuenta => 'Dinero en Mercado Pago',
            TipoMedioPago::Credito => $cuotas > 1 ? "Crédito en {$cuotas} cuotas" : 'Crédito',
        };
    }

    /**
     * "1 pago" o "3 cuotas de $96.274,51". Nunca muestra el porcentaje de la regla.
     */
    public static function detalle(int $cuotas, string $importe): string
    {
        if ($cuotas <= 1) {
            return '1 pago';
        }

        return "{$cuotas} cuotas de ".FormatoMoneda::formatear(self::importeDeCuota($importe, $cuotas));
    }

    /**
     * Importe de cada cuota: el total dividido en partes, redondeado hacia arriba al centavo.
     */
    public static function importeDeCuota(string $importe, int $cuotas): string
    {
        $centavos = bcmul($importe, '100', 0);
        $cuotaEnCentavos = bcdiv(bcadd($centavos, (string) ($cuotas - 1), 0), (string) $cuotas, 0);

        return bcdiv($cuotaEnCentavos, '100', 2);
    }
}
