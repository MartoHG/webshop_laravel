<?php

namespace App\Enums;

enum TipoMedioPago: string
{
    case Credito = 'credito';
    case Debito = 'debito';
    case DineroEnCuenta = 'dinero_en_cuenta';

    /**
     * Solo el crédito admite pagos en cuotas; el resto se cobra en un único pago.
     */
    public function admiteCuotas(): bool
    {
        return $this === self::Credito;
    }
}
