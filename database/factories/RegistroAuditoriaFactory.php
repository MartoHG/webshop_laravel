<?php

namespace Database\Factories;

use App\Models\RegistroAuditoria;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegistroAuditoria>
 */
class RegistroAuditoriaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_id' => Usuario::factory()->administrador(),
            'accion' => 'producto.estado_actualizado',
            'entidad' => 'productos',
            'entidad_id' => fake()->numberBetween(1, 10000),
            'datos_anteriores' => ['activo' => false],
            'datos_nuevos' => ['activo' => true],
            'ip' => fake()->ipv4(),
        ];
    }
}
