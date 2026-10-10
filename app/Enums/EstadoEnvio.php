<?php

namespace App\Enums;

enum EstadoEnvio: string
{
    case PendienteCoordinacion = 'pendiente_coordinacion';
    case Cotizado = 'cotizado';
    case Aceptado = 'aceptado';
    case Preparado = 'preparado';
    case Despachado = 'despachado';
    case Entregado = 'entregado';
}
