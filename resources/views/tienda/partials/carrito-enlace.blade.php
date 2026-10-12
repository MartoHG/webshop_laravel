{{-- Enlace del carrito del encabezado. Lo usa el layout y, con hx-swap-oob, las respuestas htmx del carrito. --}}
<a id="carrito-enlace" @if ($oob ?? false) hx-swap-oob="true" @endif href="{{ route('carrito.mostrar') }}" aria-label="Carrito, {{ $unidades }} {{ $unidades === 1 ? 'unidad' : 'unidades' }}" class="relative flex h-12 items-center gap-2.5 rounded-md px-3 font-extrabold no-underline sm:bg-naranja sm:px-4 sm:text-grafito sm:hover:bg-[#e3551a]">
    <x-icono nombre="carrito" class="size-6 sm:size-[22px]" />
    <span class="hidden sm:inline">Carrito · {{ $unidades }}</span>
    <span aria-hidden="true" class="absolute right-0.5 top-1.5 grid h-5 min-w-5 place-items-center rounded-full bg-naranja px-1 text-xs font-black text-grafito sm:hidden">{{ $unidades }}</span>
</a>
