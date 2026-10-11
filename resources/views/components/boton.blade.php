@props([
    'variante' => 'oscuro',
    'href' => null,
    'type' => 'button',
    'tamano' => 'normal',
])

@php
    $variantes = [
        'oscuro' => 'bg-grafito text-blanco hover:bg-grafito-hondo',
        'naranja' => 'bg-naranja text-grafito shadow-[0_8px_18px_rgba(242,96,31,0.3)] hover:bg-[#e3551a]',
        'contorno' => 'border-2 border-grafito bg-blanco text-grafito hover:bg-niebla',
    ];

    $tamanos = [
        'normal' => 'min-h-14 px-7 text-lg',
        'grande' => 'min-h-16 px-8 text-xl',
    ];

    $clases = 'inline-flex items-center justify-center gap-2.5 rounded-md font-extrabold no-underline transition-colors duration-200 disabled:cursor-not-allowed disabled:opacity-50 '
        .($variantes[$variante] ?? $variantes['oscuro']).' '
        .($tamanos[$tamano] ?? $tamanos['normal']);
@endphp

@if ($href !== null)
    <a href="{{ $href }}" {{ $attributes->class($clases) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($clases) }}>{{ $slot }}</button>
@endif
