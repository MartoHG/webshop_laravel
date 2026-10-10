<?php

namespace App\Servicios\Productos;

use App\Models\Producto;
use App\Servicios\Auditoria\AuditoriaService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductoService
{
    public function __construct(private AuditoriaService $auditoria) {}

    /** @return Collection<int, Producto> */
    public function listar(): Collection
    {
        return Producto::query()->with(['variantes', 'categorias.imagenContenido', 'imagenes'])->latest('id')->get();
    }

    public function mostrar(Producto $producto): Producto
    {
        return $producto->load(['variantes.imagenes', 'categorias.imagenContenido', 'imagenes']);
    }

    /** @param array{nombre: string, descripcion?: ?string, variantes: list<array{color?: ?string, etiqueta?: ?string, sku: string, precio_base: numeric-string|int|float}>} $datos */
    public function crear(array $datos): Producto
    {
        $this->validarVariantes($datos['variantes']);

        return DB::transaction(function () use ($datos): Producto {
            $producto = new Producto;
            $producto->nombre = $datos['nombre'];
            $producto->descripcion = $datos['descripcion'] ?? null;
            $producto->slug = $this->crearSlug($datos['nombre']);
            $producto->activo = false;
            $producto->disponible = false;
            $producto->save();

            foreach ($datos['variantes'] as $datosVariante) {
                $producto->variantes()->create($this->datosVariante($datosVariante));
            }

            return $this->mostrar($producto->refresh());
        });
    }

    /** @param array{nombre?: string, descripcion?: ?string} $datos */
    public function actualizar(Producto $producto, array $datos): Producto
    {
        return DB::transaction(function () use ($producto, $datos): Producto {
            $producto = Producto::query()->lockForUpdate()->findOrFail($producto->getKey());
            $producto->fill($datos);
            $producto->save();

            return $this->mostrar($producto->refresh());
        });
    }

    /** @param array{activo?: bool|string|int, disponible?: bool|string|int} $datos */
    public function actualizarEstado(Producto $producto, array $datos): Producto
    {
        return DB::transaction(function () use ($producto, $datos): Producto {
            $producto = Producto::query()->lockForUpdate()->findOrFail($producto->getKey());
            $producto->variantes()->lockForUpdate()->get(['id', 'activo']);

            $activo = array_key_exists('activo', $datos) ? (bool) $datos['activo'] : $producto->activo;
            $disponible = array_key_exists('disponible', $datos) ? (bool) $datos['disponible'] : $producto->disponible;

            if ($activo) {
                $tieneCategoriaActiva = $producto->categorias()
                    ->where('categorias.activo', true)
                    ->lockForUpdate()
                    ->exists();
                $tieneImagenGeneralPrincipalLista = $producto->imagenes()
                    ->whereNull('variante_producto_id')
                    ->where('estado', 'listo')
                    ->where('es_principal', true)
                    ->lockForUpdate()
                    ->exists();
                $variantesActivas = $producto->variantes()
                    ->where('activo', true)
                    ->lockForUpdate()
                    ->get();

                if (! $tieneCategoriaActiva) {
                    throw ValidationException::withMessages(['activo' => __('Para activar el producto, asigna al menos una categoría activa.')]);
                }

                if (! $tieneImagenGeneralPrincipalLista) {
                    throw ValidationException::withMessages(['activo' => __('Para activar el producto, necesita una imagen general principal procesada.')]);
                }

                if ($variantesActivas->isEmpty()) {
                    throw ValidationException::withMessages(['activo' => __('Para activar el producto, debe tener al menos una variante activa.')]);
                }

                if ($variantesActivas->count() > 1) {
                    foreach ($variantesActivas as $varianteActiva) {
                        $imagenes = $varianteActiva->imagenes()->lockForUpdate()->get(['id', 'estado', 'es_principal']);
                        $tieneImagenPrincipalLista = $imagenes->count() > 1
                            ? $imagenes->contains(fn ($imagen): bool => $imagen->estado === 'listo' && $imagen->es_principal)
                            : $imagenes->contains(fn ($imagen): bool => $imagen->estado === 'listo');

                        if (! $tieneImagenPrincipalLista) {
                            throw ValidationException::withMessages([
                                'activo' => __('Para activar un producto con varias variantes activas, cada variante debe tener una imagen propia procesada.'),
                            ]);
                        }
                    }
                }
            }

            $estadoAnterior = ['activo' => $producto->activo, 'disponible' => $producto->disponible];

            $producto->activo = $activo;
            $producto->disponible = $disponible;
            $producto->save();

            $this->auditoria->registrarCambios(
                'producto.estado_actualizado',
                $producto,
                $estadoAnterior,
                ['activo' => $activo, 'disponible' => $disponible],
            );

            return $this->mostrar($producto->refresh());
        });
    }

    /** @param list<array{color?: ?string, etiqueta?: ?string, sku: string, precio_base: numeric-string|int|float}> $variantes */
    public function validarVariantes(array $variantes): void
    {
        $colores = [];
        $etiquetas = [];

        foreach ($variantes as $indice => $variante) {
            $color = $this->normalizar($variante['color'] ?? null);
            $colores[$color === null ? 'null:' : 'valor:'.mb_strtolower($color)][] = $indice;

            $etiqueta = $this->normalizar($variante['etiqueta'] ?? null);
            if ($etiqueta !== null) {
                $claveEtiqueta = mb_strtolower($etiqueta);
                if (isset($etiquetas[$claveEtiqueta])) {
                    throw ValidationException::withMessages([
                        "variantes.{$indice}.etiqueta" => __('Las etiquetas descriptivas deben ser distintas dentro del producto.'),
                    ]);
                }
                $etiquetas[$claveEtiqueta] = true;
            }
        }

        $errores = [];

        foreach ($colores as $indices) {
            if (count($indices) < 2) {
                continue;
            }

            foreach ($indices as $indice) {
                if ($this->normalizar($variantes[$indice]['etiqueta'] ?? null) === null) {
                    $errores["variantes.{$indice}.etiqueta"] = __('La etiqueta es obligatoria cuando varias variantes comparten color.');
                }
            }
        }

        if ($errores !== []) {
            throw ValidationException::withMessages($errores);
        }
    }

    /** @param array{color?: ?string, etiqueta?: ?string, sku: string, precio_base: numeric-string|int|float} $datos */
    public function datosVariante(array $datos): array
    {
        return [
            'color' => $this->normalizar($datos['color'] ?? null),
            'etiqueta' => $this->normalizar($datos['etiqueta'] ?? null),
            'sku' => trim($datos['sku']),
            'precio_base' => $datos['precio_base'],
            'activo' => false,
            'disponible' => false,
        ];
    }

    private function crearSlug(string $nombre): string
    {
        $base = Str::slug($nombre);
        $base = $base !== '' ? $base : 'producto';
        $slug = $base;
        $sufijo = 2;

        while (Producto::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$sufijo}";
            $sufijo++;
        }

        return $slug;
    }

    private function normalizar(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        return $valor === '' ? null : $valor;
    }
}
