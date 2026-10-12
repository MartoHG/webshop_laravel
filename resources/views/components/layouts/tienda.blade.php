@props([
    'titulo' => null,
    'descripcion' => 'Muebles de jardín para disfrutar el verano. Envíos a todo el país o retiro en el local de Olavarría.',
])

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $descripcion }}">
    <meta name="theme-color" content="#26282b">
    <title>{{ $titulo ? $titulo.' · ' : '' }}El Refugio</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-blanco font-sans text-grafito">
    <a href="#contenido" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-amarillo focus:px-4 focus:py-3 focus:font-bold">Saltar al contenido</a>

    <header class="relative z-30 bg-grafito text-blanco">
        <div class="mx-auto flex h-16 max-w-7xl items-center gap-6 px-4 sm:h-[76px] sm:px-10">
            <a href="{{ route('tienda.inicio') }}" class="flex shrink-0 items-baseline gap-2.5 whitespace-nowrap no-underline">
                <span class="condensada text-[28px] sm:text-[34px]">El Refugio</span>
                <span class="hidden text-sm font-semibold text-amarillo sm:inline">Olavarría</span>
            </a>

            <nav aria-label="Principal" class="ml-6 hidden gap-7 whitespace-nowrap xl:flex">
                <a href="{{ route('catalogo.index') }}" class="py-2.5 text-[17px] font-semibold no-underline hover:underline">Muebles</a>
                <a href="{{ route('catalogo.index', ['categoria' => 'ofertas']) }}" class="py-2.5 text-[17px] font-semibold no-underline hover:underline">Ofertas</a>
                <a href="{{ route('tienda.inicio') }}#como-comprar" class="py-2.5 text-[17px] font-semibold no-underline hover:underline">Cómo comprar</a>
                <a href="{{ route('tienda.inicio') }}#retiro" class="py-2.5 text-[17px] font-semibold no-underline hover:underline">Retiro en el local</a>
            </nav>

            <div class="ml-auto flex items-center gap-1 whitespace-nowrap sm:gap-2">
                <a href="{{ route('catalogo.index') }}#buscar" aria-label="Buscar muebles" class="grid size-12 place-items-center rounded-md hover:bg-white/10">
                    <x-icono nombre="buscar" />
                </a>
                @auth
                    <a href="{{ route('cuenta.mostrar') }}" class="hidden h-12 items-center gap-2 rounded-md px-3 font-semibold no-underline hover:bg-white/10 sm:flex">
                        <x-icono nombre="usuario" class="size-[22px]" />
                        Mi cuenta
                    </a>
                @else
                    <a href="{{ route('login.formulario') }}" class="hidden h-12 items-center gap-2 rounded-md px-3 font-semibold no-underline hover:bg-white/10 sm:flex">
                        <x-icono nombre="usuario" class="size-[22px]" />
                        Ingresar
                    </a>
                @endauth
                @include('tienda.partials.carrito-enlace', ['unidades' => $unidadesCarrito])

                <details class="group xl:hidden">
                    <summary aria-label="Abrir menú" class="grid size-12 cursor-pointer list-none place-items-center rounded-md hover:bg-white/10 [&::-webkit-details-marker]:hidden">
                        <x-icono nombre="menu" class="size-[26px] group-open:hidden" />
                        <x-icono nombre="cerrar" class="hidden size-[26px] group-open:block" />
                    </summary>
                    <nav aria-label="Menú" class="absolute inset-x-0 top-full border-t border-white/10 bg-grafito px-4 pb-6 pt-2 shadow-[0_16px_30px_rgba(17,18,20,0.35)]">
                        <ul class="flex flex-col text-lg font-semibold">
                            <li><a href="{{ route('catalogo.index') }}" class="flex min-h-14 items-center border-b border-white/10 no-underline">Muebles</a></li>
                            <li><a href="{{ route('catalogo.index', ['categoria' => 'ofertas']) }}" class="flex min-h-14 items-center border-b border-white/10 no-underline">Ofertas</a></li>
                            <li><a href="{{ route('tienda.inicio') }}#como-comprar" class="flex min-h-14 items-center border-b border-white/10 no-underline">Cómo comprar</a></li>
                            <li><a href="{{ route('tienda.inicio') }}#retiro" class="flex min-h-14 items-center border-b border-white/10 no-underline">Retiro en el local</a></li>
                            @auth
                                <li><a href="{{ route('cuenta.mostrar') }}" class="flex min-h-14 items-center no-underline">Mi cuenta y mis pedidos</a></li>
                            @else
                                <li><a href="{{ route('login.formulario') }}" class="flex min-h-14 items-center no-underline">Ingresar</a></li>
                            @endauth
                        </ul>
                    </nav>
                </details>
            </div>
        </div>
    </header>

    <main id="contenido">
        @if (session('estado'))
            <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-10">
                <x-aviso>{{ session('estado') }}</x-aviso>
            </div>
        @endif

        @if (session('error'))
            <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-10">
                <x-aviso tipo="error">{{ session('error') }}</x-aviso>
            </div>
        @endif

        {{ $slot }}
    </main>

    <footer class="bg-grafito pb-28 pt-16 text-blanco sm:pb-10 sm:pt-[72px]">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:grid-cols-3 sm:px-10">
            <div>
                <span class="condensada text-4xl sm:text-[44px]">El Refugio</span>
                <p class="mt-3.5 max-w-[34ch] text-[17px] leading-relaxed text-[#d4d6da]">Muebles de jardín desde Olavarría para todo el país.</p>
            </div>
            <div>
                <h2 class="mb-4 text-lg font-extrabold text-amarillo">Contacto</h2>
                <ul class="flex flex-col gap-3 text-[17px] text-[#e7e8ea]">
                    @if ($contactoTienda['whatsapp_url'])
                        <li><a href="{{ $contactoTienda['whatsapp_url'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2.5"><x-icono nombre="whatsapp" class="size-5" />Escribinos por WhatsApp</a></li>
                    @endif
                    @if ($contactoTienda['instagram_url'])
                        <li><a href="{{ $contactoTienda['instagram_url'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2.5"><x-icono nombre="instagram" class="size-5" />Instagram</a></li>
                    @endif
                    @if ($contactoTienda['direccion'])
                        <li class="flex items-start gap-2.5"><x-icono nombre="ubicacion" class="mt-0.5 size-5" />{{ $contactoTienda['direccion'] }}</li>
                    @endif
                    @if ($contactoTienda['horarios_texto'])
                        <li class="flex items-start gap-2.5"><x-icono nombre="horario" class="mt-0.5 size-5" />{{ $contactoTienda['horarios_texto'] }}</li>
                    @endif
                    @if ($contactoTienda['email'])
                        <li><a href="mailto:{{ $contactoTienda['email'] }}" class="inline-flex items-center gap-2.5"><x-icono nombre="mail" class="size-5" />{{ $contactoTienda['email'] }}</a></li>
                    @endif
                </ul>
            </div>
            <div>
                <h2 class="mb-4 text-lg font-extrabold text-amarillo">Tienda</h2>
                <ul class="flex flex-col gap-3 text-[17px]">
                    <li><a href="{{ route('catalogo.index') }}">Muebles</a></li>
                    <li><a href="{{ route('catalogo.index', ['categoria' => 'ofertas']) }}">Ofertas</a></li>
                    <li><a href="{{ route('tienda.inicio') }}#como-comprar">Cómo comprar</a></li>
                    <li><a href="{{ route('cuenta.pedidos.index') }}">Mi cuenta y mis pedidos</a></li>
                </ul>
            </div>
        </div>
        <p class="mx-auto mt-14 max-w-7xl border-t border-[#45484d] px-4 pt-6 text-sm text-[#b9bcc1] sm:px-10">Precios en pesos argentinos. El envío se cotiza y se suma antes de pagar.</p>
    </footer>

    @if ($contactoTienda['whatsapp_url'])
        <a href="{{ $contactoTienda['whatsapp_url'] }}" target="_blank" rel="noopener" aria-label="Escribinos por WhatsApp" class="fixed bottom-5 right-4 z-40 flex h-[60px] items-center gap-2.5 rounded-full bg-grafito pl-[18px] pr-[18px] font-extrabold text-blanco no-underline shadow-[0_10px_26px_rgba(38,40,43,0.35)] sm:bottom-7 sm:right-7 sm:pr-[22px]">
            <x-icono nombre="whatsapp" class="size-7 text-amarillo" />
            <span class="hidden sm:inline">Hablá con nosotros</span>
        </a>
    @endif
</body>
</html>
