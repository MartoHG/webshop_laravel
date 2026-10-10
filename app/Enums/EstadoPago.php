<?php

namespace App\Enums;

enum EstadoPago: string
{
    case Deshabilitado = 'deshabilitado';
    case Habilitado = 'habilitado';
    case Pendiente = 'pendiente';
    case Pagado = 'pagado';
    case Cancelado = 'cancelado';
}
