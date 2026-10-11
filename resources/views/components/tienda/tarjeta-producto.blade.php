<a href="{{ $url }}" {{ $attributes->class('group relative flex flex-col rounded-[10px] bg-blanco text-grafito no-underline shadow-[0_2px_6px_rgba(38,40,43,0.06)]') }}>
    <div class="relative aspect-square overflow-hidden rounded-t-[10px] bg-humo">
        @if ($imagen)
            <img src="{{ $imagen }}" alt="" loading="lazy" class="size-full object-cover transition-transform duration-500 ease-out group-hover:scale-[1.03] {{ $agotado ? 'grayscale-[0.35]' : '' }}">
        @endif

        @if ($enOferta && ! $agotado)
            <x-sello class="absolute bottom-2 left-2 size-14 sm:bottom-auto sm:left-4 sm:top-4 sm:size-[72px]" :rotacion="-10">
                <span class="condensada text-base sm:text-xl">Oferta</span>
            </x-sello>
        @endif
    </div>

    <x-etiqueta
        :color="$agotado ? 'grafito' : 'naranja'"
        tamano="mediana"
        class="absolute -top-3 right-2 min-w-24 origin-[50%_-18px] transition-transform duration-500 ease-out group-hover:-rotate-[4deg] sm:right-[18px] sm:min-w-32"
    >
        @if ($agotado)
            <span class="condensada text-[22px] sm:text-3xl">Agotado</span>
        @else
            @if ($precioDesde)
                <span class="text-[11px] font-extrabold uppercase tracking-wide">Desde</span>
            @endif
            <span class="condensada cifras text-[22px] sm:text-[34px]">{{ $precio }}</span>
        @endif
    </x-etiqueta>

    <div class="flex flex-col gap-1 px-3 pb-4 pt-3 sm:px-5 sm:pb-6 sm:pt-5">
        <h3 class="text-base font-bold leading-tight sm:text-xl">{{ $nombre }}</h3>
        @if ($colores !== '')
            <p class="text-sm text-tinta sm:text-base">{{ $colores }}</p>
        @endif
    </div>
</a>
