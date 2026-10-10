<?php

namespace App\Models;

use Database\Factories\ItemPedidoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemPedido extends Model
{
    /** @use HasFactory<ItemPedidoFactory> */
    use HasFactory;

    protected $table = 'items_pedido';

    /** @var list<string> */
    protected $fillable = [
        'pedido_id',
        'producto_id',
        'variante_producto_id',
        'nombre_producto',
        'color',
        'etiqueta',
        'sku',
        'precio_unitario',
        'cantidad',
        'subtotal',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'precio_unitario' => 'decimal:2',
            'cantidad' => 'integer',
            'subtotal' => 'decimal:2',
        ];
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }
}
