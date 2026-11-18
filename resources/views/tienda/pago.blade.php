@use('App\Servicios\Precios\FormatoMoneda')
@use('App\View\OpcionPagoVisible')
<x-layouts.tienda titulo="Pagá tu pedido">
    @if ($admitePago && $mercadoPagoConfigurado)
        @push('scripts')
            <script src="https://sdk.mercadopago.com/js/v2"></script>
            @vite('resources/js/pago.js')
        @endpush
    @endif

    <section aria-labelledby="titulo-pago" class="mx-auto max-w-2xl px-4 pb-24 pt-8 sm:px-10 sm:pt-12">
        <h1 id="titulo-pago" class="condensada text-[52px] sm:text-[72px]">Pagá tu pedido</h1>
        <p class="cifras mt-2 text-lg font-bold [overflow-wrap:anywhere]">{{ $resumen['numero'] }} · {{ $resumen['nombre_cliente'] }}</p>

        <section aria-labelledby="titulo-tu-pedido" class="mt-6 rounded-[10px] bg-niebla p-5 sm:p-6">
            <h2 id="titulo-tu-pedido" class="text-xl font-extrabold">Tu pedido</h2>

            <ul class="mt-4 flex flex-col gap-2.5 text-lg">
                @foreach ($resumen['items'] as $item)
                    <li class="flex items-baseline justify-between gap-3">
                        <span class="min-w-0 [overflow-wrap:anywhere]">{{ $item['cantidad'] }} × {{ $item['nombre_producto'] }}</span>
                        <span class="cifras shrink-0 font-bold">{{ FormatoMoneda::formatear((string) $item['subtotal']) }}</span>
                    </li>
                @endforeach

                @if ($resumen['costo_envio'] !== null)
                    <li class="flex items-baseline justify-between gap-3">
                        <span class="min-w-0 [overflow-wrap:anywhere]">Envío por expreso{{ $pedido->localidad_envio ? ' a '.$pedido->localidad_envio : '' }}</span>
                        <span class="cifras shrink-0 font-bold">{{ FormatoMoneda::formatear((string) $resumen['costo_envio']) }}</span>
                    </li>
                @endif
            </ul>

            <div class="mt-4 flex items-baseline justify-between gap-3 border-t-2 border-dashed border-linea pt-4 text-xl">
                <span class="font-black">Total</span>
                <span class="cifras font-black">{{ FormatoMoneda::formatear((string) $resumen['total_base']) }}</span>
            </div>
        </section>

        @if ($admitePago && $opciones === [])
            <div class="mt-10">
                <x-aviso tipo="error">Todavía no hay formas de pago disponibles para este pedido. Escribinos por WhatsApp para coordinarlo.</x-aviso>
            </div>
        @elseif ($admitePago)
            <form
                method="POST"
                action="{{ route('pago.intentos.crear', $token) }}"
                hx-post="{{ route('pago.intentos.crear', $token) }}"
                hx-target="#pago-intento"
                hx-swap="innerHTML"
                hx-sync="this:replace"
                hx-disabled-elt="find button[type=submit]"
                class="mt-10"
            >
                @csrf

                <fieldset class="min-w-0">
                    <legend class="condensada text-[40px] sm:text-[48px]">Elegí cómo pagar</legend>
                    <p class="mt-2 text-lg leading-relaxed text-tinta">Cada forma de pago tiene su precio final, porque incluye lo que cobra Mercado Pago por ese medio.</p>

                    <div class="mt-5 flex flex-col gap-3">
                        @foreach ($opciones as $opcion)
                            <label class="flex min-h-[72px] cursor-pointer items-center gap-4 rounded-[10px] border-2 border-linea bg-blanco p-4 transition-colors hover:border-grafito has-[:checked]:border-grafito has-[:checked]:bg-naranja-claro">
                                <input
                                    type="radio"
                                    name="regla_precio_pago_id"
                                    value="{{ $opcion['regla_id'] }}"
                                    required
                                    @checked($seleccionada === $opcion['regla_id'])
                                    class="size-[22px] shrink-0 accent-grafito"
                                >
                                <span class="flex min-w-0 flex-1 flex-col gap-0.5">
                                    <span class="text-lg font-extrabold">{{ OpcionPagoVisible::etiqueta($opcion['tipo_medio_pago'], $opcion['cuotas']) }}</span>
                                    <span class="cifras text-base text-tinta">{{ OpcionPagoVisible::detalle($opcion['cuotas'], $opcion['importe']) }}</span>
                                </span>
                                <span class="cifras shrink-0 text-xl font-black">{{ FormatoMoneda::formatear($opcion['importe']) }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <x-boton type="submit" variante="naranja" tamano="grande" class="mt-6 w-full">Continuar</x-boton>
            </form>
        @endif

        @if ($admitePago)
            <div id="pago-intento" class="mt-8">
                @if ($brick !== null)
                    @include('tienda.partials.pago-brick', ['brick' => $brick])
                @endif
            </div>
        @endif

        <div id="pago-resultado" aria-live="polite" class="mt-8">
            @if ($resultado !== null)
                @include('tienda.partials.pago-estado', $resultado)
            @endif
        </div>

        @if ($whatsappUrl !== null)
            <div class="mt-12 flex flex-col items-center gap-1 text-center">
                <p class="text-lg text-tinta">¿Dudas con el pago?</p>
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="inline-flex min-h-12 items-center gap-2.5 text-lg font-extrabold underline underline-offset-4">
                    <x-icono nombre="whatsapp" class="size-[22px]" />
                    Escribinos por WhatsApp
                </a>
            </div>
        @endif
    </section>
</x-layouts.tienda>
