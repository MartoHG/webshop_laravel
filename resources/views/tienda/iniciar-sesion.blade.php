<x-layouts.tienda titulo="Iniciar sesión">
    <section aria-labelledby="titulo-ingresar" class="mx-auto max-w-md px-4 pb-16 pt-10 sm:pt-16">
        <h1 id="titulo-ingresar" class="condensada text-[52px] sm:text-[64px]">Iniciar sesión</h1>
        <p class="mt-4 text-lg text-tinta">Entrá con tu email y tu contraseña para seguir con tu pedido.</p>

        <form method="POST" action="{{ route('login') }}" class="mt-8 flex flex-col gap-6">
            @csrf
            <x-formulario.resumen />

            <x-formulario.campo nombre="email" etiqueta="Email" tipo="email" autocompletar="email" modo="email" />
            <x-formulario.campo nombre="password" etiqueta="Contraseña" tipo="password" autocompletar="current-password" />

            <x-boton type="submit" class="w-full">Ingresar</x-boton>
        </form>

        <div class="mt-8 flex flex-col gap-2 text-lg">
            <a href="{{ route('password.request') }}" class="inline-flex min-h-11 items-center font-bold">¿Olvidaste tu contraseña?</a>
            <p>¿No tenés cuenta? <a href="{{ route('registro.formulario') }}" class="inline-flex min-h-11 items-center font-bold">Creala acá</a></p>
        </div>
    </section>
</x-layouts.tienda>
