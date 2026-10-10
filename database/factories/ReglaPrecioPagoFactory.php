<?php

namespace Database\Factories;

use App\Enums\TipoMedioPago;
use App\Models\ReglaPrecioPago;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReglaPrecioPago>
 */
class ReglaPrecioPagoFactory extends Factory
{
    protected $model = ReglaPrecioPago::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tipo_medio_pago' => TipoMedioPago::Credito,
            'cuotas' => 1,
            'porcentaje' => '10',
            'activo' => false,
        ];
    }

    public function activa(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => true,
        ]);
    }
}
