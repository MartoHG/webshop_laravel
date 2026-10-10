<?php

namespace App\Servicios\Categorias;

use App\Models\Categoria;
use App\Models\ImagenContenido;
use App\Servicios\Auditoria\AuditoriaService;
use App\Servicios\Imagenes\ImagenContenidoService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

class CategoriaImagenService
{
    public function __construct(
        private AuditoriaService $auditoria,
        private ImagenContenidoService $imagenes,
    ) {}

    public function cambiarImagen(Categoria $categoria, UploadedFile $archivo): ImagenContenido
    {
        $nueva = $this->imagenes->subir($archivo, ImagenContenido::TIPO_CATEGORIA);

        try {
            DB::transaction(function () use ($categoria, $nueva): void {
                $actual = Categoria::query()->whereKey($categoria->getKey())->lockForUpdate()->firstOrFail();
                $anteriorId = $actual->imagen_contenido_id;

                $actual->forceFill(['imagen_contenido_id' => $nueva->id])->save();
                $this->auditoria->registrarCambios(
                    'categoria.imagen_actualizada',
                    $actual,
                    ['imagen_contenido_id' => $anteriorId],
                    ['imagen_contenido_id' => $nueva->id],
                );

                $this->eliminarImagen($anteriorId);
            });
        } catch (Throwable $exception) {
            $this->imagenes->eliminar($nueva);

            throw $exception;
        }

        return $nueva;
    }

    public function quitarImagen(Categoria $categoria): void
    {
        DB::transaction(function () use ($categoria): void {
            $actual = Categoria::query()->whereKey($categoria->getKey())->lockForUpdate()->firstOrFail();
            $anteriorId = $actual->imagen_contenido_id;

            if ($anteriorId === null) {
                return;
            }

            $actual->forceFill(['imagen_contenido_id' => null])->save();
            $this->auditoria->registrarCambios(
                'categoria.imagen_actualizada',
                $actual,
                ['imagen_contenido_id' => $anteriorId],
                ['imagen_contenido_id' => null],
            );

            $this->eliminarImagen($anteriorId);
        });
    }

    public function marcarDestacada(Categoria $categoria, bool $destacada): void
    {
        DB::transaction(function () use ($categoria, $destacada): void {
            $actual = Categoria::query()->whereKey($categoria->getKey())->lockForUpdate()->firstOrFail();
            $anterior = $actual->destacada_en_home;

            $actual->forceFill(['destacada_en_home' => $destacada])->save();
            $this->auditoria->registrarCambios(
                'categoria.destacada_actualizada',
                $actual,
                ['destacada_en_home' => $anterior],
                ['destacada_en_home' => $destacada],
            );
        });
    }

    private function eliminarImagen(?int $imagenId): void
    {
        if ($imagenId === null) {
            return;
        }

        $imagen = ImagenContenido::query()->find($imagenId);

        if ($imagen !== null) {
            $this->imagenes->eliminar($imagen);
        }
    }
}
