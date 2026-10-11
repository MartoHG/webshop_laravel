<?php

use App\Http\Controllers\Tienda\InicioController;
use Illuminate\Support\Facades\Route;

Route::get('/', [InicioController::class, 'mostrar'])->name('tienda.inicio');
