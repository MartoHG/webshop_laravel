<?php

namespace App\Servicios\Carrito;

use App\Models\ImagenProducto;
use App\Models\VarianteProducto;
use App\Servicios\Precios\AjusteEcommerceService;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CarritoService
{
    private const string CLAVE_SESION = 'carrito';

    private const int CANTIDAD_MINIMA = 1;

    private const int CANTIDAD_MAXIMA = 99;

    public function __construct(
        private readonly AjusteEcommerceService $ajuste,
        private readonly Session $sesion,
    ) {}

    public function estaConfigurado(): bool
    {
        return $this->ajuste->obtenerPorcentaje() !== null;
    }

    /**
     * Agrega unidades a la línea de la variante. La sesión solo guarda ids y cantidades.
     *
     * @return array<string, mixed>
     */
    public function agregar(int $varianteId, int $cantidad): array
    {
        $this->exigirAjusteConfigurado();
        $this->validarCantidad($cantidad);

        $this->exigirDisponible($this->cargarVariantes([$varianteId])->get($varianteId));

        $carrito = $this->leerCarrito();
        $nuevaCantidad = ($carrito[$varianteId] ?? 0) + $cantidad;

        if ($nuevaCantidad > self::CANTIDAD_MAXIMA) {
            throw ValidationException::withMessages([
                'cantidad' => 'Una misma variante admite hasta 99 unidades en el carrito.',
            ]);
        }

        $carrito[$varianteId] = $nuevaCantidad;
        $this->guardarCarrito($carrito);

        return $this->obtener();
    }

    /** @return array<string, mixed> */
    public function cambiarCantidad(int $varianteId, int $cantidad): array
    {
        $this->exigirAjusteConfigurado();
        $this->validarCantidad($cantidad);

        $carrito = $this->leerCarrito();
        $this->exigirLinea($carrito, $varianteId);
        $this->exigirDisponible($this->cargarVariantes([$varianteId])->get($varianteId));

        $carrito[$varianteId] = $cantidad;
        $this->guardarCarrito($carrito);

        return $this->obtener();
    }

    /** @return array<string, mixed> */
    public function quitar(int $varianteId): array
    {
        $this->exigirAjusteConfigurado();

        $carrito = $this->leerCarrito();
        $this->exigirLinea($carrito, $varianteId);

        unset($carrito[$varianteId]);
        $this->guardarCarrito($carrito);

        return $this->obtener();
    }

    public function vaciar(): void
    {
        $this->sesion->forget(self::CLAVE_SESION);
    }

    /**
     * Ids de las variantes en el carrito, leídos de la sesión y sin consultar la base de datos.
     *
     * @return list<int>
     */
    public function idsDeVariantes(): array
    {
        return array_keys($this->leerCarrito());
    }

    /**
     * Recalcula el carrito desde la base de datos. Las líneas no disponibles se conservan y no suman al subtotal.
     *
     * @return array{items: list<array<string, mixed>>, subtotal: string, cantidad_unidades: int, puede_continuar: bool}
     */
    public function obtener(): array
    {
        $porcentaje = $this->exigirAjusteConfigurado();
        $carrito = $this->leerCarrito();
        $variantes = $this->cargarVariantes(array_keys($carrito));

        $items = [];
        $subtotal = '0.00';

        foreach ($carrito as $varianteId => $cantidad) {
            $item = $this->representarItem($varianteId, $cantidad, $variantes->get($varianteId), $porcentaje);

            if ($item['disponible']) {
                $subtotal = bcadd($subtotal, (string) $item['subtotal'], 2);
            }

            $items[] = $item;
        }

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'cantidad_unidades' => (int) array_sum($carrito),
            'puede_continuar' => $items !== [] && array_all($items, fn (array $item): bool => $item['disponible']),
        ];
    }

    /**
     * @return array{items: list<array<string, mixed>>, subtotal: string, cantidad_unidades: int, puede_continuar: bool}
     *
     * @throws ValidationException
     */
    public function validarParaCheckout(): array
    {
        $carrito = $this->obtener();

        if ($carrito['items'] === []) {
            throw ValidationException::withMessages(['carrito' => 'El carrito está vacío.']);
        }

        if (! $carrito['puede_continuar']) {
            throw ValidationException::withMessages([
                'carrito' => 'Hay productos del carrito que no están disponibles. Quitalos para continuar.',
            ]);
        }

        return $carrito;
    }

    private function exigirAjusteConfigurado(): string
    {
        $porcentaje = $this->ajuste->obtenerPorcentaje();

        if ($porcentaje === null) {
            throw new \LogicException('Se usó el carrito sin validar la configuración del ajuste e-commerce.');
        }

        return $porcentaje;
    }

    private function validarCantidad(int $cantidad): void
    {
        if ($cantidad < self::CANTIDAD_MINIMA || $cantidad > self::CANTIDAD_MAXIMA) {
            throw ValidationException::withMessages([
                'cantidad' => 'La cantidad debe estar entre 1 y 99.',
            ]);
        }
    }

    /** @param array<int, int> $carrito */
    private function exigirLinea(array $carrito, int $varianteId): void
    {
        if (! array_key_exists($varianteId, $carrito)) {
            throw new NotFoundHttpException('La variante no está en el carrito.');
        }
    }

    private function exigirDisponible(?VarianteProducto $variante): void
    {
        $motivo = $this->motivoNoDisponible($variante);

        if ($motivo === null) {
            return;
        }

        throw ValidationException::withMessages([
            'variante_producto_id' => $motivo === 'agotado'
                ? 'La variante está agotada y no puede agregarse al carrito.'
                : 'La variante no está disponible para la compra.',
        ]);
    }

    private function motivoNoDisponible(?VarianteProducto $variante): ?string
    {
        return $variante === null ? 'no_disponible' : $variante->motivoNoDisponible();
    }

    /**
     * @return array{variante_producto_id: int, producto_id: ?int, nombre_producto: ?string, slug: ?string, color: ?string, etiqueta: ?string, imagen_miniatura: ?string, precio_unitario: ?string, cantidad: int, subtotal: ?string, disponible: bool, motivo: ?string}
     */
    private function representarItem(int $varianteId, int $cantidad, ?VarianteProducto $variante, string $porcentaje): array
    {
        $motivo = $this->motivoNoDisponible($variante);
        $precioUnitario = $variante === null
            ? null
            : $this->ajuste->calcularPrecioPublicado((string) $variante->precio_base, $porcentaje);

        return [
            'variante_producto_id' => $varianteId,
            'producto_id' => $variante?->producto_id,
            'nombre_producto' => $variante?->producto->nombre,
            'slug' => $variante?->producto->slug,
            'color' => $variante?->color,
            'etiqueta' => $variante?->etiqueta,
            'imagen_miniatura' => $variante === null ? null : $this->imagenMiniatura($variante),
            'precio_unitario' => $precioUnitario,
            'cantidad' => $cantidad,
            'subtotal' => $precioUnitario === null ? null : bcmul($precioUnitario, (string) $cantidad, 2),
            'disponible' => $motivo === null,
            'motivo' => $motivo,
        ];
    }

    /**
     * Prioriza la imagen propia de la variante y, si no tiene, la imagen general del producto.
     */
    private function imagenMiniatura(VarianteProducto $variante): ?string
    {
        $imagenes = $variante->producto->imagenes;
        $imagen = $this->primeraConMiniatura($imagenes->where('variante_producto_id', $variante->id))
            ?? $this->primeraConMiniatura($imagenes->whereNull('variante_producto_id'));

        if ($imagen === null) {
            return null;
        }

        return Storage::disk($imagen->archivo->disco)->url($imagen->archivo->ruta_miniatura);
    }

    /** @param Collection<int, ImagenProducto> $imagenes */
    private function primeraConMiniatura(Collection $imagenes): ?ImagenProducto
    {
        return $imagenes
            ->sortBy([['es_principal', 'desc'], ['orden', 'asc']])
            ->first(fn (ImagenProducto $imagen): bool => $imagen->archivo?->ruta_miniatura !== null);
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, VarianteProducto>
     */
    private function cargarVariantes(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        return VarianteProducto::query()
            ->whereIn('id', $ids)
            ->with([
                'producto',
                'producto.imagenes' => fn ($imagenes) => $imagenes
                    ->where('estado', 'listo')
                    ->whereNotNull('ruta')
                    ->whereHas('archivo', fn (Builder $archivo): Builder => $archivo->where('estado', 'listo')),
                'producto.imagenes.archivo',
            ])
            ->get()
            ->keyBy('id');
    }

    /** @return array<int, int> */
    private function leerCarrito(): array
    {
        $carrito = $this->sesion->get(self::CLAVE_SESION, []);

        return is_array($carrito) ? array_map(intval(...), $carrito) : [];
    }

    /** @param array<int, int> $carrito */
    private function guardarCarrito(array $carrito): void
    {
        $this->sesion->put(self::CLAVE_SESION, $carrito);
    }
}
