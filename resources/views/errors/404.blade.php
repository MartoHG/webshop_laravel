<x-layouts.tienda titulo="No encontramos lo que buscabas">
    <section class="mx-auto flex max-w-3xl flex-col items-start gap-6 px-4 py-16 sm:px-10 sm:py-28">
        <h1 class="condensada text-[52px] sm:text-[80px]">No encontramos lo que buscabas</h1>
        <p class="text-lg leading-relaxed text-tinta sm:text-xl">Puede que el link tenga un error o que el mueble ya no esté en la tienda. Podés ver todos los muebles y seguir buscando.</p>
        <div class="flex w-full flex-col gap-4 sm:w-auto sm:flex-row">
            <x-boton :href="route('catalogo.index')" class="w-full sm:w-auto">Ver muebles</x-boton>
            <x-boton :href="route('tienda.inicio')" variante="contorno" class="w-full sm:w-auto">Ir al inicio</x-boton>
        </div>
    </section>
</x-layouts.tienda>
