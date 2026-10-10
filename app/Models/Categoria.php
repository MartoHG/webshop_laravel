<?php

namespace App\Models;

use Database\Factories\CategoriaFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Categoria extends Model
{
    /** @use HasFactory<CategoriaFactory> */
    use HasFactory;

    protected $table = 'categorias';

    /** @var list<string> */
    protected $fillable = ['nombre', 'slug', 'clave_sistema', 'activo', 'orden'];

    /** @var list<string> */
    protected $appends = ['imagen_url', 'imagen_miniatura_url'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['activo' => 'boolean', 'orden' => 'integer', 'destacada_en_home' => 'boolean'];
    }

    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Producto::class, 'categoria_producto', 'categoria_id', 'producto_id');
    }

    public function imagenContenido(): BelongsTo
    {
        return $this->belongsTo(ImagenContenido::class, 'imagen_contenido_id');
    }

    /** @return Attribute<?string, never> */
    protected function imagenUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->imagenContenido?->url());
    }

    /** @return Attribute<?string, never> */
    protected function imagenMiniaturaUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->imagenContenido?->urlMiniatura());
    }
}
