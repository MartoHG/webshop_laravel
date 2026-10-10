<?php

namespace App\Servicios\Imagenes;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ProcesadorImagenes
{
    /**
     * Valida la imagen original y genera una versión WebP por cada formato pedido, en el mismo orden.
     *
     * @param  list<array{maximo_ancho: int, maximo_alto: int, calidad: int}>  $formatos
     * @return list<string>
     *
     * @throws RuntimeException
     */
    public function convertirWebp(string $contenido, array $formatos): array
    {
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

            return array_map(
                fn (array $formato): string => $this->codificarWebp($imagenOrigen, $ancho, $alto, $formato),
                $formatos,
            );
        } finally {
            imagedestroy($imagenOrigen);
        }
    }

    /**
     * Escribe cada contenido en su ruta de destino con visibilidad pública. Si una escritura falla, lanza la excepción
     * y deja a cargo del job el borrado de las rutas ya escritas.
     *
     * @param  array<string, string>  $archivos  Contenido indexado por ruta de destino.
     *
     * @throws RuntimeException
     */
    public function guardar(string $disco, array $archivos): void
    {
        $almacenamiento = Storage::disk($disco);

        foreach ($archivos as $ruta => $contenido) {
            if (! $almacenamiento->put($ruta, $contenido, ['visibility' => 'public'])) {
                throw new RuntimeException('No se pudieron guardar las versiones WebP.');
            }
        }
    }

    /**
     * @param  array{maximo_ancho: int, maximo_alto: int, calidad: int}  $formato
     *
     * @throws RuntimeException
     */
    private function codificarWebp(\GdImage $origen, int $ancho, int $alto, array $formato): string
    {
        $escala = min(1, $formato['maximo_ancho'] / $ancho, $formato['maximo_alto'] / $alto);
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
        $convertida = imagewebp($destino, null, $formato['calidad']);
        $contenido = ob_get_clean();
        imagedestroy($destino);

        if (! $convertida || $contenido === false) {
            throw new RuntimeException('No se pudo convertir la imagen a WebP.');
        }

        return $contenido;
    }
}
