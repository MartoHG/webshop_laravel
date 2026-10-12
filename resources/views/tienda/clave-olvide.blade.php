<x-layouts.tienda titulo="Recuperar contraseña">
    <section aria-labelledby="titulo-recuperar" class="mx-auto max-w-md px-4 pb-16 pt-10 sm:pt-16">
        <h1 id="titulo-recuperar" class="condensada text-[52px] sm:text-[64px]">Recuperar contraseña</h1>
        <p class="mt-4 text-lg text-tinta">Escribí el email de tu cuenta. Te mandamos un link para crear una contraseña nueva.</p>

        <form method="POST" action="{{ route('password.email') }}" class="mt-8 flex flex-col gap-6">
            @csrf
            <x-formulario.resumen />

            <x-formulario.campo nombre="email" etiqueta="Email" tipo="email" autocompletar="email" modo="email" />

            <x-boton type="submit" class="w-full">Enviar link</x-boton>
        </form>

        <p class="mt-8 text-lg"><a href="{{ route('login.formulario') }}" class="inline-flex min-h-11 items-center font-bold">Volver a iniciar sesión</a></p>
    </section>
</x-layouts.tienda>
