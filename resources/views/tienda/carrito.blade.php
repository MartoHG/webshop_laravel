<x-layouts.tienda titulo="Tu carrito">
    <section aria-labelledby="titulo-carrito" class="mx-auto max-w-7xl px-4 pb-24 pt-8 sm:px-10 sm:pt-14">
        <h1 id="titulo-carrito" class="condensada text-[52px] sm:text-[80px]">Tu carrito</h1>

        <div class="mt-6 sm:mt-8">
            @include('tienda.partials.carrito-contenido')
        </div>
    </section>
</x-layouts.tienda>
