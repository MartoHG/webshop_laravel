<?php

namespace App\Enums;

enum MotivoCancelacion: string
{
    case ClienteDesistio = 'cliente_desistio';
    case NoRespondio = 'no_respondio';
    case ProblemaConPago = 'problema_con_pago';
    case ProblemaLogistico = 'problema_logistico';
    case Reemplazado = 'reemplazado';
    case Otro = 'otro';

    /**
     * El motivo reemplazado lo asigna el sistema al reemplazar un pedido; el administrador no lo elige.
     */
    public function elegibleManualmente(): bool
    {
        return $this !== self::Reemplazado;
    }
}
