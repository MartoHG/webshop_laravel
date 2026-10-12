<?php

namespace App\Servicios\Catalogo;

use App\Servicios\Precios\FormatoMoneda;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DetalleProductoService
{
    private const string DESCRIPCION_PREDETERMINADA = 'muebles de jardín con envío a todo el país o retiro en el local de Olavarría.';

    /**
     * Arma los datos de la página de un producto a partir de lo que devuelve CatalogoService::mostrar().
     *
     * Hay variante elegida cuando se pide una disponible o cuando solo hay una disponible. Sin variante elegida,
     * el precio muestra "Desde" el más bajo si las variantes disponibles tienen precios distintos.
     *
     * @param  array<string, mixed>  $producto
     * @return array{nombre: string, slug: string, descripcion: ?string, descripcion_meta: string, disponible: bool, volver: array{nombre: string, url: string}, imagenes: list<array{url: string, url_miniatura: ?string, alt: string}>, opciones: list<array{id: int, titulo: string, subtitulo: ?string, disponible: bool, seleccionada: bool}>, mostrar_opciones: bool, opcion_unica: ?int, precio: ?string, precio_desde: bool, direccion_retiro: ?string}
     */
    public function componer(array $producto, ?int $varianteSolicitada, ?string $direccionRetiro): array
    {
        $variantes = collect($producto['variantes'])->values();
        $disponibles = $variantes->where('disponible', true)->values();
        $seleccionada = $this->varianteSeleccionada($disponibles, $varianteSolicitada);

        $nombre = (string) $producto['nombre'];
        $descripcion = $this->textoOpcional($producto['descripcion'] ?? null);
        $etiquetaSeleccionada = $seleccionada === null ? null : $this->etiquetaVariante($seleccionada);
        $alt = $nombre.($etiquetaSeleccionada !== null ? ', '.$etiquetaSeleccionada : '');

        $imagenes = $seleccionada !== null ? $this->imagenesConUrl($seleccionada['imagenes'], $alt) : [];
        if ($imagenes === []) {
            $imagenes = $this->imagenesConUrl($producto['imagenes'], $alt);
        }

        $mostrarOpciones = $variantes->count() > 1
            || $variantes->contains(fn (array $variante): bool => $this->textoOpcional($variante['color']) !== null || $this->textoOpcional($variante['etiqueta']) !== null);

        [$precio, $precioDesde] = $this->precio($seleccionada, $disponibles);

        $categoria = collect($producto['categorias'])->first(fn (array $categoria): bool => $categoria['slug'] !== 'ofertas')
            ?? $producto['categorias'][0]
            ?? null;

        return [
            'nombre' => $nombre,
            'slug' => (string) $producto['slug'],
            'descripcion' => $descripcion,
            'descripcion_meta' => $this->descripcionParaBuscadores($nombre, $descripcion),
            'disponible' => (bool) $producto['disponible'],
            'volver' => $categoria === null
                ? ['nombre' => 'Muebles', 'url' => route('catalogo.index')]
                : ['nombre' => (string) $categoria['nombre'], 'url' => route('catalogo.index', ['categoria' => $categoria['slug']])],
            'imagenes' => $imagenes,
            'opciones' => $variantes->map(fn (array $variante, int $indice): array => [
                'id' => (int) $variante['id'],
                'titulo' => $this->textoOpcional($variante['color']) ?? $this->textoOpcional($variante['etiqueta']) ?? 'Opción '.($indice + 1),
                'subtitulo' => $this->subtituloVariante($variante),
                'disponible' => (bool) $variante['disponible'],
                'seleccionada' => $seleccionada !== null && $seleccionada['id'] === $variante['id'],
            ])->all(),
            'mostrar_opciones' => $mostrarOpciones,
            'opcion_unica' => $mostrarOpciones ? null : (int) $variantes->first()['id'],
            'precio' => $precio,
            'precio_desde' => $precioDesde,
            'direccion_retiro' => $direccionRetiro,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $disponibles
     * @return ?array<string, mixed>
     */
    private function varianteSeleccionada(Collection $disponibles, ?int $solicitada): ?array
    {
        if ($solicitada !== null) {
            $variante = $disponibles->firstWhere('id', $solicitada);

            if ($variante !== null) {
                return $variante;
            }
        }

        return $disponibles->count() === 1 ? $disponibles->first() : null;
    }

    /**
     * @param  ?array<string, mixed>  $seleccionada
     * @param  Collection<int, array<string, mixed>>  $disponibles
     * @return array{0: ?string, 1: bool}
     */
    private function precio(?array $seleccionada, Collection $disponibles): array
    {
        if ($seleccionada !== null) {
            return [FormatoMoneda::formatear((string) $seleccionada['precio_publicado']), false];
        }

        if ($disponibles->isEmpty()) {
            return [null, false];
        }

        $precios = $disponibles
            ->map(fn (array $variante): string => (string) $variante['precio_publicado'])
            ->sort(fn (string $a, string $b): int => bccomp($a, $b, 2))
            ->values();

        return [FormatoMoneda::formatear($precios->first()), $precios->unique()->count() > 1];
    }

    /**
     * @param  array<string, mixed>  $variante
     */
    private function etiquetaVariante(array $variante): ?string
    {
        return $this->textoOpcional($variante['color']) ?? $this->textoOpcional($variante['etiqueta']);
    }

    /**
     * La etiqueta descriptiva solo aporta algo cuando el color ya figura como título.
     *
     * @param  array<string, mixed>  $variante
     */
    private function subtituloVariante(array $variante): ?string
    {
        $color = $this->textoOpcional($variante['color']);
        $etiqueta = $this->textoOpcional($variante['etiqueta']);

        return $color !== null && $etiqueta !== null && $etiqueta !== $color ? $etiqueta : null;
    }

    /**
     * @param  list<array{url: ?string, url_miniatura: ?string, principal: bool}>  $imagenes
     * @return list<array{url: string, url_miniatura: ?string, alt: string}>
     */
    private function imagenesConUrl(array $imagenes, string $alt): array
    {
        return collect($imagenes)
            ->filter(fn (array $imagen): bool => $imagen['url'] !== null)
            ->sortByDesc('principal')
            ->values()
            ->map(fn (array $imagen): array => [
                'url' => (string) $imagen['url'],
                'url_miniatura' => $imagen['url_miniatura'],
                'alt' => $alt,
            ])
            ->all();
    }

    private function descripcionParaBuscadores(string $nombre, ?string $descripcion): string
    {
        if ($descripcion === null) {
            return $nombre.' en El Refugio: '.self::DESCRIPCION_PREDETERMINADA;
        }

        $primeraFrase = preg_match('/^.*?[.!?](?=\s|$)/su', $descripcion, $coincidencias) === 1
            ? $coincidencias[0]
            : $descripcion;

        return Str::limit(trim($primeraFrase), 160);
    }

    private function textoOpcional(mixed $valor): ?string
    {
        $texto = $valor === null ? '' : trim((string) $valor);

        return $texto === '' ? null : $texto;
    }
}
