@props([
    'color' => 'naranja',
    'tamano' => 'mediana',
    'hilo' => null,
    'balanceo' => false,
    'cuerpo' => '',
])

@php
    $colores = [
        'naranja' => ['fondo' => 'bg-naranja text-grafito', 'ojal' => 'border-grafito'],
        'celeste' => ['fondo' => 'bg-celeste text-grafito', 'ojal' => 'border-grafito'],
        'amarillo' => ['fondo' => 'bg-amarillo text-grafito', 'ojal' => 'border-grafito'],
        'grafito' => ['fondo' => 'bg-grafito text-blanco', 'ojal' => 'border-naranja'],
    ];

    $tamanos = [
        'grande' => ['recorte' => 'recorte-etiqueta', 'relleno' => 'px-6 pt-5 pb-7 sm:px-11 sm:pt-7 sm:pb-10 gap-4 sm:gap-5', 'ojal' => 'size-5 border-4', 'hilo' => 54],
        'mediana' => ['recorte' => 'recorte-etiqueta-chica', 'relleno' => 'px-4 pt-3 pb-3.5 gap-1.5', 'ojal' => 'size-3 border-[3px]', 'hilo' => 20],
        'chica' => ['recorte' => 'recorte-etiqueta-chica', 'relleno' => 'px-2.5 pt-2.5 pb-2 gap-1', 'ojal' => 'size-2.5 border-[2.5px]', 'hilo' => 14],
    ];

    $estilo = $colores[$color] ?? $colores['naranja'];
    $medida = $tamanos[$tamano] ?? $tamanos['mediana'];
    $largoHilo = $hilo ?? $medida['hilo'];
@endphp

<div {{ $attributes->class(['flex flex-col items-center', 'animate-balanceo origin-[50%_-60px]' => $balanceo]) }}>
    @if ($largoHilo > 0)
        <div aria-hidden="true" class="w-0.5 bg-grafito" style="height: {{ $largoHilo }}px"></div>
    @endif
    <div class="flex w-full flex-col items-center {{ $medida['recorte'] }} {{ $medida['relleno'] }} {{ $estilo['fondo'] }} {{ $cuerpo }}">
        <span aria-hidden="true" class="shrink-0 self-center rounded-full bg-blanco {{ $medida['ojal'] }} {{ $estilo['ojal'] }}"></span>
        {{ $slot }}
    </div>
</div>
