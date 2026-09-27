<?php

namespace App\Servicios\Imagenes;

use App\Jobs\ProcesarImagenProducto;
use App\Models\ImagenProducto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ImagenProductoService
{
    /**
     * @param  array{producto_id: int, variante_producto_id?: int|null, orden?: int, es_principal?: bool}  $datos
     * @return array{imagen: ImagenProducto, archivo_imagen_producto_id: int}
     */
    public function subir(UploadedFile $archivoSubido, array $datos): array
    {
        $discoTemporal = (string) config('imagenes.disco_temporal');
        $rutaTemporal = $archivoSubido->store((string) config('imagenes.directorio_temporal'), $discoTemporal);

        if ($rutaTemporal === false) {
            throw new RuntimeException('No se pudo guardar la imagen temporalmente.');
        }

        try {
            return DB::transaction(function () use ($datos, $discoTemporal, $rutaTemporal): array {
                $this->validarVariante($datos['producto_id'], $datos['variante_producto_id'] ?? null);

                $archivoId = DB::table('archivos_imagenes_producto')->insertGetId([
                    'disco_temporal' => $discoTemporal,
                    'ruta_temporal' => $rutaTemporal,
                    'disco' => (string) config('imagenes.disco'),
                    'estado' => 'pendiente',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $imagen = $this->crearAsignacion($archivoId, $datos);
                ProcesarImagenProducto::dispatch($archivoId)->afterCommit();

                return ['imagen' => $imagen, 'archivo_imagen_producto_id' => $archivoId];
            });
        } catch (\Throwable $exception) {
            Storage::disk($discoTemporal)->delete($rutaTemporal);
            throw $exception;
        }
    }

    /**
     * @param  array{archivo_imagen_producto_id: int, producto_id: int, variante_producto_id?: int|null, orden?: int, es_principal?: bool}  $datos
     */
    public function asignar(array $datos): ImagenProducto
    {
        return DB::transaction(function () use ($datos): ImagenProducto {
            $this->validarVariante($datos['producto_id'], $datos['variante_producto_id'] ?? null);
            DB::table('productos')->where('id', $datos['producto_id'])->lockForUpdate()->first();
            $archivo = DB::table('archivos_imagenes_producto')
                ->where('id', $datos['archivo_imagen_producto_id'])
                ->lockForUpdate()
                ->first();

            if ($archivo === null || $archivo->estado !== 'listo') {
                throw ValidationException::withMessages(['archivo_imagen_producto_id' => 'El archivo todavía no está procesado o falló su procesamiento.']);
            }

            return $this->crearAsignacion((int) $archivo->id, $datos, $archivo);
        });
    }

    public function reintentar(int $archivoId): void
    {
        DB::transaction(function () use ($archivoId): void {
            $archivo = DB::table('archivos_imagenes_producto')->where('id', $archivoId)->lockForUpdate()->first();

            if ($archivo === null || $archivo->estado !== 'fallido' || ! Storage::disk($archivo->disco_temporal)->exists($archivo->ruta_temporal)) {
                throw ValidationException::withMessages(['archivo' => 'El archivo no está disponible para reintentar.']);
            }

            DB::table('archivos_imagenes_producto')->where('id', $archivoId)->update([
                'estado' => 'pendiente',
                'error' => null,
                'updated_at' => now(),
            ]);

            ProcesarImagenProducto::dispatch($archivoId)->afterCommit();
        });
    }

    /** @param  list<array{id: int, orden: int}>  $imagenes */
    public function reordenar(int $productoId, ?int $varianteId, array $imagenes): void
    {
        DB::transaction(function () use ($productoId, $varianteId, $imagenes): void {
            DB::table('productos')->where('id', $productoId)->lockForUpdate()->first();
            $ids = collect($imagenes)->pluck('id')->all();
            $scope = ImagenProducto::query()->where('producto_id', $productoId)->where('variante_producto_id', $varianteId);
            $encontradas = (clone $scope)->whereIn('id', $ids)->lockForUpdate()->count();

            if ($encontradas !== count($ids)) {
                throw ValidationException::withMessages(['imagenes' => 'Todas las imágenes deben pertenecer al producto y ámbito indicados.']);
            }

            foreach ($imagenes as $imagen) {
                (clone $scope)->whereKey($imagen['id'])->update(['orden' => $imagen['orden'], 'updated_at' => now()]);
            }
        });
    }

    public function establecerPrincipal(ImagenProducto $imagen): ImagenProducto
    {
        return DB::transaction(function () use ($imagen): ImagenProducto {
            $actual = ImagenProducto::query()->whereKey($imagen->id)->firstOrFail();
            $producto = DB::table('productos')->where('id', $actual->producto_id)->lockForUpdate()->first();
            $imagen = ImagenProducto::query()->whereKey($imagen->id)->lockForUpdate()->firstOrFail();

            if ($imagen->estado !== 'listo' && $this->requiereImagenLista($producto?->activo ?? false, $imagen->variante_producto_id)) {
                throw ValidationException::withMessages(['imagen' => 'Una imagen pendiente o fallida no puede ser principal en un ámbito activo.']);
            }

            $this->ambito($imagen->producto_id, $imagen->variante_producto_id)
                ->where('id', '!=', $imagen->id)
                ->update(['es_principal' => false, 'updated_at' => now()]);
            $imagen->forceFill(['es_principal' => true])->save();

            return $imagen->refresh();
        });
    }

    public function eliminar(ImagenProducto $imagen): void
    {
        $limpieza = DB::transaction(function () use ($imagen): ?array {
            $actual = ImagenProducto::query()->whereKey($imagen->id)->firstOrFail();
            $producto = DB::table('productos')->where('id', $actual->producto_id)->lockForUpdate()->first();
            $imagen = ImagenProducto::query()->whereKey($imagen->id)->lockForUpdate()->firstOrFail();

            if ($imagen->variante_producto_id === null && $imagen->es_principal && $producto?->activo) {
                throw ValidationException::withMessages(['imagen' => 'No se puede quitar la imagen general principal de un producto activo.']);
            }

            if ($imagen->variante_producto_id === null) {
                $variantesActivas = DB::table('variantes_producto')
                    ->where('producto_id', $imagen->producto_id)
                    ->where('activo', true)
                    ->lockForUpdate()
                    ->get(['id']);
                $imagenesGenerales = $this->ambito($imagen->producto_id, null)->count();

                if ($variantesActivas->count() === 1
                    && $this->ambito($imagen->producto_id, (int) $variantesActivas->first()->id)->doesntExist()
                    && $imagenesGenerales <= 1) {
                    throw ValidationException::withMessages(['imagen' => 'No se puede quitar la última imagen general de la única variante activa.']);
                }

                if ($imagen->es_principal && $variantesActivas->count() === 1
                    && $this->ambito($imagen->producto_id, (int) $variantesActivas->first()->id)->doesntExist()
                    && ! $this->ambito($imagen->producto_id, null)
                        ->where('id', '!=', $imagen->id)
                        ->where('estado', 'listo')
                        ->exists()) {
                    throw ValidationException::withMessages(['imagen' => 'No se puede quitar la imagen principal que utiliza la única variante activa sin una alternativa ya procesada.']);
                }
            }

            if ($imagen->variante_producto_id !== null) {
                $variante = DB::table('variantes_producto')->where('id', $imagen->variante_producto_id)->lockForUpdate()->first();
                $cantidad = $this->ambito($imagen->producto_id, $imagen->variante_producto_id)->count();

                if ($variante?->activo && $cantidad <= 1) {
                    throw ValidationException::withMessages(['imagen' => 'No se puede quitar la última imagen de una variante activa.']);
                }

                if ($variante?->activo && $imagen->es_principal
                    && ! $this->ambito($imagen->producto_id, $imagen->variante_producto_id)
                        ->where('id', '!=', $imagen->id)
                        ->where('estado', 'listo')
                        ->exists()) {
                    throw ValidationException::withMessages(['imagen' => 'No se puede quitar la imagen principal de una variante activa sin otra imagen procesada.']);
                }
            }

            $archivoId = $imagen->archivo_imagen_producto_id;
            $eraPrincipal = $imagen->es_principal;
            $productoId = $imagen->producto_id;
            $varianteId = $imagen->variante_producto_id;
            $imagen->delete();

            if ($eraPrincipal) {
                $siguientePrincipal = $this->ambito($productoId, $varianteId)
                    ->orderByRaw("CASE WHEN estado = 'listo' THEN 0 ELSE 1 END")
                    ->orderBy('orden')
                    ->orderBy('id')
                    ->first();

                if ($siguientePrincipal !== null) {
                    $siguientePrincipal->forceFill(['es_principal' => true])->save();
                }
            }

            if (! ImagenProducto::query()->where('archivo_imagen_producto_id', $archivoId)->exists()) {
                $archivo = DB::table('archivos_imagenes_producto')->where('id', $archivoId)->lockForUpdate()->first();

                if ($archivo !== null) {
                    DB::table('archivos_imagenes_producto')->where('id', $archivoId)->delete();

                    return [
                        'disco_temporal' => $archivo->disco_temporal,
                        'ruta_temporal' => $archivo->ruta_temporal,
                        'disco' => $archivo->disco,
                        'rutas' => array_values(array_filter([$archivo->ruta, $archivo->ruta_miniatura])),
                    ];
                }
            }

            return null;
        });

        if ($limpieza !== null) {
            Storage::disk($limpieza['disco_temporal'])->delete($limpieza['ruta_temporal']);
            Storage::disk($limpieza['disco'])->delete($limpieza['rutas']);
        }
    }

    /** @param array<string, mixed> $datos */
    private function crearAsignacion(int $archivoId, array $datos, ?object $archivo = null): ImagenProducto
    {
        $productoId = (int) $datos['producto_id'];
        $varianteId = isset($datos['variante_producto_id']) ? (int) $datos['variante_producto_id'] : null;
        DB::table('productos')->where('id', $productoId)->lockForUpdate()->first();
        $this->validarVariante($productoId, $varianteId);

        $duplicada = $this->ambito($productoId, $varianteId)->where('archivo_imagen_producto_id', $archivoId)->lockForUpdate()->exists();

        if ($duplicada) {
            throw ValidationException::withMessages(['archivo_imagen_producto_id' => 'Ese archivo ya está asignado en este ámbito.']);
        }

        $principal = (bool) ($datos['es_principal'] ?? false);
        $tienePrincipal = $this->ambito($productoId, $varianteId)->where('es_principal', true)->exists();
        $productoActivo = (bool) DB::table('productos')->where('id', $productoId)->value('activo');
        $varianteActiva = $varianteId !== null && (bool) DB::table('variantes_producto')->where('id', $varianteId)->value('activo');
        $estadoArchivo = $archivo?->estado ?? 'pendiente';

        if ($principal && $estadoArchivo !== 'listo' && $this->requiereImagenLista($productoActivo, $varianteId)) {
            throw ValidationException::withMessages(['es_principal' => 'Una imagen pendiente o fallida no puede ser principal en un ámbito activo.']);
        }

        if (! $tienePrincipal && ! $this->requiereImagenLista($productoActivo, $varianteId)) {
            $principal = true;
        } elseif (! $tienePrincipal && ($productoActivo || $varianteActiva) && $estadoArchivo === 'listo') {
            $principal = true;
        }

        if ($principal) {
            $this->ambito($productoId, $varianteId)->update(['es_principal' => false, 'updated_at' => now()]);
        }

        $orden = (int) ($datos['orden'] ?? ((int) $this->ambito($productoId, $varianteId)->max('orden')) + 1);

        return ImagenProducto::query()->create([
            'producto_id' => $productoId,
            'variante_producto_id' => $varianteId,
            'archivo_imagen_producto_id' => $archivoId,
            'ruta' => $archivo?->ruta,
            'ruta_miniatura' => $archivo?->ruta_miniatura,
            'estado' => $archivo?->estado ?? 'pendiente',
            'orden' => $orden,
            'es_principal' => $principal,
        ]);
    }

    private function validarVariante(int $productoId, ?int $varianteId): void
    {
        if ($varianteId !== null && ! DB::table('variantes_producto')->where('id', $varianteId)->where('producto_id', $productoId)->exists()) {
            throw ValidationException::withMessages(['variante_producto_id' => 'La variante indicada no pertenece al producto.']);
        }
    }

    private function ambito(int $productoId, ?int $varianteId): Builder
    {
        return ImagenProducto::query()->where('producto_id', $productoId)->where('variante_producto_id', $varianteId);
    }

    private function requiereImagenLista(bool $productoActivo, ?int $varianteId): bool
    {
        if ($productoActivo && $varianteId === null) {
            return true;
        }

        return $varianteId !== null && (bool) DB::table('variantes_producto')->where('id', $varianteId)->value('activo');
    }
}
