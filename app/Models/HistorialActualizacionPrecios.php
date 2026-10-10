<?php

namespace App\Models;

use App\Enums\AlcancePrecioMasivo;
use App\Enums\OperacionPrecioMasiva;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class HistorialActualizacionPrecios extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'historial_actualizaciones_precios';

    /** @var list<string> */
    protected $fillable = [
        'usuario_id',
        'operacion',
        'valor',
        'alcance',
        'filtro',
        'cantidad_afectados',
        'detalle',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'operacion' => OperacionPrecioMasiva::class,
            'alcance' => AlcancePrecioMasivo::class,
            'valor' => 'decimal:6',
            'filtro' => 'array',
            'cantidad_afectados' => 'integer',
            'detalle' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('El historial de actualizaciones de precios no se puede modificar.');
        });

        static::deleting(function (): void {
            throw new LogicException('El historial de actualizaciones de precios no se puede eliminar.');
        });
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
