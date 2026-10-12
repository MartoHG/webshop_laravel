<x-layouts.tienda :titulo="$titulo">
    <section aria-labelledby="titulo-catalogo" class="mx-auto max-w-7xl px-4 pb-16 pt-10 sm:px-10 sm:pb-24 sm:pt-16">
        <h1 id="titulo-catalogo" class="condensada text-[52px] sm:text-[80px]">{{ $titulo }}</h1>

        <form id="buscar" action="{{ route('catalogo.index') }}" method="GET" role="search" class="mt-8 scroll-mt-24 sm:mt-10 sm:max-w-3xl">
            @if ($categoriaActiva !== null)
                <input type="hidden" name="categoria" value="{{ $categoriaActiva['slug'] }}">
            @endif

            <label for="buscar-texto" class="mb-3 block text-lg font-extrabold">Buscar muebles</label>
            <div class="flex flex-col gap-3 sm:flex-row">
                <input id="buscar-texto" type="search" name="buscar" value="{{ $buscar }}" maxlength="160" autocomplete="off" placeholder="Por ejemplo: reposera, sillón, mesa" class="min-h-14 w-full rounded-md border-2 border-grafito bg-blanco px-4 text-lg text-grafito placeholder:text-tinta-tenue">
                <x-boton type="submit" class="w-full sm:w-auto">
                    <x-icono nombre="buscar" class="size-5" />
                    Buscar
                </x-boton>
            </div>
        </form>

        <nav aria-label="Categorías" class="-mx-4 mt-8">
            <ul class="flex gap-2.5 overflow-x-auto px-4 pb-2 sm:mx-0 sm:flex-wrap sm:px-0">
                <li class="shrink-0">
                    <a href="{{ route('catalogo.index') }}" @if ($categoriaActiva === null) aria-current="page" @endif class="inline-flex min-h-11 items-center rounded-full border-2 px-5 text-base font-bold no-underline transition-colors aria-[current=page]:border-grafito aria-[current=page]:bg-grafito aria-[current=page]:text-blanco border-linea bg-blanco text-grafito hover:border-grafito">Todos</a>
                </li>
                @foreach ($categorias as $categoria)
                    <li class="shrink-0">
                        <a href="{{ route('catalogo.index', ['categoria' => $categoria['slug']]) }}" @if ($categoriaActiva !== null && $categoriaActiva['slug'] === $categoria['slug']) aria-current="page" @endif class="inline-flex min-h-11 items-center rounded-full border-2 px-5 text-base font-bold no-underline transition-colors aria-[current=page]:border-grafito aria-[current=page]:bg-grafito aria-[current=page]:text-blanco border-linea bg-blanco text-grafito hover:border-grafito">{{ $categoria['nombre'] }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>

        @if ($productos->isEmpty())
            <div class="mt-12 flex flex-col items-start gap-5 rounded-[10px] bg-niebla p-6 sm:p-10">
                @if ($buscar !== '')
                    <p class="text-xl font-bold">No encontramos muebles que coincidan con «{{ $buscar }}».</p>
                @else
                    <p class="text-xl font-bold">Por ahora no hay muebles en esta categoría.</p>
                @endif
                <p class="text-lg text-tinta">Probá con otra palabra o mirá todos los muebles.</p>
                <x-boton :href="route('catalogo.index')">Ver todos los muebles</x-boton>
            </div>
        @else
            <ul class="mt-10 grid grid-cols-2 gap-x-3.5 gap-y-10 md:grid-cols-3 lg:grid-cols-4 lg:gap-x-7 lg:gap-y-12">
                @foreach ($productos as $producto)
                    <li>
                        <x-tienda.tarjeta-producto :producto="$producto" />
                    </li>
                @endforeach
            </ul>

            <x-tienda.paginacion :paginador="$productos" class="mt-14" />
        @endif
    </section>
</x-layouts.tienda>
