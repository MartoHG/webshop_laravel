<?php

namespace App\Servicios\Precios;

use App\Models\Configuracion;
use App\Servicios\Auditoria\AuditoriaService;
use Illuminate\Support\Facades\DB;

class AjusteEcommerceService
{
    private const string CLAVE_PORCENTAJE = 'ajuste_ecommerce_porcentaje';

    public function __construct(private AuditoriaService $auditoria) {}

    public function obtenerPorcentaje(): ?string
    {
        $valor = Configuracion::query()->where('clave', self::CLAVE_PORCENTAJE)->value('valor');

        return $valor === null ? null : (string) $valor;
    }

    public function guardarPorcentaje(string $porcentaje): string
    {
        $porcentaje = $this->normalizarPorcentaje($porcentaje);

        return DB::transaction(function () use ($porcentaje): string {
            $configuracion = Configuracion::query()
                ->lockForUpdate()
                ->firstOrNew(['clave' => self::CLAVE_PORCENTAJE]);
            $porcentajeAnterior = $configuracion->valor;

            $configuracion->valor = $porcentaje;
            $configuracion->save();

            $this->auditoria->registrarCambios(
                'configuracion.ajuste_ecommerce_actualizado',
                $configuracion,
                ['porcentaje' => $porcentajeAnterior],
                ['porcentaje' => $porcentaje],
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
