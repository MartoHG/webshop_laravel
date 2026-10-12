<x-layouts.tienda titulo="Confirmá tu pedido">
    @php
        $cliente = $checkout['cliente'];
        $carrito = $checkout['carrito'];
        $verificado = $checkout['email_verificado'];
        $envio = $envioAnterior ?? [];
    @endphp

    <section aria-labelledby="titulo-checkout" class="mx-auto max-w-6xl px-4 pb-24 pt-6 sm:px-10 sm:pt-10">
        <a href="{{ route('carrito.mostrar') }}" class="inline-flex min-h-11 items-center gap-1.5 text-lg font-bold no-underline hover:underline">
            <x-icono nombre="volver" class="size-5" />
            Volver al carrito
        </a>
        <h1 id="titulo-checkout" class="condensada mt-3 text-[48px] sm:text-[72px]">Confirmá tu pedido</h1>

        @if ($errors->has('carrito'))
            <div class="mt-6">
                <x-aviso tipo="error">{{ $errors->first('carrito') }}</x-aviso>
            </div>
        @endif

        @unless ($verificado)
            <section aria-labelledby="titulo-verificacion" class="mt-6 flex flex-col gap-4 rounded-[10px] border-2 border-naranja bg-naranja-claro p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-start gap-3">
                    <x-icono nombre="mail" class="mt-0.5 size-6 text-grafito" />
                    <div class="min-w-0">
                        <h2 id="titulo-verificacion" class="text-xl font-extrabold">Falta confirmar tu email</h2>
                        <p class="mt-2 text-base">Para confirmar el pedido necesitamos que verifiques tu email. Lo usamos para avisarte si no logramos comunicarnos por WhatsApp.</p>
                        <p class="mt-2 text-base [overflow-wrap:anywhere]">Revisá la casilla de {{ $cliente['email'] }}. Si no te llegó, pedí otro email.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('verification.send') }}" class="shrink-0">
                    @csrf
                    <x-boton type="submit" variante="contorno" class="w-full sm:w-auto">Mandarme otro email para confirmar</x-boton>
                </form>
            </section>
        @endunless

        <form method="POST" action="{{ route('checkout.confirmar') }}" class="mt-8 grid grid-cols-[minmax(0,1fr)] items-start gap-8 lg:grid-cols-[minmax(0,1fr)_380px] lg:gap-x-12">
            @csrf
            <input type="hidden" name="huella" value="{{ $checkout['huella'] }}">

            @unless ($errors->has('carrito'))
                <div class="min-w-0 lg:col-span-2">
                    <x-formulario.resumen />
                </div>
            @endunless

            <div class="flex min-w-0 flex-col gap-10">
                <section aria-labelledby="titulo-datos" class="min-w-0 rounded-[10px] border-2 border-humo p-5">
                    <div class="flex items-baseline justify-between gap-3">
                        <h2 id="titulo-datos" class="text-2xl font-extrabold">Tus datos</h2>
                        <a href="{{ route('cuenta.mostrar') }}" class="inline-flex min-h-11 items-center text-base font-bold underline underline-offset-4">
                            Cambiar
                            <span class="sr-only">tus datos</span>
                        </a>
                    </div>

                    <dl class="mt-4 grid grid-cols-[auto_minmax(0,1fr)] gap-x-5 gap-y-3 text-lg">
                        <dt class="text-tinta">Nombre</dt>
                        <dd class="font-bold [overflow-wrap:anywhere]">{{ $cliente['nombre'] }} {{ $cliente['apellido'] }}</dd>

                        <dt class="text-tinta">DNI</dt>
                        <dd class="cifras font-bold">{{ $cliente['dni'] }}</dd>

                        <dt class="text-tinta">Email</dt>
                        <dd class="font-bold [overflow-wrap:anywhere]">{{ $cliente['email'] }}</dd>

                        <dt class="text-tinta">WhatsApp</dt>
                        <dd class="cifras font-bold [overflow-wrap:anywhere]">{{ $cliente['telefono'] }}</dd>
                    </dl>
                </section>

                <section aria-labelledby="titulo-envio" class="min-w-0">
                    <h2 id="titulo-envio" class="condensada text-[40px] sm:text-[48px]">¿A dónde lo enviamos?</h2>

                    <div class="mt-6 flex flex-col gap-5">
                        <x-formulario.seleccion
                            nombre="provincia"
                            etiqueta="Provincia"
                            :opciones="$provincias"
                            :valor="$envio['provincia'] ?? null"
                            autocompletar="address-level1"
                        />

                        <div class="grid grid-cols-[minmax(0,1fr)_8rem] gap-4">
                            <div class="min-w-0">
                                <x-formulario.campo
                                    nombre="localidad"
                                    etiqueta="Localidad"
                                    autocompletar="address-level2"
                                    :valor="$envio['localidad'] ?? null"
                                />
                            </div>
                            <div class="min-w-0">
                                <x-formulario.campo
                                    nombre="codigo_postal"
                                    etiqueta="Código postal"
                                    autocompletar="postal-code"
                                    :valor="$envio['codigo_postal'] ?? null"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-[minmax(0,1fr)_7rem] gap-4">
                            <div class="min-w-0">
                                <x-formulario.campo
                                    nombre="direccion"
                                    etiqueta="Calle"
                                    autocompletar="address-line1"
                                    :valor="$envio['direccion'] ?? null"
                                />
                            </div>
                            <div class="min-w-0">
                                <x-formulario.campo
                                    nombre="numero"
                                    etiqueta="Número"
                                    :valor="$envio['numero'] ?? null"
                                />
                            </div>
                        </div>

                        <x-formulario.campo
                            nombre="direccion_adicional"
                            etiqueta="Piso o depto"
                            pista="Opcional. Por ejemplo, 3° B."
                            autocompletar="address-line2"
                            :valor="$envio['direccion_adicional'] ?? null"
                        />

                        <x-formulario.area
                            nombre="observaciones"
                            etiqueta="Algo que tengamos que saber"
                            pista="Opcional. Por ejemplo, entre qué calles queda o en qué horario podés recibirlo."
                            :valor="$envio['observaciones'] ?? null"
                            :filas="3"
                        />
                    </div>
                </section>
            </div>

            <div class="flex min-w-0 flex-col gap-6">
                <section aria-labelledby="titulo-resumen" class="min-w-0 rounded-[10px] bg-niebla p-5">
                    <h2 id="titulo-resumen" class="text-2xl font-extrabold">Resumen</h2>

                    <ul class="mt-4 flex flex-col gap-3 text-lg">
                        @foreach ($carrito['items'] as $item)
                            @php
                                $etiquetaLinea = collect([$item['color'], $item['etiqueta']])->filter()->unique()->implode(' · ');
                            @endphp
                            <li class="flex min-w-0 items-start justify-between gap-3">
                                <span class="min-w-0 break-words">
                                    {{ $item['cantidad'] }} × {{ $item['nombre_producto'] }}
                                    @if ($etiquetaLinea !== '')
                                        <span class="block text-base text-tinta">{{ $etiquetaLinea }}</span>
                                    @endif
                                </span>
                                <span class="cifras shrink-0 font-extrabold">{{ \App\Servicios\Precios\FormatoMoneda::formatear((string) $item['subtotal']) }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-4 flex flex-col gap-2 border-t-2 border-dashed border-linea pt-4">
                        <div class="flex items-baseline justify-between gap-3 text-lg">
                            <span class="font-bold">Productos</span>
                            <span class="cifras font-black">{{ \App\Servicios\Precios\FormatoMoneda::formatear($carrito['subtotal']) }}</span>
                        </div>
                        <div class="flex items-baseline justify-between gap-3 text-base text-tinta">
                            <span>Envío</span>
                            <span class="text-right">A coordinar por WhatsApp</span>
                        </div>
                    </div>
                </section>

                <section aria-labelledby="titulo-despues" class="min-w-0 rounded-[10px] bg-celeste p-5">
                    <h2 id="titulo-despues" class="text-xl font-extrabold">¿Qué pasa después?</h2>
                    <ol class="mt-3 list-decimal space-y-2 pl-6 text-lg font-medium leading-snug">
                        <li>Te escribimos por WhatsApp para cotizar el envío.</li>
                        <li>Cuando estés de acuerdo, te mandamos el link para pagar.</li>
                    </ol>
                </section>

                <div class="flex flex-col gap-3">
                    <x-boton type="submit" variante="naranja" tamano="grande" class="w-full" :disabled="! $verificado">
                        Confirmar pedido
                    </x-boton>
                    <p class="text-center text-lg text-tinta">Todavía no pagás nada.</p>
                    @unless ($verificado)
                        <p class="text-center text-base font-semibold">Vas a poder confirmar el pedido cuando verifiques tu email.</p>
                    @endunless
                </div>
            </div>
        </form>
    </section>
</x-layouts.tienda>
