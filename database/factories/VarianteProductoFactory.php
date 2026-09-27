<?php

namespace Database\Factories;

use App\Models\Producto;
use App\Models\VarianteProducto;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VarianteProducto> */
class VarianteProductoFactory extends Factory
{
    protected $model = VarianteProducto::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'producto_id' => Producto::factory(),
            'color' => null,
            'etiqueta' => null,
            'sku' => fake()->unique()->bothify('SKU-########'),
            'precio_base' => fake()->randomFloat(2, 1, 100000),
            'activo' => false,
            'disponible' => false,
        ];
    }
}
