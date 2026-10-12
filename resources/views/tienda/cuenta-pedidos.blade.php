@use('App\Enums\EstadoPago')
@use('App\Servicios\Contenido\ContactoService')
@use('App\Servicios\Precios\FormatoMoneda')
@use('App\View\EstadoVisiblePedido')
<x-layouts.tienda titulo="Mis pedidos">
    <section aria-labelledby="titulo-pedidos" class="mx-auto max-w-3xl px-4 pb-24 pt-8 sm:px-10 sm:pt-14">
        <a href="{{ route('cuenta.mostrar') }}" class="inline-flex min-h-11 items-center gap-1.5 text-lg font-bold no-underline hover:underline">
            <x-icono nombre="volver" class="size-5" />
            Mi cuenta
        </a>
        <h1 id="titulo-pedidos" class="condensada mt-3 text-[52px] sm:text-[64px]">Mis pedidos</h1>

        @if ($pedidos->isEmpty())
            <div class="mt-8 flex flex-col items-start gap-5 rounded-[10px] bg-niebla p-6 sm:p-10">
                <p class="text-xl font-bold">Todavía no hiciste ningún pedido.</p>
                <p class="text-lg text-tinta">Cuando armes un pedido, lo vas a ver acá con su estado.</p>
                <x-boton :href="route('catalogo.index')">Ver muebles</x-boton>
            </div>
        @else
            <ul class="mt-8 flex flex-col gap-4">
                @foreach ($pedidos as $pedido)
                    @php
                        $estado = EstadoVisiblePedido::paraPedido($pedido);
                        $fecha = $pedido->created_at->timezone(ContactoService::ZONA_HORARIA)->format('d/m/Y');
                        $puedePagar = $pedido->estado_pago === EstadoPago::Habilitado && $pedido->token_pago !== null;
                    @endphp

                    <li class="min-w-0 rounded-[10px] bg-blanco p-5 shadow-[0_2px_6px_rgba(38,40,43,0.06)]">
                        <div class="flex min-w-0 flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <a href="{{ route('cuenta.pedidos.mostrar', $pedido->numero) }}" class="cifras inline-flex min-h-11 items-center break-all text-xl font-extrabold no-underline hover:underline">{{ $pedido->numero }}</a>
                                <p class="text-base text-tinta">Pedido del {{ $fecha }}</p>
                            </div>

                            <x-etiqueta :color="$estado['color']" tamano="chica" :hilo="0">
                                <span class="text-base font-extrabold leading-tight">{{ $estado['etiqueta'] }}</span>
                            </x-etiqueta>
                        </div>

                        <p class="mt-3 text-base leading-snug">{{ $estado['descripcion'] }}</p>

                        <div class="mt-4 flex flex-wrap items-end justify-between gap-4 border-t-2 border-humo pt-4">
                            <div class="min-w-0">
                                <p class="text-base font-bold text-tinta">Total</p>
                                @if ($pedido->total_base !== null)
                                    <p class="cifras text-2xl font-extrabold">{{ FormatoMoneda::formatear((string) $pedido->total_base) }}</p>
                                @else
                                    <p class="text-lg">
                                        <span class="cifras font-extrabold">{{ FormatoMoneda::formatear((string) $pedido->subtotal_productos) }}</span>
                                        <span class="text-base text-tinta">+ envío a coordinar</span>
                                    </p>
                                @endif
                            </div>

                            @if ($puedePagar)
                                <x-boton :href="route('pago.mostrar', $pedido->token_pago)" variante="naranja" class="w-full sm:w-auto">
                                    Pagar
                                    <span class="sr-only">el pedido {{ $pedido->numero }}</span>
                                </x-boton>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>

            <x-tienda.paginacion :paginador="$pedidos" class="mt-10" />
        @endif
    </section>
</x-layouts.tienda>
