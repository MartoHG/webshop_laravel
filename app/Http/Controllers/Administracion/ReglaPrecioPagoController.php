<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administracion\Precios\ActualizarReglaPrecioPagoRequest;
use App\Http\Requests\Administracion\Precios\CrearReglaPrecioPagoRequest;
use App\Models\ReglaPrecioPago;
use App\Servicios\Precios\ReglaPrecioPagoService;
use Illuminate\Http\JsonResponse;

class ReglaPrecioPagoController extends Controller
{
    public function index(ReglaPrecioPagoService $servicio): JsonResponse
    {
        return response()->json(['reglas' => $servicio->listar()]);
    }

    public function store(CrearReglaPrecioPagoRequest $request, ReglaPrecioPagoService $servicio): JsonResponse
    {
        $regla = $servicio->crear($request->validated());

        return response()->json(['mensaje' => 'Regla de precio de pago creada.', 'regla' => $regla], 201);
    }

    public function update(ActualizarReglaPrecioPagoRequest $request, ReglaPrecioPago $regla, ReglaPrecioPagoService $servicio): JsonResponse
    {
        return response()->json([
            'mensaje' => 'Regla de precio de pago actualizada.',
            'regla' => $servicio->actualizar($regla, $request->validated()),
        ]);
    }
}
