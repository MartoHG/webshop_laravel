<?php

namespace App\Http\Controllers\Cuenta;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CuentaController extends Controller
{
    public function mostrar(Request $request): View
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();

        return view('tienda.cuenta', ['usuario' => $usuario]);
    }
}
