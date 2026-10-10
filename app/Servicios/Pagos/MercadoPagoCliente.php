<?php

namespace App\Servicios\Pagos;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Cliente mínimo de la API de pagos de Mercado Pago. No guarda ni registra el token de acceso ni los datos de tarjeta.
 */
class MercadoPagoCliente
{
    private const int SEGUNDOS_TIMEOUT = 20;

    /**
     * @param  array<string, mixed>  $cuerpo
     * @return array<string, mixed>
     *
     * @throws ErrorMercadoPago
     */
    public function crearPago(array $cuerpo, string $claveIdempotencia): array
    {
        try {
            $respuesta = $this->solicitud()
                ->withHeaders(['X-Idempotency-Key' => $claveIdempotencia])
                ->post('/v1/payments', $cuerpo);
        } catch (ConnectionException $excepcion) {
            throw new ErrorMercadoPago('No se pudo conectar con Mercado Pago.', null, $excepcion);
        }

        return $this->interpretar($respuesta);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ErrorMercadoPago
     */
    public function obtenerPago(string $id): array
    {
        try {
            $respuesta = $this->solicitud()->get('/v1/payments/'.rawurlencode($id));
        } catch (ConnectionException $excepcion) {
            throw new ErrorMercadoPago('No se pudo conectar con Mercado Pago.', null, $excepcion);
        }

        return $this->interpretar($respuesta);
    }

    private function solicitud(): PendingRequest
    {
        return Http::baseUrl((string) config('mercadopago.url_api'))
            ->withToken((string) config('mercadopago.access_token'))
            ->acceptJson()
            ->timeout(self::SEGUNDOS_TIMEOUT);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ErrorMercadoPago
     */
    private function interpretar(Response $respuesta): array
    {
        if (! $respuesta->successful()) {
            throw new ErrorMercadoPago('Mercado Pago respondió con un error.', $respuesta->status());
        }

        $datos = $respuesta->json();

        if (! is_array($datos)) {
            throw new ErrorMercadoPago('Mercado Pago respondió con un cuerpo inválido.');
        }

        return $datos;
    }
}
