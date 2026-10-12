{{-- Contenido del carrito. Lo usan la página completa y, con htmx, la respuesta de cada cambio. Los formularios apuntan a #carrito-contenido. --}}
@php
    $hayNoDisponibles = collect($carrito['items'])->contains('disponible', false);
@endphp

<div id="carrito-contenido" class="grid grid-cols-[minmax(0,1fr)] gap-6 lg:grid-cols-[minmax(0,1fr)_340px] lg:items-start lg:gap-x-10">
    @if ($carrito['items'] === [])
        <div class="flex flex-col items-start gap-5 rounded-[10px] bg-niebla p-6 sm:p-10 lg:col-span-2">
            <p class="text-xl font-bold">Tu carrito está vacío.</p>
            <p class="text-lg text-tinta">Cuando elijas un mueble, lo vas a ver acá. Todavía no pagás nada.</p>
            <x-boton :href="route('catalogo.index')">Ver muebles</x-boton>
        </div>
    @else
        <div class="flex flex-col gap-4 lg:col-span-2">
            <p class="cifras text-lg text-tinta">{{ $carrito['cantidad_unidades'] }} {{ $carrito['cantidad_unidades'] === 1 ? 'unidad' : 'unidades' }}</p>

            @foreach ($errores as $error)
                <x-aviso tipo="error">{{ $error }}</x-aviso>
            @endforeach

            @if ($hayNoDisponibles)
                <x-aviso tipo="error">Hay productos que ya no están disponibles. Quitalos para continuar.</x-aviso>
            @endif
        </div>

        <ul class="flex flex-col gap-3.5">
            @foreach ($carrito['items'] as $item)
                @php
                    $nombre = $item['nombre_producto'] ?? 'Mueble que ya no está en la tienda';
                    $etiqueta = collect([$item['color'], $item['etiqueta']])->filter()->unique()->implode(' · ');
                @endphp

                <li class="flex gap-3.5 rounded-[10px] bg-blanco p-3.5 shadow-[0_2px_6px_rgba(38,40,43,0.06)] sm:gap-5 sm:p-4">
                    <div class="size-24 shrink-0 overflow-hidden rounded-lg bg-humo sm:size-28">
                        @if ($item['imagen_miniatura'] !== null)
                            <img src="{{ $item['imagen_miniatura'] }}" alt="" loading="lazy" class="size-full object-cover {{ $item['disponible'] ? '' : 'grayscale' }}">
                        @endif
                    </div>

                    <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                        <h2 class="text-lg font-extrabold leading-tight">
                            @if ($item['slug'] !== null)
                                <a href="{{ route('catalogo.mostrar', $item['slug']) }}" class="no-underline hover:underline">{{ $nombre }}</a>
                            @else
                                {{ $nombre }}
                            @endif
                        </h2>

                        @if ($etiqueta !== '')
                            <p class="text-base text-tinta">{{ $etiqueta }}</p>
                        @endif

                        @if ($item['precio_unitario'] !== null)
                            <p class="text-base text-tinta"><span class="cifras">{{ \App\Servicios\Precios\FormatoMoneda::formatear($item['precio_unitario']) }}</span> c/u</p>
                        @endif

                        <div class="mt-auto flex flex-wrap items-center justify-between gap-3 pt-2">
                            @if ($item['disponible'])
                                <div class="inline-flex h-11 items-center rounded-md border-2 border-grafito">
                                    <form method="POST" action="{{ route('carrito.items.actualizar', $item['variante_producto_id']) }}" hx-target="#carrito-contenido" hx-swap="outerHTML">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="cantidad" value="{{ $item['cantidad'] - 1 }}">
                                        <button type="submit" aria-label="Restar uno a {{ $nombre }}" @disabled($item['cantidad'] <= 1) class="grid size-11 place-items-center text-2xl font-bold leading-none hover:bg-niebla disabled:cursor-not-allowed disabled:opacity-40">−</button>
                                    </form>

                                    <span class="grid w-10 place-items-center text-lg font-extrabold cifras"><span class="sr-only">Cantidad: </span>{{ $item['cantidad'] }}</span>

                                    <form method="POST" action="{{ route('carrito.items.actualizar', $item['variante_producto_id']) }}" hx-target="#carrito-contenido" hx-swap="outerHTML">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="cantidad" value="{{ $item['cantidad'] + 1 }}">
                                        <button type="submit" aria-label="Sumar uno a {{ $nombre }}" @disabled($item['cantidad'] >= 99) class="grid size-11 place-items-center text-2xl font-bold leading-none hover:bg-niebla disabled:cursor-not-allowed disabled:opacity-40">+</button>
                                    </form>
                                </div>

                                <span class="cifras text-lg font-extrabold">{{ \App\Servicios\Precios\FormatoMoneda::formatear((string) $item['subtotal']) }}</span>
                            @else
                                <span class="inline-flex min-h-9 items-center rounded-full bg-grafito px-3.5 py-1 text-sm font-extrabold uppercase tracking-wide text-blanco">{{ $item['motivo'] === 'agotado' ? 'Agotado' : 'No disponible' }}</span>
                                <span class="text-base font-semibold text-tinta">No suma al total</span>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('carrito.items.quitar', $item['variante_producto_id']) }}" hx-target="#carrito-contenido" hx-swap="outerHTML" class="self-start">
                            @csrf
                            @method('DELETE')
                            <button type="submit" aria-label="Quitar {{ $nombre }} del carrito" class="inline-flex min-h-11 items-center text-base font-bold text-tinta underline underline-offset-4 hover:text-grafito">Quitar</button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="flex flex-col gap-5">
            <section aria-label="Total" class="rounded-[10px] bg-blanco p-5 shadow-[0_2px_6px_rgba(38,40,43,0.06)]">
                <div class="flex items-baseline justify-between gap-3 text-lg">
                    <span class="font-bold">Productos</span>
                    <span class="cifras font-extrabold">{{ \App\Servicios\Precios\FormatoMoneda::formatear($carrito['subtotal']) }}</span>
                </div>
                <div class="mt-2.5 flex items-baseline justify-between gap-3 text-base text-tinta">
                    <span>Envío</span>
                    <span class="text-right">Lo acordamos por WhatsApp</span>
                </div>

                @if ($carrito['puede_continuar'])
                    <x-boton :href="route('checkout.resumen')" tamano="grande" class="mt-5 w-full">
                        Continuar con el pedido
                        <x-icono nombre="flecha" class="size-5" />
                    </x-boton>
                @else
                    <x-boton type="button" tamano="grande" class="mt-5 w-full" :disabled="true">
                        Continuar con el pedido
                        <x-icono nombre="flecha" class="size-5" />
                    </x-boton>
                @endif

                <p class="mt-3 text-center text-base text-tinta">Todavía no pagás nada.</p>
            </section>

            @if ($hayRetiro)
                <section aria-labelledby="titulo-retiro" class="rounded-[10px] bg-naranja p-5 sm:p-6">
                    <h2 id="titulo-retiro" class="condensada text-[32px]">¿Lo retirás en el local?</h2>
                    <p class="mt-3 text-base font-medium leading-normal">Mandanos este carrito por WhatsApp y coordinamos el retiro en {{ $direccionRetiro ?? 'el local de Olavarría' }}.</p>
                    <x-boton :href="route('carrito.retiro')" variante="contorno" target="_blank" rel="noopener" class="mt-4 w-full">
                        <x-icono nombre="whatsapp" class="size-[22px]" />
                        Retirar en el local
                        <span class="sr-only">(se abre WhatsApp en otra pestaña)</span>
                    </x-boton>
                </section>
            @endif

            <div class="text-center">
                <a href="{{ route('catalogo.index') }}" class="inline-flex min-h-11 items-center text-lg font-bold underline underline-offset-4">Seguir mirando muebles</a>
            </div>
        </div>
    @endif
</div>
