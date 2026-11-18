<?php

namespace App\Models;

use App\Enums\EstadoIntentoPago;
use App\Enums\TipoMedioPago;
use Database\Factories\PagoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    /** @use HasFactory<PagoFactory> */
    use HasFactory;

    protected $table = 'pagos';

    /** @var list<string> */
    protected $fillable = [
        'pedido_id',
        'regla_precio_pago_id',
        'id_pago_mercado_pago',
        'id_orden_mercado_pago',
        'clave_idempotencia',
        'tipo_medio_pago',
        'cuotas',
        'importe_base',
        'porcentaje_aplicado',
        'importe_cobrado',
        'estado',
        'detalle_estado',
        'motivo_revision_resuelta',
        'medio_pago_mercado_pago',
        'pagado_en',
    ];

    /** @var list<string> */
    protected $hidden = [
        'clave_idempotencia',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo_medio_pago' => TipoMedioPago::class,
            'estado' => EstadoIntentoPago::class,
            'cuotas' => 'integer',
            'importe_base' => 'decimal:2',
            'porcentaje_aplicado' => 'decimal:6',
            'importe_cobrado' => 'decimal:2',
            'pagado_en' => 'datetime',
        ];
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    public function regla(): BelongsTo
    {
        return $this->belongsTo(ReglaPrecioPago::class, 'regla_precio_pago_id');
    }
}
