<?php

namespace App\Enums;

enum EstadoPedido: string
{
    case PendienteCoordinacion = 'pendiente_coordinacion';
    case Confirmado = 'confirmado';
    case Cancelado = 'cancelado';
}
