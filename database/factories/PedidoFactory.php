<?php

namespace Database\Factories;

use App\Enums\EstadoContacto;
use App\Enums\EstadoEnvio;
use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Enums\MotivoCancelacion;
use App\Enums\Provincia;
use App\Models\Pedido;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Pedido>
 */
class PedidoFactory extends Factory
{
    protected $model = Pedido::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_id' => Usuario::factory(),
            'numero' => sprintf('ER-%s-%06d', now()->year, fake()->unique()->numberBetween(1, 999999)),
            'token_pago' => null,
            'pedido_origen_id' => null,
            'nombre_cliente' => fake()->firstName(),
            'apellido_cliente' => fake()->lastName(),
            'dni_cliente' => fake()->numerify('########'),
            'email_cliente' => fake()->safeEmail(),
            'telefono_cliente' => fake()->numerify('+54 9 11 ####-####'),
            'provincia_envio' => Provincia::BuenosAires,
            'localidad_envio' => fake()->city(),
            'codigo_postal_envio' => fake()->numerify('####'),
            'direccion_envio' => fake()->streetName(),
            'numero_envio' => fake()->buildingNumber(),
            'direccion_adicional' => null,
            'observaciones_envio' => null,
            'expreso' => null,
            'subtotal_productos' => number_format(fake()->randomFloat(2, 1000, 100000), 2, '.', ''),
            'costo_envio' => null,
            'total_base' => null,
            'estado_pedido' => EstadoPedido::PendienteCoordinacion,
            'estado_contacto' => EstadoContacto::Pendiente,
            'estado_envio' => EstadoEnvio::PendienteCoordinacion,
            'estado_pago' => EstadoPago::Deshabilitado,
        ];
    }

    public function pagado(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado_pedido' => EstadoPedido::Confirmado,
            'estado_pago' => EstadoPago::Pagado,
            'pagado_en' => now(),
        ]);
    }

    public function pagoPendiente(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado_pago' => EstadoPago::Pendiente,
        ]);
    }

    public function cancelado(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado_pedido' => EstadoPedido::Cancelado,
            'estado_pago' => EstadoPago::Cancelado,
            'cancelado_en' => now(),
            'cancelado_por' => Usuario::factory()->administrador(),
            'motivo_cancelacion' => MotivoCancelacion::ClienteDesistio,
        ]);
    }

    public function conTokenPago(): static
    {
        return $this->state(fn (array $attributes) => [
            'token_pago' => Str::random(64),
        ]);
    }
}
