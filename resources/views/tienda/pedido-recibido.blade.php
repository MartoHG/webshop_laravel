<x-layouts.tienda titulo="Pedido recibido">
    <section aria-labelledby="titulo-recibido" class="bg-amarillo">
        <div class="mx-auto max-w-xl px-4 pb-20 pt-12 sm:px-10 sm:pt-16">
            <div class="mx-auto flex max-w-md flex-col">
                <x-etiqueta color="naranja" tamano="grande" :balanceo="true">
                    <h1 id="titulo-recibido" class="condensada text-center text-[52px] sm:text-[60px]">¡Pedido recibido!</h1>
                    <p class="mt-1 text-center text-base font-bold">Tu número de pedido</p>
                    <p class="cifras condensada text-center text-[34px] tracking-wide [overflow-wrap:anywhere] sm:text-[40px]">{{ $pedido->numero }}</p>
                </x-etiqueta>
            </div>

            <section aria-labelledby="titulo-sigue" class="mt-12 rounded-[10px] bg-blanco p-5 sm:p-6">
                <h2 id="titulo-sigue" class="text-2xl font-extrabold">Ahora sigue esto</h2>

                <ol class="mt-5 flex flex-col gap-5">
                    <li class="flex min-w-0 gap-4">
                        <span aria-hidden="true" class="grid size-9 shrink-0 place-items-center rounded-full bg-grafito text-amarillo">
                            <x-icono nombre="check" class="size-[18px] stroke-3" />
                        </span>
                        <p class="min-w-0 pt-1 text-lg leading-snug [overflow-wrap:anywhere]">
                            <strong>Recibimos tu pedido.</strong> Te mandamos el detalle a {{ $pedido->email_cliente }}.
                        </p>
                    </li>

                    <li class="flex min-w-0 gap-4">
                        <span aria-hidden="true" class="grid size-9 shrink-0 place-items-center rounded-full border-[3px] border-grafito text-lg font-black">2</span>
                        <p class="min-w-0 pt-1 text-lg leading-snug [overflow-wrap:anywhere]">
                            <strong>Te escribimos por WhatsApp</strong> al <span class="cifras">{{ $pedido->telefono_cliente }}</span> para cotizar el envío.
                        </p>
                    </li>

                    <li class="flex min-w-0 gap-4">
                        <span aria-hidden="true" class="grid size-9 shrink-0 place-items-center rounded-full border-[3px] border-grafito text-lg font-black">3</span>
                        <p class="min-w-0 pt-1 text-lg leading-snug">
                            <strong>Te mandamos el link para pagar</strong> cuando estés de acuerdo con el total.
                        </p>
                    </li>
                </ol>
            </section>

            <div class="mt-10 flex flex-col gap-4">
                <x-boton :href="route('cuenta.pedidos.mostrar', $pedido->numero)" class="w-full">
                    Ver mi pedido
                    <x-icono nombre="flecha" class="size-5" />
                </x-boton>
                <a href="{{ route('tienda.inicio') }}" class="inline-flex min-h-14 items-center justify-center text-lg font-bold underline underline-offset-4">Volver a la tienda</a>
            </div>
        </div>
    </section>
</x-layouts.tienda>
