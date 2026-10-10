<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Servicios\Pagos\PagoService;
use Illuminate\Http\JsonResponse;

class PagoController extends Controller
{
    public function index(Pedido $pedido, PagoService $servicio): JsonResponse
    {
        return response()->json(['pagos' => $servicio->listarIntentos($pedido)]);
    }
}
