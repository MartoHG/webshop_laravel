<x-layouts.tienda titulo="Crear cuenta">
    <section aria-labelledby="titulo-registro" class="mx-auto max-w-md px-4 pb-16 pt-10 sm:pt-16">
        <h1 id="titulo-registro" class="condensada text-[52px] sm:text-[64px]">Crear cuenta</h1>
        <p class="mt-4 text-lg text-tinta">Necesitás una cuenta para hacer un pedido. Te lleva un minuto.</p>

        <form method="POST" action="{{ route('registro') }}" class="mt-8 flex flex-col gap-6">
            @csrf
            <x-formulario.resumen />

            <x-formulario.campo nombre="nombre" etiqueta="Nombre" autocompletar="given-name" />
            <x-formulario.campo nombre="apellido" etiqueta="Apellido" autocompletar="family-name" />
            <x-formulario.campo nombre="dni" etiqueta="DNI" modo="numeric" pista="Solo números" />
            <x-formulario.campo nombre="email" etiqueta="Email" tipo="email" autocompletar="email" modo="email" />
            <x-formulario.campo nombre="telefono" etiqueta="Teléfono o WhatsApp" tipo="tel" autocompletar="tel" pista="Te vamos a escribir a este número para coordinar el envío" />
            <x-formulario.campo nombre="password" etiqueta="Contraseña" tipo="password" autocompletar="new-password" pista="Mínimo 8 caracteres." />
            <x-formulario.campo nombre="password_confirmation" etiqueta="Repetí la contraseña" tipo="password" autocompletar="new-password" />

            <x-boton type="submit" class="w-full">Crear cuenta</x-boton>
        </form>

        <p class="mt-8 text-lg">¿Ya tenés cuenta? <a href="{{ route('login.formulario') }}" class="inline-flex min-h-11 items-center font-bold">Ingresá acá</a></p>
    </section>
</x-layouts.tienda>
