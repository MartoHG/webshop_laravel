<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImagenProducto extends Model
{
    protected $table = 'imagenes_producto';

    protected $fillable = [
        'producto_id',
        'variante_producto_id',
        'archivo_imagen_producto_id',
        'ruta',
        'ruta_miniatura',
        'estado',
        'orden',
        'es_principal',
    ];

    protected function casts(): array
    {
        return [
            'es_principal' => 'boolean',
            'orden' => 'integer',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function variante(): BelongsTo
    {
        return $this->belongsTo(VarianteProducto::class, 'variante_producto_id');
    }

    public function archivo(): BelongsTo
    {
        return $this->belongsTo(ArchivoImagenProducto::class, 'archivo_imagen_producto_id');
    }
}
