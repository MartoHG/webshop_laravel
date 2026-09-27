<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
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

        $contenido = Storage::disk($archivo->disco_temporal)->get($archivo->ruta_temporal);
        $datosImagen = getimagesizefromstring($contenido);

        if ($datosImagen === false || ! in_array($datosImagen['mime'] ?? null, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new RuntimeException('El archivo no es una imagen JPEG, PNG o WebP válida.');
        }

        $ancho = (int) $datosImagen[0];
        $alto = (int) $datosImagen[1];

        if (($ancho * $alto) > (int) config('imagenes.maximo_pixeles')) {
            throw new RuntimeException('La imagen supera el límite de píxeles permitido.');
        }

        $imagenOrigen = imagecreatefromstring($contenido);

        if ($imagenOrigen === false) {
            throw new RuntimeException('El archivo no contiene una imagen válida.');
        }

        try {
            if (! function_exists('imagewebp')) {
                throw new RuntimeException('GD no tiene soporte para WebP.');
            }

            $detalle = $this->convertirWebp(
                $imagenOrigen,
                $ancho,
                $alto,
                (int) config('imagenes.maximo_ancho_detalle'),
                (int) config('imagenes.maximo_alto_detalle'),
                (int) config('imagenes.calidad_detalle'),
            );
            $miniatura = $this->convertirWebp(
                $imagenOrigen,
                $ancho,
                $alto,
                (int) config('imagenes.maximo_ancho_miniatura'),
                (int) config('imagenes.maximo_alto_miniatura'),
                (int) config('imagenes.calidad_miniatura'),
            );
        } finally {
            imagedestroy($imagenOrigen);
        }

        $rutaBase = trim((string) config('imagenes.directorio_final'), '/').'/'.$archivo->id;
        $rutaDetalle = $rutaBase.'/detalle.webp';
        $rutaMiniatura = $rutaBase.'/miniatura.webp';
        $disco = Storage::disk($archivo->disco);

        if (! $disco->put($rutaDetalle, $detalle, ['visibility' => 'public']) || ! $disco->put($rutaMiniatura, $miniatura, ['visibility' => 'public'])) {
            throw new RuntimeException('No se pudieron guardar las versiones WebP.');
        }

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
            $disco->delete([$rutaDetalle, $rutaMiniatura]);
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

    private function convertirWebp(\GdImage $origen, int $ancho, int $alto, int $maximoAncho, int $maximoAlto, int $calidad): string
    {
        $escala = min(1, $maximoAncho / $ancho, $maximoAlto / $alto);
        $anchoDestino = max(1, (int) floor($ancho * $escala));
        $altoDestino = max(1, (int) floor($alto * $escala));
        $destino = imagecreatetruecolor($anchoDestino, $altoDestino);

        if ($destino === false) {
            throw new RuntimeException('No se pudo reservar memoria para procesar la imagen.');
        }

        imagealphablending($destino, false);
        imagesavealpha($destino, true);
        imagefill($destino, 0, 0, imagecolorallocatealpha($destino, 0, 0, 0, 127));
        imagecopyresampled($destino, $origen, 0, 0, 0, 0, $anchoDestino, $altoDestino, $ancho, $alto);

        ob_start();
        $convertida = imagewebp($destino, null, $calidad);
        $contenido = ob_get_clean();
        imagedestroy($destino);

        if (! $convertida || $contenido === false) {
            throw new RuntimeException('No se pudo convertir la imagen a WebP.');
        }

        return $contenido;
    }
}
