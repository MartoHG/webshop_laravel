<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

require __DIR__.'/autenticacion.php';
require __DIR__.'/cuenta.php';
require __DIR__.'/administracion.php';
require __DIR__.'/productos.php';
require __DIR__.'/categorias.php';
require __DIR__.'/imagenes.php';
require __DIR__.'/precios.php';
require __DIR__.'/catalogo.php';
require __DIR__.'/carrito.php';
require __DIR__.'/auditoria.php';
