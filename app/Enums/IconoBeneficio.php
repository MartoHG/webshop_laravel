<?php

namespace App\Enums;

enum IconoBeneficio: string
{
    case Envio = 'envio';
    case Pago = 'pago';
    case Local = 'local';
    case Whatsapp = 'whatsapp';
    case Cuotas = 'cuotas';
    case Calidad = 'calidad';
    case Garantia = 'garantia';
    case Horario = 'horario';
}
