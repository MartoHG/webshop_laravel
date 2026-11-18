<?php

namespace Database\Factories;

use App\Enums\EstadoIntentoPago;
use App\Enums\TipoMedioPago;
use App\Models\Pago;
use App\Models\Pedido;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Pago>
 */
class PagoFactory extends Factory
{
    protected $model = Pago::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pedido_id' => Pedido::factory(),
            'regla_precio_pago_id' => null,
            'id_pago_mercado_pago' => null,
            'id_orden_mercado_pago' => null,
            'clave_idempotencia' => (string) Str::uuid(),
            'tipo_medio_pago' => TipoMedioPago::Credito,
            'cuotas' => 1,
            'importe_base' => '1000.00',
            'porcentaje_aplicado' => '10.000000',
            'importe_cobrado' => '1111.12',
            'estado' => EstadoIntentoPago::Creado,
            'detalle_estado' => null,
            'motivo_revision_resuelta' => null,
            'medio_pago_mercado_pago' => null,
            'pagado_en' => null,
        ];
    }

    public function pagado(): static
    {
        return $this->state(fn (array $atributos) => [
            'estado' => EstadoIntentoPago::Pagado,
            'id_pago_mercado_pago' => (string) fake()->unique()->numberBetween(100000000, 999999999),
            'pagado_en' => now(),
        ]);
    }
}
