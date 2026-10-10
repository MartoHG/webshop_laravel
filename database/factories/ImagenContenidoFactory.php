<?php

namespace Database\Factories;

use App\Models\ImagenContenido;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImagenContenido>
 */
class ImagenContenidoFactory extends Factory
{
    protected $model = ImagenContenido::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tipo' => ImagenContenido::TIPO_PORTADA,
            'disco_temporal' => 'local',
            'ruta_temporal' => null,
            'disco' => 'public',
            'ruta' => null,
            'ruta_miniatura' => null,
            'estado' => 'pendiente',
            'error' => null,
        ];
    }

    public function listo(): static
    {
        return $this->state(function (array $atributos): array {
            $base = 'imagenes-contenido/'.fake()->unique()->uuid();

            return [
                'estado' => 'listo',
                'ruta' => $base.'/detalle.webp',
                'ruta_miniatura' => $base.'/miniatura.webp',
            ];
        });
    }

    public function fallido(): static
    {
        return $this->state(fn (array $atributos): array => [
            'estado' => 'fallido',
            'error' => 'El archivo no es una imagen JPEG, PNG o WebP válida.',
        ]);
    }
}
