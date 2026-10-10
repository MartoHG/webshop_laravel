<?php

namespace App\Enums;

enum OperacionPrecioMasiva: string
{
    case AumentarPorcentaje = 'aumentar_porcentaje';
    case DisminuirPorcentaje = 'disminuir_porcentaje';
    case FijarImporte = 'fijar_importe';

    /** Los porcentajes admiten hasta 4 decimales; el importe fijo, hasta 2 (es un precio). */
    public function decimalesPermitidos(): int
    {
        return $this === self::FijarImporte ? 2 : 4;
    }
}
