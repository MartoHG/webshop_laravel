@props(['paginador'])

@if ($paginador->hasPages())
    <nav aria-label="Páginas de muebles" {{ $attributes->class('flex items-center justify-between gap-3 sm:justify-center sm:gap-8') }}>
        @if ($paginador->onFirstPage())
            <span aria-disabled="true" class="inline-flex min-h-14 min-w-36 items-center justify-center gap-2 rounded-md border-2 border-linea px-5 text-lg font-bold text-campo">
                <x-icono nombre="volver" class="size-5" />
                Anterior
            </span>
        @else
            <a href="{{ $paginador->previousPageUrl() }}" rel="prev" class="inline-flex min-h-14 min-w-36 items-center justify-center gap-2 rounded-md border-2 border-grafito px-5 text-lg font-bold no-underline hover:bg-niebla">
                <x-icono nombre="volver" class="size-5" />
                Anterior
            </a>
        @endif

        <p class="cifras text-center text-lg font-bold">Página {{ $paginador->currentPage() }} de {{ $paginador->lastPage() }}</p>

        @if ($paginador->hasMorePages())
            <a href="{{ $paginador->nextPageUrl() }}" rel="next" class="inline-flex min-h-14 min-w-36 items-center justify-center gap-2 rounded-md border-2 border-grafito px-5 text-lg font-bold no-underline hover:bg-niebla">
                Siguiente
                <x-icono nombre="flecha" class="size-5" />
            </a>
        @else
            <span aria-disabled="true" class="inline-flex min-h-14 min-w-36 items-center justify-center gap-2 rounded-md border-2 border-linea px-5 text-lg font-bold text-campo">
                Siguiente
                <x-icono nombre="flecha" class="size-5" />
            </span>
        @endif
    </nav>
@endif
