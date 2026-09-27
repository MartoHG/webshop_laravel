<?php

namespace Database\Factories;

use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Producto> */
class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $nombre = fake()->unique()->words(3, true);

        return [
            'nombre' => $nombre,
            'slug' => Str::slug($nombre).'-'.fake()->unique()->numberBetween(1000, 999999),
            'descripcion' => fake()->optional()->paragraph(),
            'activo' => false,
            'disponible' => false,
        ];
    }
}
