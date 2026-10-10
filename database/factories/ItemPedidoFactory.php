<?php

namespace Database\Factories;

use App\Models\ItemPedido;
use App\Models\Pedido;
use App\Models\VarianteProducto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemPedido>
 */
class ItemPedidoFactory extends Factory
{
    protected $model = ItemPedido::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $precioUnitario = number_format(fake()->randomFloat(2, 100, 50000), 2, '.', '');
        $cantidad = fake()->numberBetween(1, 5);

        return [
            'pedido_id' => Pedido::factory(),
            'variante_producto_id' => VarianteProducto::factory(),
            'producto_id' => fn (array $atributos): int => VarianteProducto::query()
                ->findOrFail($atributos['variante_producto_id'])
                ->producto_id,
            'nombre_producto' => fake()->words(3, true),
            'color' => fake()->optional()->colorName(),
            'etiqueta' => fake()->optional()->word(),
            'sku' => fake()->unique()->bothify('SKU-########'),
            'precio_unitario' => $precioUnitario,
            'cantidad' => $cantidad,
            'subtotal' => bcmul($precioUnitario, (string) $cantidad, 2),
        ];
    }
}
