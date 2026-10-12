<x-layouts.tienda titulo="Este link de pago ya no está activo">
    <section class="mx-auto flex max-w-3xl flex-col items-start gap-6 px-4 py-16 sm:px-10 sm:py-28">
        <h1 class="condensada text-[52px] sm:text-[80px]">Este link de pago ya no está activo</h1>
        <p class="text-lg leading-relaxed text-tinta sm:text-xl">Si ya pagaste este pedido, no tenés que hacer nada más. Si todavía no lo pagaste, escribinos por WhatsApp y te ayudamos.</p>
        <div class="flex w-full flex-col gap-4 sm:w-auto sm:flex-row">
            <x-boton :href="route('cuenta.pedidos.index')" class="w-full sm:w-auto">Ver mis pedidos</x-boton>
            @if ($whatsappUrl !== null)
                <x-boton :href="$whatsappUrl" variante="contorno" class="w-full sm:w-auto">Escribinos por WhatsApp</x-boton>
            @endif
        </div>
    </section>
</x-layouts.tienda>
