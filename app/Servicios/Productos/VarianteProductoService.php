<?php

namespace App\Servicios\Productos;

use App\Models\Producto;
use App\Models\VarianteProducto;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VarianteProductoService
{
    public function __construct(private ProductoService $productoService) {}

    /** @param array{color?: ?string, etiqueta?: ?string, sku: string, precio_base: numeric-string|int|float} $datos */
    public function crear(Producto $producto, array $datos): VarianteProducto
    {
        return DB::transaction(function () use ($producto, $datos): VarianteProducto {
            $producto = Producto::query()->lockForUpdate()->findOrFail($producto->getKey());
            $variantes = $producto->variantes()->lockForUpdate()->get();
            $candidatas = $variantes->map(fn (VarianteProducto $variante): array => [
                'color' => $variante->color,
                'etiqueta' => $variante->etiqueta,
            ])->all();
            $candidatas[] = ['color' => $datos['color'] ?? null, 'etiqueta' => $datos['etiqueta'] ?? null];
            $this->productoService->validarVariantes($candidatas);

            return $producto->variantes()->create($this->productoService->datosVariante($datos))->refresh();
        });
    }

    /** @param array{activo?: bool|string|int, disponible?: bool|string|int} $datos */
    public function actualizarEstado(Producto $producto, VarianteProducto $variante, array $datos): VarianteProducto
    {
        return DB::transaction(function () use ($producto, $variante, $datos): VarianteProducto {
            $producto = Producto::query()->lockForUpdate()->findOrFail($producto->getKey());
            $variantes = $producto->variantes()->lockForUpdate()->get();
            $variante = $variantes->firstWhere('id', $variante->getKey());

            if (! $variante instanceof VarianteProducto) {
                throw ValidationException::withMessages(['variante' => __('La variante no pertenece a este producto.')]);
            }

            $activo = array_key_exists('activo', $datos) ? (bool) $datos['activo'] : $variante->activo;
            $disponible = array_key_exists('disponible', $datos) ? (bool) $datos['disponible'] : $variante->disponible;
            $variantesActivas = $variantes->filter(fn (VarianteProducto $existente): bool => $existente->activo);

            if ($variante->activo && ! $activo && $producto->activo && $variantesActivas->count() <= 1) {
                throw ValidationException::withMessages([
                    'activo' => __('No se puede desactivar la última variante activa de un producto activo.'),
                ]);
            }

            if ($activo) {
                $otrasVariantesActivas = $variantesActivas->reject(fn (VarianteProducto $existente): bool => $existente->is($variante));

                if ($otrasVariantesActivas->isNotEmpty()) {
                    if (! $this->tieneImagenVarianteLista($variante)) {
                        throw ValidationException::withMessages([
                            'activo' => __('Al activar una segunda variante, cada variante activa necesita una imagen propia procesada.'),
                        ]);
                    }

                    foreach ($otrasVariantesActivas as $varianteActiva) {
                        if (! $this->tieneImagenVarianteLista($varianteActiva)) {
                            throw ValidationException::withMessages([
                                'activo' => __('Antes de activar otra variante, asigna una imagen propia procesada a cada variante que ya está activa.'),
                            ]);
                        }
                    }
                } elseif (! $this->tieneImagenVarianteLista($variante) && ! $this->tieneImagenGeneralPrincipalLista($producto)) {
                    throw ValidationException::withMessages([
                        'activo' => __('La variante necesita una imagen procesada o, si será la única activa, una imagen general principal procesada.'),
                    ]);
                }
            }

            $variante->activo = $activo;
            $variante->disponible = $disponible;
            $variante->save();

            return $variante->refresh();
        });
    }

    /** @param array{color?: ?string, etiqueta?: ?string, sku?: string, precio_base?: numeric-string|int|float} $datos */
    public function actualizar(VarianteProducto $variante, array $datos): VarianteProducto
    {
        return DB::transaction(function () use ($variante, $datos): VarianteProducto {
            $producto = Producto::query()->lockForUpdate()->findOrFail($variante->producto_id);
            $variante = VarianteProducto::query()->lockForUpdate()->findOrFail($variante->getKey());
            $variantes = $producto->variantes()->lockForUpdate()->get();
            $candidatas = $variantes->map(fn (VarianteProducto $existente): array => [
                'color' => $existente->is($variante) ? (array_key_exists('color', $datos) ? $datos['color'] : $existente->color) : $existente->color,
                'etiqueta' => $existente->is($variante) ? (array_key_exists('etiqueta', $datos) ? $datos['etiqueta'] : $existente->etiqueta) : $existente->etiqueta,
            ])->all();
            $this->productoService->validarVariantes($candidatas);

            $variante->fill($datos);
            $variante->color = trim((string) $variante->color) !== '' ? trim((string) $variante->color) : null;
            $variante->etiqueta = trim((string) $variante->etiqueta) !== '' ? trim((string) $variante->etiqueta) : null;
            $variante->sku = trim($variante->sku);
            $variante->save();

            return $variante->refresh();
        });
    }

    private function tieneImagenVarianteLista(VarianteProducto $variante): bool
    {
        $imagenes = $variante->imagenes()->lockForUpdate()->get(['id', 'estado', 'es_principal']);

        if ($imagenes->count() > 1) {
            return $imagenes->contains(fn ($imagen): bool => $imagen->estado === 'listo' && $imagen->es_principal);
        }

        return $imagenes->contains(fn ($imagen): bool => $imagen->estado === 'listo');
    }

    private function tieneImagenGeneralPrincipalLista(Producto $producto): bool
    {
        return $producto->imagenes()
            ->whereNull('variante_producto_id')
            ->where('estado', 'listo')
            ->where('es_principal', true)
            ->lockForUpdate()
            ->exists();
    }
}
