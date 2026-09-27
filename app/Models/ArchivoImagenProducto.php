<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArchivoImagenProducto extends Model
{
    protected $table = 'archivos_imagenes_producto';

    protected $fillable = [
        'disco_temporal',
        'ruta_temporal',
        'disco',
        'ruta',
        'ruta_miniatura',
        'estado',
        'sha256',
        'error',
    ];

    public function imagenes(): HasMany
    {
        return $this->hasMany(ImagenProducto::class, 'archivo_imagen_producto_id');
    }
}
