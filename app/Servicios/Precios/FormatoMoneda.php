<?php

namespace App\Servicios\Precios;

class FormatoMoneda
{
    /**
     * Formatea un importe en pesos: "$245.500" cuando no tiene centavos y "$288.823,53" cuando sí.
     * Trabaja sobre cadenas con bcmath, sin pasar el importe por flotantes.
     */
    public static function formatear(string $importe): string
    {
        $normalizado = bcadd($importe, '0', 2);
        $negativo = str_starts_with($normalizado, '-');

        [$entero, $decimales] = explode('.', ltrim($normalizado, '-'), 2);

        $miles = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $entero);
        $numero = $decimales === '00' ? $miles : $miles.','.$decimales;

        return ($negativo ? '-' : '').'$'.$numero;
    }
}
