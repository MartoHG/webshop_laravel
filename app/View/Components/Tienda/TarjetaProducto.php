<?php

namespace App\View\Components\Tienda;

use App\Servicios\Precios\FormatoMoneda;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TarjetaProducto extends Component
{
    public string $nombre;

    public string $url;

    public ?string $imagen;

    public ?string $precio;

    public bool $precioDesde;

    public bool $agotado;

    public bool $enOferta;

    public string $colores;

    /**
     * Arma la tarjeta a partir del producto tal como lo representa el catálogo público.
     *
     * @param  array{nombre: string, slug: string, disponible: bool, categorias: list<array{nombre: string, slug: string}>, imagenes: list<array{url: ?string, url_miniatura: ?string, principal: bool}>, variantes: list<array{color: ?string, disponible: bool, precio_publicado: string}>}  $producto
     */
    public function __construct(array $producto)
    {
        $this->nombre = $producto['nombre'];
        $this->url = route('catalogo.mostrar', $producto['slug']);

        $imagenes = collect($producto['imagenes']);
        $imagen = $imagenes->firstWhere('principal', true) ?? $imagenes->first();
        $this->imagen = $imagen['url_miniatura'] ?? $imagen['url'] ?? null;

        $variantes = collect($producto['variantes']);
        $disponibles = $variantes->where('disponible', true);
        $precios = $disponibles->pluck('precio_publicado')
            ->sort(fn (string $a, string $b): int => bccomp($a, $b, 2))
            ->values();

        $this->agotado = ! $producto['disponible'] || $precios->isEmpty();
        $this->precio = $this->agotado ? null : FormatoMoneda::formatear($precios->first());
        $this->precioDesde = ! $this->agotado && $precios->unique()->count() > 1;
        $this->enOferta = collect($producto['categorias'])->contains('slug', 'ofertas');
        $this->colores = $variantes->pluck('color')->filter()->unique()->implode(' · ');
    }

    public function render(): View
    {
        return view('components.tienda.tarjeta-producto');
    }
}
