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
use Throwable;

class ProcesarImagenProducto implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60, 180];

    public int $timeout = 180;

    public function __construct(public readonly int $archivoImagenProductoId) {}

    public function handle(): void
    {
        $archivo = DB::table('archivos_imagenes_producto')->where('id', $this->archivoImagenProductoId)->first();

        if ($archivo === null) {
            return;
        }

        if ($archivo->estado === 'listo') {
            Storage::disk($archivo->disco_temporal)->delete($archivo->ruta_temporal);

            return;
        }

        if (! in_array($archivo->estado, ['pendiente', 'procesando'], true)) {
            return;
        }

        DB::table('archivos_imagenes_producto')->where('id', $archivo->id)->update([
            'estado' => 'procesando',
            'error' => null,
            'updated_at' => now(),
        ]);

        $procesador = app(ProcesadorImagenes::class);
        $contenido = Storage::disk($archivo->disco_temporal)->get($archivo->ruta_temporal);
        [$detalle, $miniatura] = $procesador->convertirWebp($contenido, [
            [
                'maximo_ancho' => (int) config('imagenes.maximo_ancho_detalle'),
                'maximo_alto' => (int) config('imagenes.maximo_alto_detalle'),
                'calidad' => (int) config('imagenes.calidad_detalle'),
            ],
            [
                'maximo_ancho' => (int) config('imagenes.maximo_ancho_miniatura'),
                'maximo_alto' => (int) config('imagenes.maximo_alto_miniatura'),
                'calidad' => (int) config('imagenes.calidad_miniatura'),
            ],
        ]);

        $rutaBase = trim((string) config('imagenes.directorio_final'), '/').'/'.$archivo->id;
        $rutaDetalle = $rutaBase.'/detalle.webp';
        $rutaMiniatura = $rutaBase.'/miniatura.webp';
        $procesador->guardar($archivo->disco, [$rutaDetalle => $detalle, $rutaMiniatura => $miniatura]);

        $procesado = DB::transaction(function () use ($archivo, $rutaDetalle, $rutaMiniatura, $contenido): bool {
            $actual = DB::table('archivos_imagenes_producto')->where('id', $archivo->id)->lockForUpdate()->first();

            if ($actual === null) {
                return false;
            }

            DB::table('archivos_imagenes_producto')->where('id', $archivo->id)->update([
                'ruta' => $rutaDetalle,
                'ruta_miniatura' => $rutaMiniatura,
                'sha256' => hash('sha256', $contenido),
                'estado' => 'listo',
                'error' => null,
                'updated_at' => now(),
            ]);
            DB::table('imagenes_producto')->where('archivo_imagen_producto_id', $archivo->id)->update([
                'ruta' => $rutaDetalle,
                'ruta_miniatura' => $rutaMiniatura,
                'estado' => 'listo',
                'updated_at' => now(),
            ]);

            return true;
        });

        if (! $procesado) {
            Storage::disk($archivo->disco)->delete([$rutaDetalle, $rutaMiniatura]);
        }

        Storage::disk($archivo->disco_temporal)->delete($archivo->ruta_temporal);
    }

    public function failed(?Throwable $exception): void
    {
        $rutaBase = trim((string) config('imagenes.directorio_final'), '/').'/'.$this->archivoImagenProductoId;
        $archivo = DB::table('archivos_imagenes_producto')->where('id', $this->archivoImagenProductoId)->first();

        if ($archivo !== null) {
            Storage::disk($archivo->disco)->delete([$rutaBase.'/detalle.webp', $rutaBase.'/miniatura.webp']);
        }

        DB::transaction(function () use ($exception): void {
            DB::table('archivos_imagenes_producto')->where('id', $this->archivoImagenProductoId)->update([
                'estado' => 'fallido',
                'ruta' => null,
                'ruta_miniatura' => null,
                'error' => mb_substr($exception?->getMessage() ?? 'Falló el procesamiento de la imagen.', 0, 2000),
                'updated_at' => now(),
            ]);
            DB::table('imagenes_producto')->where('archivo_imagen_producto_id', $this->archivoImagenProductoId)->update([
                'estado' => 'fallido',
                'ruta' => null,
                'ruta_miniatura' => null,
                'updated_at' => now(),
            ]);
        });
    }
}
