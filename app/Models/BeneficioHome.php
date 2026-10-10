<?php

namespace App\Models;

use App\Enums\IconoBeneficio;
use Database\Factories\BeneficioHomeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BeneficioHome extends Model
{
    /** @use HasFactory<BeneficioHomeFactory> */
    use HasFactory;

    protected $table = 'beneficios_home';

    /** @var list<string> */
    protected $fillable = ['icono', 'texto', 'orden', 'activo'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['icono' => IconoBeneficio::class, 'orden' => 'integer', 'activo' => 'boolean'];
    }
}
