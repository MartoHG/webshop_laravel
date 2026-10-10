<?php

namespace App\Enums;

enum EstadoContacto: string
{
    case Pendiente = 'pendiente';
    case Contactado = 'contactado';
    case Respondio = 'respondio';
    case NoContestado = 'no_contestado';
}
