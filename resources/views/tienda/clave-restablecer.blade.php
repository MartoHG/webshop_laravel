<x-layouts.tienda titulo="Contraseña nueva">
    <section aria-labelledby="titulo-clave-nueva" class="mx-auto max-w-md px-4 pb-16 pt-10 sm:pt-16">
        <h1 id="titulo-clave-nueva" class="condensada text-[52px] sm:text-[64px]">Contraseña nueva</h1>
        <p class="mt-4 text-lg text-tinta">Elegí una contraseña nueva para tu cuenta.</p>

        <form method="POST" action="{{ route('password.update') }}" class="mt-8 flex flex-col gap-6">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-formulario.resumen />

            <x-formulario.campo nombre="email" etiqueta="Email" tipo="email" autocompletar="email" modo="email" :valor="$email" />
            <x-formulario.campo nombre="password" etiqueta="Contraseña nueva" tipo="password" autocompletar="new-password" pista="Mínimo 8 caracteres." />
            <x-formulario.campo nombre="password_confirmation" etiqueta="Repetí la contraseña nueva" tipo="password" autocompletar="new-password" />

            <x-boton type="submit" class="w-full">Guardar contraseña nueva</x-boton>
        </form>

        <p class="mt-8 text-lg">¿El link ya venció? <a href="{{ route('password.request') }}" class="inline-flex min-h-11 items-center font-bold">Pedí uno nuevo</a></p>
    </section>
</x-layouts.tienda>
