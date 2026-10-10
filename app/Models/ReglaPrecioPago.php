<?php

namespace App\Models;

use App\Enums\TipoMedioPago;
use Database\Factories\ReglaPrecioPagoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReglaPrecioPago extends Model
{
    /** @use HasFactory<ReglaPrecioPagoFactory> */
    use HasFactory;

    protected $table = 'reglas_precio_pago';

    /** @var list<string> */
    protected $fillable = ['tipo_medio_pago', 'cuotas', 'porcentaje', 'activo'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo_medio_pago' => TipoMedioPago::class,
            'cuotas' => 'integer',
            'porcentaje' => 'decimal:6',
            'activo' => 'boolean',
        ];
    }
}
