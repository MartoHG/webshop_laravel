<?php

namespace App\Servicios\Imagenes;

use App\Jobs\ProcesarImagenContenido;
use App\Models\ImagenContenido;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class ImagenContenidoService
{
    /**
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function subir(UploadedFile $archivoSubido, string $tipo): ImagenContenido
    {
        if (! in_array($tipo, ImagenContenido::TIPOS, true)) {
            throw new InvalidArgumentException('El tipo de imagen de contenido no es válido.');
        }

        $discoTemporal = (string) config('imagenes.disco_temporal');
        $rutaTemporal = $archivoSubido->store((string) config('imagenes.contenido.directorio_temporal'), $discoTemporal);

        if ($rutaTemporal === false) {
            throw new RuntimeException('No se pudo guardar la imagen temporalmente.');
        }

        try {
            return DB::transaction(function () use ($tipo, $discoTemporal, $rutaTemporal): ImagenContenido {
                $imagen = ImagenContenido::query()->create([
                    'tipo' => $tipo,
                    'disco_temporal' => $discoTemporal,
                    'ruta_temporal' => $rutaTemporal,
                    'disco' => (string) config('imagenes.disco'),
                    'estado' => 'pendiente',
                ]);

                ProcesarImagenContenido::dispatch($imagen->id)->afterCommit();

                return $imagen;
            });
        } catch (Throwable $exception) {
            Storage::disk($discoTemporal)->delete($rutaTemporal);

            throw $exception;
        }
    }

    /**
     * Borra el registro y sus archivos. Los archivos se quitan recién cuando confirma la transacción externa, si la hay.
     */
    public function eliminar(ImagenContenido $imagen): void
    {
        DB::transaction(function () use ($imagen): void {
            $actual = ImagenContenido::query()->whereKey($imagen->getKey())->lockForUpdate()->first();

            if ($actual === null) {
                return;
            }

            $archivos = [
                'disco' => $actual->disco,
                'rutas' => array_values(array_filter([$actual->ruta, $actual->ruta_miniatura])),
                'disco_temporal' => $actual->disco_temporal,
                'ruta_temporal' => $actual->ruta_temporal,
            ];

            $actual->delete();

            DB::afterCommit(fn () => $this->borrarArchivos($archivos));
        });
    }

    /**
     * @throws ValidationException
     */
    public function reencolar(ImagenContenido $imagen): void
    {
        DB::transaction(function () use ($imagen): void {
            $actual = ImagenContenido::query()->whereKey($imagen->getKey())->lockForUpdate()->firstOrFail();

            if ($actual->estado !== 'fallido'
                || $actual->ruta_temporal === null
                || ! Storage::disk($actual->disco_temporal)->exists($actual->ruta_temporal)) {
                throw ValidationException::withMessages(['imagen' => 'La imagen no está disponible para reintentar.']);
            }

            $actual->forceFill(['estado' => 'pendiente', 'error' => null])->save();

            ProcesarImagenContenido::dispatch($actual->id)->afterCommit();
        });
    }

    /**
     * @param  array{disco: string, rutas: list<string>, disco_temporal: string, ruta_temporal: ?string}  $archivos
     */
    private function borrarArchivos(array $archivos): void
    {
        Storage::disk($archivos['disco'])->delete($archivos['rutas']);

        if ($archivos['ruta_temporal'] !== null) {
            Storage::disk($archivos['disco_temporal'])->delete($archivos['ruta_temporal']);
        }
    }
}
