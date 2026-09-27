<?php

namespace App\Servicios\Catalogo;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\VarianteProducto;
use App\Servicios\Precios\AjusteEcommerceService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class CatalogoService
{
    public function __construct(private readonly AjusteEcommerceService $ajuste) {}

    public function estaConfigurado(): bool
    {
        return $this->ajuste->obtenerPorcentaje() !== null;
    }

    /** @return list<array{nombre: string, slug: string}> */
    public function listarCategorias(): array
    {
        return Categoria::query()->where('activo', true)->orderBy('orden')->orderBy('nombre')
            ->get(['nombre', 'slug'])->map(fn (Categoria $categoria): array => [
                'nombre' => $categoria->nombre,
                'slug' => $categoria->slug,
            ])->all();
    }

    /** @return LengthAwarePaginator<int, array<string, mixed>> */
    public function listar(array $filtros): LengthAwarePaginator
    {
        $porcentaje = $this->obtenerPorcentajeRequerido();
        $consulta = Producto::query()
            ->where('activo', true)
            ->whereHas('categorias', fn (Builder $categorias): Builder => $categorias->where('categorias.activo', true))
            ->whereHas('imagenes', fn (Builder $imagenes): Builder => $this->imagenGeneralPrincipalLista($imagenes))
            ->with([
                'variantes' => fn ($consulta) => $consulta->where('activo', true)->orderBy('id'),
                'categorias' => fn ($consulta) => $consulta->where('categorias.activo', true)->orderBy('orden')->orderBy('nombre'),
                'imagenes' => fn ($consulta) => $consulta->whereNull('variante_producto_id')->whereNotNull('ruta')->where('imagenes_producto.estado', 'listo')->whereExists(fn (\Illuminate\Database\Query\Builder $archivo): \Illuminate\Database\Query\Builder => $archivo->selectRaw('1')->from('archivos_imagenes_producto')->whereColumn('archivos_imagenes_producto.id', 'imagenes_producto.archivo_imagen_producto_id')->where('archivos_imagenes_producto.estado', 'listo'))->orderBy('orden'),
                'imagenes.archivo',
            ])
            ->whereHas('variantes', fn (Builder $consulta): Builder => $consulta->where('activo', true));

        if (($filtros['contexto'] ?? 'catalogo') === 'inicio') {
            $consulta->where('disponible', true)
                ->whereHas('variantes', fn (Builder $variantes): Builder => $variantes->where('activo', true)->where('disponible', true));
        }

        if (($filtros['buscar'] ?? '') !== '') {
            $termino = '%'.addcslashes($filtros['buscar'], '%_\\').'%';
            $consulta->where(fn (Builder $productos): Builder => $productos
                ->where('nombre', 'like', $termino)
                ->orWhere('descripcion', 'like', $termino));
        }

        if (($filtros['categoria'] ?? '') !== '') {
            $consulta->whereHas('categorias', fn (Builder $categorias): Builder => $categorias
                ->where('categorias.activo', true)
                ->where('categorias.slug', $filtros['categoria']));
        }

        $pagina = $consulta->orderBy('nombre')->paginate($filtros['por_pagina'] ?? 12);
        $pagina->through(fn (Producto $producto): array => $this->representarProducto($producto, $porcentaje, false));

        return $pagina;
    }

    /** @return array<string, mixed> */
    public function mostrar(string $slug): array
    {
        $porcentaje = $this->obtenerPorcentajeRequerido();
        $producto = Producto::query()
            ->where('activo', true)
            ->where('slug', $slug)
            ->whereHas('categorias', fn (Builder $categorias): Builder => $categorias->where('categorias.activo', true))
            ->whereHas('imagenes', fn (Builder $imagenes): Builder => $this->imagenGeneralPrincipalLista($imagenes))
            ->with([
                'variantes' => fn ($consulta) => $consulta->where('activo', true)->orderBy('id'),
                'categorias' => fn ($consulta) => $consulta->where('categorias.activo', true)->orderBy('orden')->orderBy('nombre'),
                'imagenes' => fn ($consulta) => $consulta->whereNotNull('ruta')->where('imagenes_producto.estado', 'listo')->whereExists(fn (\Illuminate\Database\Query\Builder $archivo): \Illuminate\Database\Query\Builder => $archivo->selectRaw('1')->from('archivos_imagenes_producto')->whereColumn('archivos_imagenes_producto.id', 'imagenes_producto.archivo_imagen_producto_id')->where('archivos_imagenes_producto.estado', 'listo'))->orderBy('orden'),
                'imagenes.archivo',
            ])
            ->whereHas('variantes', fn (Builder $consulta): Builder => $consulta->where('activo', true))
            ->firstOrFail();

        return $this->representarProducto($producto, $porcentaje, true);
    }

    private function obtenerPorcentajeRequerido(): string
    {
        $porcentaje = $this->ajuste->obtenerPorcentaje();

        if ($porcentaje === null) {
            throw new \LogicException('Se consultó el catálogo sin validar la configuración del ajuste e-commerce.');
        }

        return $porcentaje;
    }

    /** @return array<string, mixed> */
    private function representarProducto(Producto $producto, string $porcentaje, bool $detalle): array
    {
        $variantes = $producto->variantes->map(fn (VarianteProducto $variante): array => [
            'id' => $variante->id,
            'color' => $variante->color,
            'etiqueta' => $variante->etiqueta,
            'disponible' => (bool) $producto->disponible && (bool) $variante->disponible,
            'precio_publicado' => $this->ajuste->calcularPrecioPublicado((string) $variante->precio_base, $porcentaje),
            'imagenes' => $detalle
                ? $producto->imagenes->where('variante_producto_id', $variante->id)->values()->map(fn ($imagen): array => $this->representarImagen($imagen))->all()
                : [],
        ])->values();

        $imagenesGenerales = $producto->imagenes
            ->whereNull('variante_producto_id')
            ->values()
            ->map(fn ($imagen): array => $this->representarImagen($imagen))
            ->all();

        $datos = [
            'id' => $producto->id,
            'nombre' => $producto->nombre,
            'slug' => $producto->slug,
            'disponible' => (bool) $producto->disponible && $variantes->contains(fn (array $variante): bool => $variante['disponible']),
            'categorias' => $producto->categorias->map(fn (Categoria $categoria): array => [
                'nombre' => $categoria->nombre,
                'slug' => $categoria->slug,
            ])->all(),
            'imagenes' => $imagenesGenerales,
            'variantes' => $variantes->all(),
        ];

        if ($detalle) {
            $datos['descripcion'] = $producto->descripcion;
        }

        return $datos;
    }

    private function imagenGeneralPrincipalLista(Builder $consulta): Builder
    {
        return $consulta->whereNull('variante_producto_id')
            ->where('es_principal', true)
            ->whereNotNull('ruta')
            ->where('imagenes_producto.estado', 'listo')
            ->whereExists(fn (\Illuminate\Database\Query\Builder $archivo): \Illuminate\Database\Query\Builder => $archivo->selectRaw('1')
                ->from('archivos_imagenes_producto')
                ->whereColumn('archivos_imagenes_producto.id', 'imagenes_producto.archivo_imagen_producto_id')
                ->where('archivos_imagenes_producto.estado', 'listo'));
    }

    /** @return array{id: int, ruta: ?string, ruta_miniatura: ?string, url: ?string, url_miniatura: ?string, principal: bool} */
    private function representarImagen(object $imagen): array
    {
        $archivo = $imagen->archivo;

        return [
            'id' => $imagen->id,
            'ruta' => $imagen->ruta,
            'ruta_miniatura' => $imagen->ruta_miniatura,
            'url' => $archivo?->ruta !== null ? Storage::disk($archivo->disco)->url($archivo->ruta) : null,
            'url_miniatura' => $archivo?->ruta_miniatura !== null ? Storage::disk($archivo->disco)->url($archivo->ruta_miniatura) : null,
            'principal' => (bool) $imagen->es_principal,
        ];
    }
}
