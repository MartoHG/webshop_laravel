<?php

namespace App\Servicios\Pagos;

use RuntimeException;
use Throwable;

class ErrorMercadoPago extends RuntimeException
{
    /**
     * El status es null cuando no hubo respuesta HTTP válida (red, timeout o respuesta mal formada).
     */
    public function __construct(
        string $mensaje,
        public readonly ?int $status = null,
        ?Throwable $anterior = null,
    ) {
        parent::__construct($mensaje, 0, $anterior);
    }
}
