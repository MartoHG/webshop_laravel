<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        Categoria::query()->firstOrCreate(
            ['clave_sistema' => 'ofertas'],
            ['nombre' => 'Ofertas', 'slug' => 'ofertas', 'activo' => true, 'orden' => 0],
        );
    }
}
