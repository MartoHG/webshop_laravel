<?php

namespace App\Models;

use Database\Factories\ImagenContenidoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ImagenContenido extends Model
{
    /** @use HasFactory<ImagenContenidoFactory> */
    use HasFactory;

    public const string TIPO_PORTADA = 'portada';

    public const string TIPO_CATEGORIA = 'categoria';

    /** @var list<string> */
    public const array TIPOS = [self::TIPO_PORTADA, self::TIPO_CATEGORIA];

    protected $table = 'imagenes_contenido';

    /** @var list<string> */
    protected $fillable = [
        'tipo',
        'disco_temporal',
        'ruta_temporal',
        'disco',
        'ruta',
        'ruta_miniatura',
        'estado',
        'error',
    ];

    public function url(): ?string
    {
        return $this->urlDe($this->ruta);
    }

    public function urlMiniatura(): ?string
    {
        return $this->urlDe($this->ruta_miniatura);
    }

    private function urlDe(?string $ruta): ?string
    {
        if ($this->estado !== 'listo' || $ruta === null) {
            return null;
        }

        return Storage::disk($this->disco)->url($ruta);
    }
}
