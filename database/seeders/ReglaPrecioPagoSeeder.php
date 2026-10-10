<?php

namespace Database\Seeders;

use App\Enums\TipoMedioPago;
use App\Models\ReglaPrecioPago;
use Illuminate\Database\Seeder;

class ReglaPrecioPagoSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->reglasIniciales() as [$tipo, $cuotas]) {
            ReglaPrecioPago::query()->firstOrCreate(
                ['tipo_medio_pago' => $tipo, 'cuotas' => $cuotas],
                ['porcentaje' => '0', 'activo' => false],
            );
        }
    }

    /**
     * Las reglas iniciales se crean inactivas: el dueño debe cargar los porcentajes antes de la primera venta.
     *
     * @return list<array{0: TipoMedioPago, 1: int}>
     */
    private function reglasIniciales(): array
    {
        return [
            [TipoMedioPago::Credito, 1],
            [TipoMedioPago::Credito, 3],
            [TipoMedioPago::Credito, 6],
            [TipoMedioPago::Credito, 12],
            [TipoMedioPago::Debito, 1],
            [TipoMedioPago::DineroEnCuenta, 1],
        ];
    }
}
