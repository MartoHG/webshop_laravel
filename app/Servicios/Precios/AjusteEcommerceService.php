<?php

namespace App\Servicios\Precios;

use App\Models\Configuracion;
use Illuminate\Support\Facades\DB;

class AjusteEcommerceService
{
    private const string CLAVE_PORCENTAJE = 'ajuste_ecommerce_porcentaje';

    public function obtenerPorcentaje(): ?string
    {
        $valor = Configuracion::query()->where('clave', self::CLAVE_PORCENTAJE)->value('valor');

        return $valor === null ? null : (string) $valor;
    }

    public function guardarPorcentaje(string $porcentaje): string
    {
        $porcentaje = $this->normalizarPorcentaje($porcentaje);

        return DB::transaction(function () use ($porcentaje): string {
            Configuracion::query()->updateOrCreate(
                ['clave' => self::CLAVE_PORCENTAJE],
                ['valor' => $porcentaje],
            );

            return $porcentaje;
        });
    }

    public function calcularPrecioPublicado(string $precioBase, ?string $porcentaje = null): ?string
    {
        $porcentaje ??= $this->obtenerPorcentaje();

        if ($porcentaje === null) {
            return null;
        }

        $fraccion = bcdiv($porcentaje, '100', 12);
        $ajuste = bcmul($precioBase, $fraccion, 12);
        $redondeado = bcround(bcadd($precioBase, $ajuste, 12), 2, \RoundingMode::HalfAwayFromZero);

        return bcadd($redondeado, '0', 2);
    }

    private function normalizarPorcentaje(string $porcentaje): string
    {
        $normalizado = bcadd($porcentaje, '0', 6);

        return rtrim(rtrim($normalizado, '0'), '.');
    }
}
