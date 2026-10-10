<?php

return [
    'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
    'public_key' => env('MERCADOPAGO_PUBLIC_KEY'),
    'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),
    'url_api' => env('MERCADOPAGO_URL_API') ?: 'https://api.mercadopago.com',
    'moneda' => 'ARS',
    'descriptor' => env('MERCADOPAGO_DESCRIPTOR') ?: 'EL REFUGIO',
];
