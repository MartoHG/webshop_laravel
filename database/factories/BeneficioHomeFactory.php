<?php

namespace Database\Factories;

use App\Enums\IconoBeneficio;
use App\Models\BeneficioHome;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BeneficioHome>
 */
class BeneficioHomeFactory extends Factory
{
    protected $model = BeneficioHome::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'icono' => fake()->randomElement(IconoBeneficio::cases()),
            'texto' => fake()->sentence(4),
            'orden' => 0,
            'activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn (array $atributos): array => ['activo' => false]);
    }
}
