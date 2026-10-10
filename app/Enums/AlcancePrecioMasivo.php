<?php

namespace App\Enums;

enum AlcancePrecioMasivo: string
{
    case Variante = 'variante';
    case Producto = 'producto';
    case Productos = 'productos';
    case Categoria = 'categoria';
    case Todos = 'todos';
}
