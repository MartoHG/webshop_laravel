<?php

return [
    'disco' => env('IMAGENES_PRODUCTO_DISK', 'public'),
    'disco_temporal' => env('IMAGENES_PRODUCTO_TEMP_DISK', 'local'),
    'directorio_temporal' => 'imagenes-producto/temporales',
    'directorio_final' => 'imagenes-producto',
    'maximo_kilobytes' => (int) env('IMAGENES_PRODUCTO_MAX_KB', 12288),
    'maximo_pixeles' => (int) env('IMAGENES_PRODUCTO_MAX_PIXELES', 12000000),
    'maximo_ancho_detalle' => 1600,
    'maximo_alto_detalle' => 1600,
    'maximo_ancho_miniatura' => 480,
    'maximo_alto_miniatura' => 480,
    'calidad_detalle' => 84,
    'calidad_miniatura' => 78,
    'contenido' => [
        'directorio_temporal' => 'imagenes-contenido/temporales',
        'directorio_final' => 'imagenes-contenido',
        'portada' => [
            'maximo_ancho' => 2400,
            'maximo_alto' => 1600,
            'calidad' => 82,
            'maximo_ancho_miniatura' => 960,
            'maximo_alto_miniatura' => 640,
            'calidad_miniatura' => 78,
        ],
        'categoria' => [
            'maximo_ancho' => 1200,
            'maximo_alto' => 1500,
            'calidad' => 82,
            'maximo_ancho_miniatura' => 480,
            'maximo_alto_miniatura' => 600,
            'calidad_miniatura' => 78,
        ],
    ],
];
