<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcesarNotificacionMercadoPago;
use App\Servicios\Pagos\VerificadorFirmaMercadoPago;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MercadoPagoController extends Controller
{
    /**
     * Responde enseguida: la consulta a Mercado Pago y la aplicación del resultado van en un job.
     */
    public function recibir(Request $solicitud, VerificadorFirmaMercadoPago $verificador): JsonResponse
    {
        if (! $verificador->esValida($solicitud)) {
            return response()->json(['mensaje' => 'Firma inválida.'], 401);
        }

        $tipo = $solicitud->query('type') ?? $solicitud->input('type');

        if ($tipo !== 'payment') {
            return response()->json(['mensaje' => 'Notificación ignorada.']);
        }

        $idPago = $verificador->idDelDato($solicitud);

        if ($idPago === null) {
            Log::warning('Notificación de pago de Mercado Pago sin id.');

            return response()->json(['mensaje' => 'Notificación sin id de pago.']);
        }

        ProcesarNotificacionMercadoPago::dispatch($idPago);

        return response()->json(['mensaje' => 'Notificación recibida.']);
    }
}
