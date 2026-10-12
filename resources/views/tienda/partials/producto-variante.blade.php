{{-- Galería y precio de la variante elegida. La página del producto lo incluye y, con htmx, es lo único que cambia al elegir un color. --}}
@php
    $imagenPrincipal = $detalle['imagenes'][0] ?? null;
@endphp

<div data-galeria class="lg:row-span-3 lg:self-start">
    <div class="relative aspect-square overflow-hidden rounded-[10px] bg-humo">
        @if ($imagenPrincipal !== null)
            <img data-imagen-principal src="{{ $imagenPrincipal['url'] }}" alt="{{ $imagenPrincipal['alt'] }}" class="size-full object-cover">
        @else
            <p class="grid size-full place-items-center p-6 text-center text-lg text-tinta">Todavía no hay fotos de este mueble.</p>
        @endif
    </div>

    @if (count($detalle['imagenes']) > 1)
        <div class="mt-3 flex gap-2.5 overflow-x-auto pb-1">
            @foreach ($detalle['imagenes'] as $imagen)
                <button type="button" data-miniatura data-url="{{ $imagen['url'] }}" data-alt="{{ $imagen['alt'] }}" aria-label="Ver foto {{ $loop->iteration }} de {{ count($detalle['imagenes']) }}" @if ($loop->first) aria-current="true" @endif class="size-18 shrink-0 overflow-hidden rounded-lg border-2 border-linea bg-humo aria-[current=true]:border-[3px] aria-[current=true]:border-grafito">
                    <img src="{{ $imagen['url_miniatura'] ?? $imagen['url'] }}" alt="" loading="lazy" class="size-full object-cover">
                </button>
            @endforeach
        </div>
    @endif
</div>

<div class="relative min-w-0 sm:pr-52">
    <h1 id="nombre-producto" lang="es" class="condensada hyphens-auto break-words text-[44px] sm:text-[64px]">{{ $detalle['nombre'] }}</h1>

    <x-etiqueta :color="$detalle['disponible'] ? 'naranja' : 'grafito'" tamano="mediana" balanceo class="mt-2 ml-auto w-fit min-w-36 sm:absolute sm:right-0 sm:top-0 sm:mt-0 sm:min-w-44">
        @if ($detalle['disponible'])
            @if ($detalle['precio_desde'])
                <span class="text-sm font-extrabold uppercase">Desde</span>
            @endif
            <span aria-live="polite" class="condensada cifras text-[38px] sm:text-[44px]">{{ $detalle['precio'] }}</span>
            <span class="text-sm font-extrabold">POR UNIDAD</span>
        @else
            <span class="condensada text-3xl">Agotado</span>
        @endif
    </x-etiqueta>
</div>
