<?php

namespace App\Enums;

enum EstadoIntentoPago: string
{
    case Creado = 'creado';
    case Pendiente = 'pendiente';
    case Pagado = 'pagado';
    case Rechazado = 'rechazado';
    case Cancelado = 'cancelado';
    case RequiereRevision = 'requiere_revision';

    /**
     * Estados desde los que todavía puede cambiar el resultado de un intento.
     *
     * @return list<self>
     */
    public static function abiertos(): array
    {
        return [self::Creado, self::Pendiente];
    }
}
