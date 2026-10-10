<?php

namespace App\Models;

use Database\Factories\VarianteProductoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VarianteProducto extends Model
{
    /** @use HasFactory<VarianteProductoFactory> */
    use HasFactory;

    protected $table = 'variantes_producto';

    /** @var list<string> */
    protected $fillable = ['color', 'etiqueta', 'sku', 'precio_base'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['precio_base' => 'decimal:2', 'activo' => 'boolean', 'disponible' => 'boolean'];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function imagenes(): HasMany
    {
        return $this->hasMany(ImagenProducto::class);
    }

    /**
     * Regla de venta compartida por el carrito y la edición de pedidos. Null cuando la variante se puede vender.
     */
    public function motivoNoDisponible(): ?string
    {
        if (! $this->activo || ! $this->producto->activo) {
            return 'no_disponible';
        }

        if (! $this->disponible || ! $this->producto->disponible) {
            return 'agotado';
        }

        return null;
    }
}
