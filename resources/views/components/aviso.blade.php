@props(['tipo' => 'exito'])

@php
    $esError = $tipo === 'error';
@endphp

<div role="{{ $esError ? 'alert' : 'status' }}" {{ $attributes->class(['flex items-start gap-3 rounded-[10px] px-4 py-3.5 text-base font-semibold leading-snug', $esError ? 'border-2 border-naranja bg-naranja-claro text-grafito' : 'bg-grafito text-blanco']) }}>
    @if ($esError)
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="mt-0.5 size-6 shrink-0">
            <path d="M12 4l9 16H3z"/>
            <path d="M12 10v4M12 17h.01"/>
        </svg>
    @else
        <x-icono nombre="check" class="mt-0.5 size-6 text-amarillo" />
    @endif
    <div class="min-w-0">{{ $slot }}</div>
</div>
