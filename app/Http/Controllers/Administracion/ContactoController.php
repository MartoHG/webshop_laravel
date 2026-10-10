<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administracion\Contenido\ActualizarContactoRequest;
use App\Http\Requests\Administracion\Contenido\AdministrarContenidoRequest;
use App\Servicios\Contenido\ContactoService;
use Illuminate\Http\JsonResponse;

class ContactoController extends Controller
{
    public function mostrar(AdministrarContenidoRequest $request, ContactoService $servicio): JsonResponse
    {
        return response()->json(['contacto' => $servicio->obtener()]);
    }

    public function actualizar(ActualizarContactoRequest $request, ContactoService $servicio): JsonResponse
    {
        $servicio->actualizar($request->validated());

        return response()->json([
            'mensaje' => 'Datos de contacto actualizados.',
            'contacto' => $servicio->obtener(),
        ]);
    }
}
