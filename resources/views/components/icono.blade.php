@props(['nombre'])

@php
    $trazos = [
        'buscar' => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-4.2-4.2"/>',
        'carrito' => '<path d="M3 4h2.5l2.2 11h10.6l2-8H7"/><circle cx="9.5" cy="19.5" r="1.5"/><circle cx="17" cy="19.5" r="1.5"/>',
        'usuario' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'cerrar' => '<path d="M6 6l12 12M18 6L6 18"/>',
        'flecha' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'volver' => '<path d="M15 6l-6 6 6 6"/>',
        'envio' => '<path d="M2 7h11v9H2zM13 10h4l3 3v3h-7"/><circle cx="6" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/>',
        'pago' => '<rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18M7 15h4"/>',
        'cuotas' => '<rect x="2" y="8" width="16" height="11" rx="2"/><path d="M6 5h14a2 2 0 0 1 2 2v9M2 12h16"/>',
        'local' => '<path d="M4 10l2-5h12l2 5M4 10v10h16V10M4 10h16M9 20v-5h6v5"/>',
        'whatsapp' => '<path d="M4 19l1.4-4A8 8 0 1 1 9 18.6z"/><path d="M9 10h6M9 13h4"/>',
        'calidad' => '<circle cx="12" cy="9" r="6"/><path d="M9 14.5L7.5 22l4.5-2.5 4.5 2.5L15 14.5"/><path d="M9.5 9l1.8 1.8 3.2-3.3"/>',
        'garantia' => '<path d="M12 3l8 3v6c0 4.5-3.4 8-8 9-4.6-1-8-4.5-8-9V6z"/><path d="M8.5 12l2.5 2.5 4.5-5"/>',
        'horario' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'check' => '<path d="M5 12l5 5 9-10"/>',
        'candado' => '<rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
        'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="0.6"/>',
        'ubicacion' => '<path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
    ];
@endphp

<svg {{ $attributes->class(['shrink-0', 'size-6' => ! str_contains((string) $attributes->get('class'), 'size-')]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $trazos[$nombre] ?? '' !!}</svg>
