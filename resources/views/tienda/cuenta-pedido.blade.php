@use('App\Enums\EstadoPedido')
@use('App\Enums\MotivoCancelacion')
@use('App\Servicios\Contenido\ContactoService')
@use('App\Servicios\Precios\FormatoMoneda')
@use('App\View\EstadoVisiblePedido')
<x-layouts.tienda :titulo="'Pedido '.$pedido->numero">
    @php
        $estado = EstadoVisiblePedido::paraPedido($pedido);
        $motivo = EstadoVisiblePedido::motivoCancelacion($pedido);
        $cancelado = $pedido->estado_pedido === EstadoPedido::Cancelado;
        $puedePagar = $pedido->estado_pago === \App\Enums\EstadoPago::Habilitado && $pedido->token_pago !== null;
        $fecha = $pedido->created_at->timezone(ContactoService::ZONA_HORARIA)->format('d/m/Y');
        $total = $pedido->total_base ?? $pedido->subtotal_productos;
        $coloresEstado = [
            'naranja' => 'bg-naranja text-grafito',
            'celeste' => 'bg-celeste text-grafito',
            'amarillo' => 'bg-amarillo text-grafito',
            'grafito' => 'bg-grafito text-blanco',
        ];
    @endphp

    <section aria-labelledby="titulo-pedido" class="mx-auto max-w-3xl px-4 pb-24 pt-8 sm:px-10 sm:pt-14">
        <a href="{{ route('cuenta.pedidos.index') }}" class="inline-flex min-h-11 items-center gap-1.5 text-lg font-bold no-underline hover:underline">
            <x-icono nombre="volver" class="size-5" />
            Mis pedidos
        </a>

        <p class="mt-4 text-base font-bold text-tinta">Pedido del {{ $fecha }}</p>
        <h1 id="titulo-pedido" class="cifras condensada mt-1 text-[40px] break-all sm:text-[56px]">{{ $pedido->numero }}</h1>

        <section aria-label="Estado del pedido" class="mt-6 rounded-[10px] p-6 sm:p-8 {{ $coloresEstado[$estado['color']] }}">
            <p class="condensada text-[44px] sm:text-[56px]">{{ $estado['etiqueta'] }}</p>
            <p class="mt-2 text-lg leading-snug">{{ $estado['descripcion'] }}</p>
        </section>

        @if ($puedePagar)
            <section aria-labelledby="titulo-pago" class="mt-6 flex flex-col gap-4 rounded-[10px] border-2 border-naranja p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <h2 id="titulo-pago" class="text-xl font-extrabold">Ya podés pagar tu pedido</h2>
                    <p class="mt-1 text-base text-tinta">
                        Total: <span class="cifras font-bold text-grafito">{{ FormatoMoneda::formatear((string) $total) }}</span>. Te llevamos a la página para elegir cómo pagar.
                    </p>
                </div>
                <x-boton :href="route('pago.mostrar', $pedido->token_pago)" variante="naranja" tamano="grande" class="w-full sm:w-auto">Pagar ahora</x-boton>
            </section>
        @endif

        @if ($cancelado)
            <section aria-labelledby="titulo-cancelado" class="mt-6 rounded-[10px] border-2 border-grafito bg-humo p-5">
                <h2 id="titulo-cancelado" class="text-xl font-extrabold">Este pedido está cancelado</h2>
                @if ($motivo !== null)
                    <p class="mt-2 text-lg leading-snug">{{ $motivo }}</p>
                @endif
                @if ($pedido->motivo_cancelacion === MotivoCancelacion::Reemplazado && $pedido->reemplazadoPor !== null)
                    <p class="mt-3 text-lg">
                        Este pedido fue reemplazado por
                        <a href="{{ route('cuenta.pedidos.mostrar', $pedido->reemplazadoPor->numero) }}" class="cifras inline-flex min-h-11 items-center font-extrabold underline underline-offset-4">{{ $pedido->reemplazadoPor->numero }}</a>.
                    </p>
                @endif
            </section>
        @else
            <section aria-labelledby="titulo-avance" class="mt-10">
                <h2 id="titulo-avance" class="text-2xl font-extrabold">Avance de tu pedido</h2>

                <ol class="mt-5 flex flex-col gap-2 sm:grid sm:grid-cols-5">
                    @foreach (EstadoVisiblePedido::ETAPAS as $indice => $etapa)
                        @php
                            $numeroPaso = $indice + 1;
                            $hecho = $numeroPaso < $estado['paso'];
                            $actual = $numeroPaso === $estado['paso'];
                        @endphp
                        <li
                            @if ($actual) aria-current="step" @endif
                            class="flex min-h-12 min-w-0 items-center gap-2 rounded-md px-3.5 py-2.5 text-base leading-tight {{ $hecho ? 'bg-grafito font-bold text-blanco' : ($actual ? 'border-2 border-grafito bg-amarillo font-extrabold text-grafito' : 'border-2 border-linea font-bold text-tinta') }}"
                        >
                            @if ($hecho)
                                <x-icono nombre="check" class="size-5 stroke-3" />
                            @endif
                            <span class="min-w-0 break-words">{{ $etapa }}</span>
                            <span class="sr-only">{{ $hecho ? '(hecho)' : ($actual ? '(en curso)' : '(pendiente)') }}</span>
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif

        <section aria-labelledby="titulo-productos" class="mt-10">
            <h2 id="titulo-productos" class="text-2xl font-extrabold">Productos</h2>

            <ul class="mt-4 flex flex-col gap-3">
                @foreach ($pedido->items as $item)
                    @php
                        $etiquetaLinea = collect([$item->color, $item->etiqueta])->filter()->unique()->implode(' · ');
                    @endphp
                    <li class="flex min-w-0 items-start justify-between gap-3 rounded-[10px] bg-blanco p-4 shadow-[0_2px_6px_rgba(38,40,43,0.06)]">
                        <div class="min-w-0">
                            <p class="text-lg font-extrabold break-words">{{ $item->nombre_producto }}</p>
                            @if ($etiquetaLinea !== '')
                                <p class="text-base text-tinta">{{ $etiquetaLinea }}</p>
                            @endif
                            <p class="cifras mt-1 text-base text-tinta">{{ $item->cantidad }} × {{ FormatoMoneda::formatear((string) $item->precio_unitario) }}</p>
                        </div>
                        <p class="cifras shrink-0 text-lg font-extrabold">{{ FormatoMoneda::formatear((string) $item->subtotal) }}</p>
                    </li>
                @endforeach
            </ul>
        </section>

        <section aria-labelledby="titulo-envio" class="mt-10">
            <h2 id="titulo-envio" class="text-2xl font-extrabold">Dónde lo enviamos</h2>

            <div class="mt-4 flex min-w-0 flex-col gap-1.5 rounded-[10px] bg-niebla p-5 text-lg leading-snug">
                <p class="font-bold break-words">{{ $pedido->direccion_envio }} {{ $pedido->numero_envio }}</p>
                @if ($pedido->direccion_adicional)
                    <p class="break-words">{{ $pedido->direccion_adicional }}</p>
                @endif
                <p class="break-words">{{ $pedido->localidad_envio }}, {{ $pedido->provincia_envio->nombre() }}. Código postal {{ $pedido->codigo_postal_envio }}</p>
                @if ($pedido->observaciones_envio)
                    <p class="mt-2 text-base text-tinta break-words">Observaciones: {{ $pedido->observaciones_envio }}</p>
                @endif
                @if ($pedido->expreso)
                    <p class="mt-2">Lo enviamos por <strong>{{ $pedido->expreso }}</strong>.</p>
                @endif
            </div>
        </section>

        <section aria-labelledby="titulo-total" class="mt-10">
            <h2 id="titulo-total" class="text-2xl font-extrabold">Total</h2>

            <dl class="mt-4 flex flex-col gap-3 rounded-[10px] border-2 border-humo p-5 text-lg">
                <div class="flex items-baseline justify-between gap-3">
                    <dt>Productos</dt>
                    <dd class="cifras font-bold">{{ FormatoMoneda::formatear((string) $pedido->subtotal_productos) }}</dd>
                </div>
                <div class="flex items-baseline justify-between gap-3">
                    <dt>Envío</dt>
                    <dd>
                        @if ($pedido->costo_envio !== null)
                            <span class="cifras font-bold">{{ FormatoMoneda::formatear((string) $pedido->costo_envio) }}</span>
                        @else
                            <span class="text-base text-tinta">A coordinar por WhatsApp</span>
                        @endif
                    </dd>
                </div>
                <div class="mt-1 flex items-baseline justify-between gap-3 border-t-2 border-dashed border-linea pt-3 text-xl">
                    <dt class="font-extrabold">Total</dt>
                    <dd class="cifras font-black">
                        @if ($pedido->total_base !== null)
                            {{ FormatoMoneda::formatear((string) $pedido->total_base) }}
                        @else
                            {{ FormatoMoneda::formatear((string) $pedido->subtotal_productos) }} <span class="text-base font-semibold text-tinta">+ envío a coordinar</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </section>

        @if ($whatsappUrl)
            <section aria-labelledby="titulo-ayuda" class="mt-10 flex flex-col gap-4 rounded-[10px] bg-grafito p-6 text-blanco">
                <h2 id="titulo-ayuda" class="text-xl font-extrabold">¿Tenés alguna duda con tu pedido?</h2>
                <p class="text-base">Escribinos por WhatsApp y te respondemos.</p>
                <x-boton :href="$whatsappUrl" variante="naranja" target="_blank" rel="noopener" class="w-full sm:w-auto">
                    <x-icono nombre="whatsapp" class="size-[22px]" />
                    Escribinos por WhatsApp
                    <span class="sr-only">(se abre WhatsApp en otra pestaña)</span>
                </x-boton>
            </section>
        @endif
    </section>
</x-layouts.tienda>
