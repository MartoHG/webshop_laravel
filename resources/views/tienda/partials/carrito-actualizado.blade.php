{{-- Respuesta htmx de un cambio en el carrito: el contenido, y el contador del encabezado por fuera de banda. --}}
@include('tienda.partials.carrito-contenido')
@include('tienda.partials.carrito-enlace', ['oob' => true, 'unidades' => $carrito['cantidad_unidades']])
