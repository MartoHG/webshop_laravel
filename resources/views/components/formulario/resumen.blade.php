@if ($errors->any())
    <x-aviso tipo="error" class="mb-2">
        <p class="font-extrabold">Revisá los datos marcados abajo.</p>
        <ul class="mt-1 list-disc space-y-0.5 pl-5 font-semibold">
            @foreach ($errors->all() as $mensaje)
                <li>{{ $mensaje }}</li>
            @endforeach
        </ul>
    </x-aviso>
@endif
