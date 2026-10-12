<x-layouts.tienda titulo="La tienda se está preparando">
    <section class="mx-auto flex max-w-3xl flex-col items-start gap-6 px-4 py-16 sm:px-10 sm:py-28">
        <h1 class="condensada text-[52px] sm:text-[80px]">La tienda se está preparando</h1>
        <p class="text-lg leading-relaxed text-tinta sm:text-xl">Estamos terminando de cargar los precios. Volvé a intentarlo en unos minutos.</p>
        <x-boton :href="route('tienda.inicio')" class="w-full sm:w-auto">Ir al inicio</x-boton>
    </section>
</x-layouts.tienda>
