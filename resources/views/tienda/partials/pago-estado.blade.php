{{-- Resultado del pago. Lo decide el servidor. Solo mientras está pendiente tiene el disparador que vuelve a consultarlo. --}}
@php
    $colores = [
        'amarillo' => 'bg-amarillo text-grafito',
        'celeste' => 'bg-celeste text-grafito',
        'naranja' => 'border-2 border-naranja bg-naranja-claro text-grafito',
        'grafito' => 'bg-grafito text-blanco',
    ];
@endphp

<div
    @if ($urlEstado !== null)
        hx-get="{{ $urlEstado }}"
        hx-trigger="every 5s"
        hx-headers='{"X-Sondeo": "1"}'
        hx-target="#pago-resultado"
        hx-swap="innerHTML"
    @endif
>
    <section aria-labelledby="pago-estado-titulo" class="rounded-[10px] p-6 sm:p-8 {{ $colores[$color] ?? $colores['grafito'] }}">
        <h2 id="pago-estado-titulo" tabindex="-1" data-foco class="condensada text-[40px] [overflow-wrap:anywhere] sm:text-[48px]">{{ $titulo }}</h2>
        <p class="mt-3 text-lg leading-snug">{{ $texto }}</p>

        @if ($motivo !== null)
            <p class="mt-3 text-lg font-bold leading-snug">{{ $motivo }}</p>
        @endif

        @if ($clave === 'aprobado' && $verPedido !== null)
            <div class="mt-6">
                <x-boton :href="$verPedido" class="w-full sm:w-auto">Ver mi pedido</x-boton>
            </div>
        @endif

        @if ($clave === 'rechazado')
            <div class="mt-6">
                <x-boton :href="$urlPagina" variante="contorno" class="w-full sm:w-auto">Probar de nuevo</x-boton>
            </div>
        @endif

        @if (in_array($clave, ['revision', 'rechazado', 'sin_pagos'], true) && $whatsappUrl !== null)
            <p class="mt-6">
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="inline-flex min-h-12 items-center text-lg font-extrabold underline underline-offset-4">Escribinos por WhatsApp</a>
            </p>
        @endif
    </section>
</div>
