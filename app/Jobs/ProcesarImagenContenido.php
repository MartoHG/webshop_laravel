<?php

namespace App\Jobs;

use App\Servicios\Imagenes\ProcesadorImagenes;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProcesarImagenContenido implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60, 180];

    public int $timeout = 180;

    public function __construct(public readonly int $imagenContenidoId) {}

    public function handle(): void
    {
        $imagen = DB::table('imagenes_contenido')->where('id', $this->imagenContenidoId)->first();

        if ($imagen === null) {
            return;
        }

        if ($imagen->estado === 'listo') {
            $this->borrarTemporal($imagen);

            return;
        }

        if (! in_array($imagen->estado, ['pendiente', 'procesando'], true) || $imagen->ruta_temporal === null) {
            return;
        }

        DB::table('imagenes_contenido')->where('id', $imagen->id)->update([
            'estado' => 'procesando',
            'error' => null,
            'updated_at' => now(),
        ]);

        $contenido = Storage::disk($imagen->disco_temporal)->get($imagen->ruta_temporal);
        [$detalle, $miniatura] = app(ProcesadorImagenes::class)->convertirWebp($contenido, $this->formatos($imagen->tipo));

        $rutaDetalle = $this->rutaBase().'/detalle.webp';
        $rutaMiniatura = $this->rutaBase().'/miniatura.webp';
        app(ProcesadorImagenes::class)->guardar($imagen->disco, [$rutaDetalle => $detalle, $rutaMiniatura => $miniatura]);

        $procesado = DB::transaction(function () use ($imagen, $rutaDetalle, $rutaMiniatura): bool {
            $actual = DB::table('imagenes_contenido')->where('id', $imagen->id)->lockForUpdate()->first();

            if ($actual === null) {
                return false;
            }

            DB::table('imagenes_contenido')->where('id', $imagen->id)->update([
                'ruta' => $rutaDetalle,
                'ruta_miniatura' => $rutaMiniatura,
                'estado' => 'listo',
                'error' => null,
                'updated_at' => now(),
            ]);

            return true;
        });

        if (! $procesado) {
            Storage::disk($imagen->disco)->delete([$rutaDetalle, $rutaMiniatura]);
        }

        $this->borrarTemporal($imagen);
    }

    public function failed(?Throwable $exception): void
    {
        $imagen = DB::table('imagenes_contenido')->where('id', $this->imagenContenidoId)->first();

        if ($imagen !== null) {
            Storage::disk($imagen->disco)->delete([$this->rutaBase().'/detalle.webp', $this->rutaBase().'/miniatura.webp']);
        }

        DB::transaction(function () use ($exception): void {
            DB::table('imagenes_contenido')->where('id', $this->imagenContenidoId)->update([
                'estado' => 'fallido',
                'ruta' => null,
                'ruta_miniatura' => null,
                'error' => mb_substr($exception?->getMessage() ?? 'Falló el procesamiento de la imagen.', 0, 2000),
                'updated_at' => now(),
            ]);
        });
    }

    /**
     * El original temporal se borra al terminar bien; ruta_temporal queda en null solo después de borrarlo,
     * así un reintento del job puede completar la limpieza si la anterior se interrumpió.
     */
    private function borrarTemporal(object $imagen): void
    {
        if ($imagen->ruta_temporal === null) {
            return;
        }

        Storage::disk($imagen->disco_temporal)->delete($imagen->ruta_temporal);

        DB::table('imagenes_contenido')->where('id', $imagen->id)->update([
            'ruta_temporal' => null,
            'updated_at' => now(),
        ]);
    }

    /**
     * @return list<array{maximo_ancho: int, maximo_alto: int, calidad: int}>
     *
     * @throws RuntimeException
     */
    private function formatos(string $tipo): array
    {
        $medidas = config('imagenes.contenido.'.$tipo);

        if (! is_array($medidas)) {
            throw new RuntimeException('El tipo de imagen de contenido no es válido.');
        }

        return [
            [
                'maximo_ancho' => (int) $medidas['maximo_ancho'],
                'maximo_alto' => (int) $medidas['maximo_alto'],
                'calidad' => (int) $medidas['calidad'],
            ],
            [
                'maximo_ancho' => (int) $medidas['maximo_ancho_miniatura'],
                'maximo_alto' => (int) $medidas['maximo_alto_miniatura'],
                'calidad' => (int) $medidas['calidad_miniatura'],
            ],
        ];
    }

    private function rutaBase(): string
    {
        return trim((string) config('imagenes.contenido.directorio_final'), '/').'/'.$this->imagenContenidoId;
    }
}
