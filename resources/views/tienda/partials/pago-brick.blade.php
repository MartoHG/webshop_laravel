{{-- Monto a pagar y formulario de Mercado Pago. Lo devuelve Continuar con htmx y también la página al recargar. --}}
<section aria-label="Monto a pagar" class="flex flex-col items-center">
    <x-etiqueta color="naranja" tamano="grande" class="w-full max-w-xs">
        <p class="text-sm font-extrabold uppercase tracking-wide">Vas a pagar</p>
        <p class="condensada cifras mt-1 text-[44px]">{{ $brick['importe_formateado'] }}</p>
        @if ($brick['detalle_cuotas'] !== null)
            <p class="cifras text-base font-bold">{{ $brick['detalle_cuotas'] }}</p>
        @endif
    </x-etiqueta>

    @if ($brick['public_key'] === null)
        <x-aviso tipo="error" class="mt-6 w-full">Mercado Pago todavía no está configurado en esta tienda.</x-aviso>
    @else
        <div class="mt-6 w-full rounded-[10px] border-2 border-dashed border-campo p-4 sm:p-6">
            <div
                id="pago-brick"
                class="min-h-40"
                data-public-key="{{ $brick['public_key'] }}"
                data-importe="{{ $brick['importe'] }}"
                data-tipo="{{ $brick['tipo'] }}"
                data-cuotas="{{ $brick['cuotas'] }}"
                data-pago-id="{{ $brick['pago_id'] }}"
                data-preference-id="{{ $brick['preference_id'] ?? '' }}"
                data-url-tarjeta="{{ $brick['url_tarjeta'] ?? '' }}"
                data-url-estado="{{ $brick['url_estado'] }}"
            >
                <p class="text-center text-base text-tinta">Cargando el formulario seguro de Mercado Pago…</p>
                <noscript>
                    <p class="text-center text-base font-semibold text-grafito">Para pagar necesitás tener JavaScript activado. Si no podés, escribinos por WhatsApp.</p>
                </noscript>
            </div>
        </div>

        <p class="mt-3 text-center text-base text-tinta">Solo acepta la forma de pago que elegiste. Los datos de tu tarjeta los maneja Mercado Pago; nosotros no los vemos ni los guardamos.</p>
    @endif
</section>
