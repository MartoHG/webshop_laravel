@php
    $coloresCategoria = ['naranja', 'celeste', 'amarillo', 'grafito'];
@endphp

<x-layouts.tienda>
    @if ($portada)
        <section aria-label="Portada" class="relative h-[600px] overflow-hidden bg-[#bfd9e2] sm:h-[680px]">
            <img src="{{ $portada['imagen_url'] }}" alt="" class="absolute inset-0 size-full object-cover object-[60%_55%]">

            <x-etiqueta tamano="grande" balanceo class="absolute inset-x-4 top-0 sm:inset-x-auto sm:left-[7%] sm:w-[540px]" cuerpo="items-start! shadow-[0_18px_40px_rgba(38,40,43,0.28)]">
                <h1 class="condensada text-[50px] sm:text-[78px]">{{ $portada['titulo'] }}</h1>
                @if ($portada['subtitulo'])
                    <p class="max-w-[30ch] text-[17px] font-medium leading-snug sm:text-xl">{{ $portada['subtitulo'] }}</p>
                @endif
                @if ($portada['boton_texto'])
                    <div class="mt-1.5 flex w-full flex-wrap items-center gap-x-5 gap-y-3">
                        <x-boton :href="$portada['boton_destino']" class="w-full sm:w-auto">
                            {{ $portada['boton_texto'] }}
                            <x-icono nombre="flecha" class="size-5" />
                        </x-boton>
                        @if ($contacto['whatsapp_url'])
                            <a href="{{ $contacto['whatsapp_url'] }}" target="_blank" rel="noopener" class="hidden text-[17px] font-bold sm:inline">Consultar por WhatsApp</a>
                        @endif
                    </div>
                @endif
            </x-etiqueta>

            @if ($contacto['abierto_ahora'] === true)
                <x-sello class="absolute bottom-5 right-4 size-[104px] sm:bottom-12 sm:right-[6%] sm:size-[150px]">
                    <span class="condensada text-[21px] sm:text-3xl">Abierto<br>ahora</span>
                    @if ($contacto['direccion'])
                        <span class="mt-1.5 hidden text-[13px] font-bold sm:block">{{ \Illuminate\Support\Str::before($contacto['direccion'], ',') }}</span>
                    @endif
                </x-sello>
            @endif
        </section>
    @endif

    @if ($beneficios !== [])
        <section aria-label="Beneficios" class="bg-amarillo">
            <ul class="mx-auto grid max-w-7xl grid-cols-2 gap-x-3.5 gap-y-5 px-4 py-7 sm:px-10 lg:grid-cols-4 lg:gap-7 lg:py-9">
                @foreach ($beneficios as $beneficio)
                    <li class="flex flex-col gap-2.5 sm:flex-row sm:items-center sm:gap-4">
                        <span class="grid size-12 shrink-0 place-items-center rounded-full bg-grafito text-amarillo sm:size-16">
                            <x-icono :nombre="$beneficio['icono']" class="size-6 sm:size-[30px]" />
                        </span>
                        <p class="text-base font-bold leading-tight sm:text-lg">{{ $beneficio['texto'] }}</p>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($categorias !== [])
        <section id="muebles" aria-labelledby="titulo-categorias" class="py-14 sm:pb-[72px] sm:pt-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-10">
                <div class="mb-8 flex flex-wrap items-end justify-between gap-6 sm:mb-10">
                    <h2 id="titulo-categorias" class="condensada text-[44px] sm:text-[64px]">¿Qué estás buscando?</h2>
                    <a href="{{ route('catalogo.index') }}" class="hidden text-lg font-bold sm:inline">Ver todos los muebles</a>
                </div>
                <ul class="grid grid-cols-2 gap-x-3.5 gap-y-8 lg:grid-cols-4 lg:gap-7">
                    @foreach ($categorias as $categoria)
                        <li>
                            <a href="{{ route('catalogo.index', ['categoria' => $categoria['slug']]) }}" class="group relative block no-underline">
                                <div class="aspect-[4/5] overflow-hidden rounded-[10px] bg-humo">
                                    @if ($categoria['imagen_url'])
                                        <img src="{{ $categoria['imagen_miniatura_url'] ?? $categoria['imagen_url'] }}" alt="" loading="lazy" class="size-full object-cover transition-transform duration-500 ease-out group-hover:scale-[1.03]">
                                    @endif
                                </div>
                                <x-etiqueta :color="$coloresCategoria[$loop->index % 4]" class="absolute -top-2 left-3 max-w-[85%] sm:-top-2.5 sm:left-[22px]">
                                    <span class="condensada text-center text-[21px] sm:text-[28px]">{{ $categoria['nombre'] }}</span>
                                </x-etiqueta>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    @if ($ofertas !== [])
        <section aria-labelledby="titulo-ofertas" class="bg-celeste py-12 sm:pb-24 sm:pt-[88px]">
            <div class="mx-auto max-w-7xl px-4 sm:px-10">
                <div class="mb-10 flex flex-wrap items-end justify-between gap-6 sm:mb-14">
                    <h2 id="titulo-ofertas" class="condensada text-[44px] sm:text-[64px]">Ofertas de temporada</h2>
                    <a href="{{ route('catalogo.index', ['categoria' => 'ofertas']) }}" class="hidden text-lg font-bold sm:inline">Ver todas las ofertas</a>
                </div>
                <div class="grid grid-cols-2 gap-x-3.5 gap-y-10 lg:grid-cols-4 lg:gap-x-7 lg:gap-y-12">
                    @foreach ($ofertas as $producto)
                        <x-tienda.tarjeta-producto :producto="$producto" />
                    @endforeach
                </div>
                <x-boton :href="route('catalogo.index', ['categoria' => 'ofertas'])" class="mt-8 w-full sm:hidden">Ver todas las ofertas</x-boton>
            </div>
        </section>
    @endif

    <section id="como-comprar" aria-labelledby="titulo-como" class="py-14 sm:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-10">
            <h2 id="titulo-como" class="condensada mb-8 text-[44px] sm:mb-12 sm:text-[64px]">Comprar es fácil</h2>
            <ol class="grid gap-7 lg:grid-cols-3">
                @foreach ([
                    ['naranja', 'Elegís y armás tu pedido', 'Agregás los muebles al carrito y confirmás el pedido. Todavía no pagás nada.'],
                    ['celeste', 'Acordamos el envío por WhatsApp', 'Te escribimos, cotizamos el expreso a tu ciudad y te pasamos el total.'],
                    ['amarillo', 'Pagás con Mercado Pago', 'Te llega un link para pagar con tarjeta de crédito, débito o dinero en cuenta.'],
                ] as [$color, $titulo, $texto])
                    <li class="flex items-start gap-4 sm:gap-5">
                        <x-etiqueta :color="$color" :hilo="0" class="w-[58px] shrink-0 sm:w-[72px]" cuerpo="h-[74px] justify-end sm:h-[92px]">
                            <span class="condensada text-4xl sm:text-[44px]">{{ $loop->iteration }}</span>
                        </x-etiqueta>
                        <div>
                            <h3 class="mb-1.5 mt-0.5 text-[19px] font-extrabold sm:mb-2 sm:mt-1 sm:text-[22px]">{{ $titulo }}</h3>
                            <p class="text-base leading-normal text-tinta sm:text-lg">{{ $texto }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <section id="retiro" aria-labelledby="titulo-retiro" class="bg-naranja">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-7 px-4 py-12 sm:gap-10 sm:px-10 sm:py-[72px]">
            <div class="max-w-[720px]">
                <h2 id="titulo-retiro" class="condensada text-[46px] sm:text-[72px]">¿Sos de Olavarría o la zona?</h2>
                <p class="mt-3.5 text-lg font-medium leading-snug sm:mt-4 sm:text-[22px]">
                    Armá el carrito y coordiná el retiro en el local{{ $contacto['direccion'] ? ' de '.$contacto['direccion'] : '' }}. Te esperamos para que pruebes los muebles.
                </p>
            </div>
            <x-boton :href="route('carrito.mostrar')" tamano="grande" class="w-full sm:w-auto">
                <x-icono nombre="carrito" class="size-6" />
                Ir al carrito para retirar
            </x-boton>
        </div>
    </section>
</x-layouts.tienda>
