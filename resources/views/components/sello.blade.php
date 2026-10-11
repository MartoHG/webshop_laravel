@props([
    'color' => 'amarillo',
    'rotacion' => -8,
])

@php
    $fondos = [
        'amarillo' => 'bg-amarillo text-grafito',
        'naranja' => 'bg-naranja text-grafito',
        'celeste' => 'bg-celeste text-grafito',
        'grafito' => 'bg-grafito text-blanco',
    ];
@endphp

<div {{ $attributes->class(['grid place-items-center rounded-full text-center shadow-[0_12px_28px_rgba(38,40,43,0.25)]', 'relative' => ! str_contains((string) $attributes->get('class'), 'absolute'), $fondos[$color] ?? $fondos['amarillo']]) }} style="transform: rotate({{ (int) $rotacion }}deg)">
    <span aria-hidden="true" class="absolute inset-[6%] rounded-full border-2 border-dashed border-current opacity-50"></span>
    <span class="relative flex flex-col items-center px-2">{{ $slot }}</span>
</div>
