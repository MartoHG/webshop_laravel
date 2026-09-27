<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administracion\Imagenes\AsignarImagenProductoRequest;
use App\Http\Requests\Administracion\Imagenes\EliminarImagenProductoRequest;
use App\Http\Requests\Administracion\Imagenes\ReordenarImagenesProductoRequest;
use App\Http\Requests\Administracion\Imagenes\SubirImagenProductoRequest;
use App\Models\ImagenProducto;
use App\Servicios\Imagenes\ImagenProductoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ImagenProductoController extends Controller
{
    public function subir(SubirImagenProductoRequest $request, ImagenProductoService $servicio): JsonResponse
    {
        $datos = $request->validated();
        $archivo = $datos['imagen'];
        unset($datos['imagen']);
        $resultado = $servicio->subir($archivo, $datos);

        return response()->json([
            'mensaje' => 'Imagen recibida y pendiente de procesamiento.',
            'imagen' => $this->serializar($resultado['imagen']),
            'archivo_imagen_producto_id' => $resultado['archivo_imagen_producto_id'],
        ], 202);
    }

    public function asignar(AsignarImagenProductoRequest $request, ImagenProductoService $servicio): JsonResponse
    {
        $imagen = $servicio->asignar($request->validated());

        return response()->json(['mensaje' => 'Imagen asignada.', 'imagen' => $this->serializar($imagen)], 201);
    }

    public function mostrar(EliminarImagenProductoRequest $request, ImagenProducto $imagen): JsonResponse
    {
        return response()->json(['imagen' => $this->serializar($imagen)]);
    }

    public function reordenar(ReordenarImagenesProductoRequest $request, ImagenProductoService $servicio): JsonResponse
    {
        $datos = $request->validated();
        $servicio->reordenar($datos['producto_id'], $datos['variante_producto_id'] ?? null, $datos['imagenes']);

        return response()->json(['mensaje' => 'Orden de imágenes actualizado.']);
    }

    public function establecerPrincipal(EliminarImagenProductoRequest $request, ImagenProducto $imagen, ImagenProductoService $servicio): JsonResponse
    {
        $imagen = $servicio->establecerPrincipal($imagen);

        return response()->json(['mensaje' => 'Imagen principal actualizada.', 'imagen' => $this->serializar($imagen)]);
    }

    public function eliminar(EliminarImagenProductoRequest $request, ImagenProducto $imagen, ImagenProductoService $servicio): JsonResponse
    {
        $servicio->eliminar($imagen);

        return response()->json(['mensaje' => 'Asignación de imagen eliminada.']);
    }

    public function reintentar(EliminarImagenProductoRequest $request, int $archivo, ImagenProductoService $servicio): JsonResponse
    {
        $servicio->reintentar($archivo);

        return response()->json(['mensaje' => 'El procesamiento de la imagen se volvió a encolar.'], 202);
    }

    /** @return array<string, mixed> */
    private function serializar(ImagenProducto $imagen): array
    {
        $imagen->loadMissing('archivo');
        $archivo = $imagen->archivo;

        return [
            'id' => $imagen->id,
            'producto_id' => $imagen->producto_id,
            'variante_producto_id' => $imagen->variante_producto_id,
            'estado' => $imagen->estado,
            'error' => $archivo?->error,
            'ruta' => $imagen->ruta,
            'ruta_miniatura' => $imagen->ruta_miniatura,
            'url' => $imagen->ruta === null || $archivo === null ? null : Storage::disk($archivo->disco)->url($imagen->ruta),
            'url_miniatura' => $imagen->ruta_miniatura === null || $archivo === null ? null : Storage::disk($archivo->disco)->url($imagen->ruta_miniatura),
            'orden' => $imagen->orden,
            'es_principal' => $imagen->es_principal,
        ];
    }
}
