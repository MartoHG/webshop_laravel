<x-layouts.tienda :titulo="$detalle['nombre']" :descripcion="$detalle['descripcion_meta']">
    <div class="mx-auto max-w-7xl px-4 pb-20 pt-4 sm:px-10 sm:pt-8">
        <a href="{{ $detalle['volver']['url'] }}" class="inline-flex min-h-11 items-center gap-1.5 text-lg font-bold no-underline hover:underline">
            <x-icono nombre="volver" class="size-[22px]" />
            {{ $detalle['volver']['nombre'] }}
        </a>

        <div class="mt-5 grid grid-cols-[minmax(0,1fr)] gap-8 lg:grid-cols-2 lg:gap-x-16 lg:gap-y-10">
            <div id="producto-variante" class="contents">
                @include('tienda.partials.producto-variante')
            </div>

            <form action="{{ route('carrito.items.agregar') }}" method="POST" class="flex flex-col gap-8 lg:self-start">
                @csrf

                @if ($detalle['mostrar_opciones'])
                    <fieldset class="m-0 min-w-0 border-0 p-0">
                        <legend class="mb-4 text-lg font-extrabold">Elegí el color</legend>
                        <div class="flex flex-wrap gap-3">
                            @foreach ($detalle['opciones'] as $opcion)
                                <label class="group relative cursor-pointer rounded-[10px] has-[:disabled]:cursor-not-allowed has-[:focus-visible]:outline-3 has-[:focus-visible]:outline-offset-3 has-[:focus-visible]:outline-celeste">
                                    <input type="radio" name="variante_producto_id" value="{{ $opcion['id'] }}" required class="peer sr-only" @checked($opcion['seleccionada']) @disabled(! $opcion['disponible'])
                                        hx-get="{{ route('catalogo.mostrar', $detalle['slug']) }}?variante={{ $opcion['id'] }}"
                                        hx-trigger="change"
                                        hx-target="#producto-variante"
                                        hx-swap="innerHTML"
                                        hx-replace-url="true">
                                    <span class="flex min-h-24 w-36 flex-col items-center justify-center gap-1 bg-humo px-4 py-3 text-center text-grafito recorte-etiqueta-chica transition-colors peer-checked:bg-grafito peer-checked:text-blanco peer-disabled:bg-niebla peer-disabled:text-tinta-tenue sm:w-40">
                                        <x-icono nombre="check" class="hidden size-5 group-has-[:checked]:block" />
                                        <span class="text-lg font-extrabold leading-tight">{{ $opcion['titulo'] }}</span>
                                        @if ($opcion['subtitulo'] !== null)
                                            <span class="text-sm font-semibold">{{ $opcion['subtitulo'] }}</span>
                                        @endif
                                    </span>
                                    @unless ($opcion['disponible'])
                                        <span class="absolute -right-2 -top-3 rotate-[8deg] rounded-full bg-grafito px-3 py-1 text-sm font-extrabold uppercase tracking-wide text-blanco">Agotado</span>
                                    @endunless
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @else
                    <input type="hidden" name="variante_producto_id" value="{{ $detalle['opcion_unica'] }}">
                @endif

                <div>
                    <label for="cantidad" class="mb-4 block text-lg font-extrabold">Cantidad</label>
                    <div data-cantidad class="inline-flex h-14 items-center rounded-md border-2 border-grafito">
                        <button type="button" data-paso="-1" aria-label="Restar uno" @disabled(! $detalle['disponible']) class="grid size-14 place-items-center text-3xl font-bold leading-none hover:bg-niebla disabled:cursor-not-allowed disabled:opacity-50">−</button>
                        <input id="cantidad" type="number" name="cantidad" value="1" min="1" max="99" step="1" inputmode="numeric" @disabled(! $detalle['disponible']) class="h-full w-16 border-0 bg-transparent text-center text-xl font-extrabold text-grafito [appearance:textfield] disabled:opacity-50 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                        <button type="button" data-paso="1" aria-label="Sumar uno" @disabled(! $detalle['disponible']) class="grid size-14 place-items-center text-3xl font-bold leading-none hover:bg-niebla disabled:cursor-not-allowed disabled:opacity-50">+</button>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    <x-boton type="submit" tamano="grande" class="w-full" :disabled="! $detalle['disponible']">
                        <x-icono nombre="carrito" class="size-[22px]" />
                        Agregar al carrito
                    </x-boton>

                    @unless ($detalle['disponible'])
                        <p class="text-lg font-bold">Está agotado por ahora. Si querés saber cuándo vuelve, escribinos por WhatsApp.</p>
                    @endunless
                </div>
            </form>

            <section aria-label="Envío y retiro" class="flex flex-col gap-4 self-start rounded-[10px] bg-amarillo p-5 sm:p-6">
                <div class="flex items-start gap-3.5">
                    <x-icono nombre="envio" class="mt-0.5 size-[26px]" />
                    <p class="text-base leading-normal"><strong class="font-extrabold">Envío a todo el país.</strong> El costo del expreso lo acordamos por WhatsApp antes de que pagues.</p>
                </div>
                <div class="flex items-start gap-3.5">
                    <x-icono nombre="local" class="mt-0.5 size-[26px]" />
                    <p class="text-base leading-normal"><strong class="font-extrabold">Retiro en el local.</strong> {{ $detalle['direccion_retiro'] ?? 'Te esperamos en Olavarría.' }}</p>
                </div>
            </section>
        </div>

        @if ($detalle['descripcion'] !== null)
            <section aria-labelledby="titulo-detalle" class="mt-14 max-w-3xl border-t-2 border-humo pt-10">
                <h2 id="titulo-detalle" class="mb-4 text-2xl font-extrabold">Sobre este producto</h2>
                <p class="whitespace-pre-line text-lg leading-relaxed text-tinta">{{ $detalle['descripcion'] }}</p>
            </section>
        @endif
    </div>
</x-layouts.tienda>
