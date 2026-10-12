<x-layouts.tienda titulo="Mi cuenta">
    <section aria-labelledby="titulo-cuenta" class="mx-auto max-w-md px-4 pb-16 pt-10 sm:pt-16">
        <h1 id="titulo-cuenta" class="condensada text-[52px] sm:text-[64px]">Mi cuenta</h1>
        <p class="mt-4 text-lg text-tinta">Hola, {{ $usuario->nombre }}.</p>

        @unless ($usuario->hasVerifiedEmail())
            <section aria-labelledby="titulo-verificacion" class="mt-8 flex flex-col gap-4 rounded-[10px] border-2 border-naranja bg-naranja-claro p-5">
                <div class="flex items-start gap-3">
                    <x-icono nombre="mail" class="mt-0.5 size-6 text-grafito" />
                    <div>
                        <h2 id="titulo-verificacion" class="text-xl font-extrabold">Falta confirmar tu email</h2>
                        <p class="mt-2 text-base">Para confirmar un pedido necesitamos que verifiques tu email. Lo usamos para avisarte si no logramos comunicarnos por WhatsApp.</p>
                        <p class="mt-2 text-base">Te mandamos el link a {{ $usuario->email }}.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <x-boton type="submit" variante="contorno" class="w-full sm:w-auto">Mandarme otro email para confirmar</x-boton>
                </form>
            </section>
        @endunless

        <section aria-labelledby="titulo-datos" class="mt-10">
            <h2 id="titulo-datos" class="text-2xl font-extrabold">Tus datos</h2>

            <dl class="mt-4 grid grid-cols-[auto_1fr] gap-x-5 gap-y-2 text-lg">
                <dt class="font-bold text-tinta">Nombre</dt>
                <dd>{{ $usuario->nombre }}</dd>
                <dt class="font-bold text-tinta">Apellido</dt>
                <dd>{{ $usuario->apellido }}</dd>
            </dl>
            <p class="mt-2 text-base text-tinta">Para cambiar tu nombre, escribinos por WhatsApp.</p>

            <form method="POST" action="{{ route('cuenta.perfil.actualizar') }}" class="mt-6 flex flex-col gap-6">
                @csrf
                @method('PUT')
                <x-formulario.resumen />

                <x-formulario.campo nombre="dni" etiqueta="DNI" modo="numeric" pista="Solo números" :valor="$usuario->dni" />
                <x-formulario.campo nombre="email" etiqueta="Email" tipo="email" autocompletar="email" modo="email" pista="Si lo cambiás, te mandamos un email para confirmar la nueva dirección." :valor="$usuario->email" />
                <x-formulario.campo nombre="telefono" etiqueta="Teléfono o WhatsApp" tipo="tel" autocompletar="tel" pista="Te vamos a escribir a este número para coordinar el envío" :valor="$usuario->telefono" />

                <x-boton type="submit" class="w-full">Guardar cambios</x-boton>
            </form>
        </section>

        <section aria-labelledby="titulo-pedidos" class="mt-10">
            <h2 id="titulo-pedidos" class="text-2xl font-extrabold">Tus pedidos</h2>
            <x-boton :href="route('cuenta.pedidos.index')" variante="contorno" class="mt-4 w-full">Mis pedidos</x-boton>
        </section>

        <form method="POST" action="{{ route('logout') }}" class="mt-10">
            @csrf
            <x-boton type="submit" variante="contorno" class="w-full">Cerrar sesión</x-boton>
        </form>
    </section>
</x-layouts.tienda>
