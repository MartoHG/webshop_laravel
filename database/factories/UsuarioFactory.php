<?php

namespace Database\Factories;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Usuario>
 */
class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    protected static ?string $password = null;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->firstName(),
            'apellido' => fake()->lastName(),
            'dni' => fake()->unique()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
            'telefono' => fake()->numerify('+54 9 11 ####-####'),
            'password' => static::$password ??= Hash::make('password'),
            'rol' => 'cliente',
            'activo' => true,
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    public function administrador(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol' => 'administrador',
        ]);
    }

    public function noVerificado(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
