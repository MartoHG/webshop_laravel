@props([
    'nombre',
    'etiqueta',
    'tipo' => 'text',
    'pista' => null,
    'valor' => null,
    'autocompletar' => null,
    'modo' => null,
])

@php
    $id = 'campo-'.\Illuminate\Support\Str::slug($nombre);
    $idPista = $id.'-pista';
    $idError = $id.'-error';
    $error = $errors->first($nombre);
    $valorMostrado = $tipo === 'password' ? null : old($nombre, $valor);
    $descripcion = collect([$pista !== null ? $idPista : null, $error ? $idError : null])->filter()->implode(' ');
@endphp

<div class="flex flex-col gap-2">
    <label for="{{ $id }}" class="text-base font-bold">{{ $etiqueta }}</label>
    @if ($pista !== null)
        <p id="{{ $idPista }}" class="text-base font-medium text-tinta">{{ $pista }}</p>
    @endif
    <input
        id="{{ $id }}"
        name="{{ $nombre }}"
        type="{{ $tipo }}"
        @unless ($valorMostrado === null) value="{{ $valorMostrado }}" @endunless
        @if ($autocompletar !== null) autocomplete="{{ $autocompletar }}" @endif
        @if ($modo !== null) inputmode="{{ $modo }}" @endif
        @if ($descripcion !== '') aria-describedby="{{ $descripcion }}" @endif
        @if ($error) aria-invalid="true" @endif
        {{ $attributes->class([
            'h-[54px] w-full rounded-lg border-2 bg-blanco px-3.5 text-[17px] text-grafito placeholder:text-tinta-tenue',
            $error ? 'border-error' : 'border-campo',
        ]) }}
    >
    @if ($error)
        <p id="{{ $idError }}" class="flex items-start gap-2 text-base font-semibold text-error">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="mt-0.5 size-5 shrink-0">
                <path d="M12 4l9 16H3z"/>
                <path d="M12 10v4M12 17h.01"/>
            </svg>
            <span>{{ $error }}</span>
        </p>
    @endif
</div>
