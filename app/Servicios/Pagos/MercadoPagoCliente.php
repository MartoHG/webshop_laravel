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
     * @param  int  $segundosTimeout  Más corto cuando hay un visitante esperando la página.
     * @return array<string, mixed>
     *
     * @throws ErrorMercadoPago
     */
    public function obtenerPago(string $id, int $segundosTimeout = self::SEGUNDOS_TIMEOUT): array
    {
        try {
            $respuesta = $this->solicitud($segundosTimeout)->get('/v1/payments/'.rawurlencode($id));
        } catch (ConnectionException $excepcion) {
            throw new ErrorMercadoPago('No se pudo conectar con Mercado Pago.', null, $excepcion);
        }

        return $this->interpretar($respuesta);
    }

    /**
     * @param  array<string, mixed>  $cuerpo
     * @return array<string, mixed>
     *
     * @throws ErrorMercadoPago
     */
    public function crearPreferencia(array $cuerpo, string $claveIdempotencia): array
    {
        try {
            $respuesta = $this->solicitud()
                ->withHeaders(['X-Idempotency-Key' => $claveIdempotencia])
                ->post('/checkout/preferences', $cuerpo);
        } catch (ConnectionException $excepcion) {
            throw new ErrorMercadoPago('No se pudo conectar con Mercado Pago.', null, $excepcion);
        }

        return $this->interpretar($respuesta);
    }

    /**
     * Operaciones que coinciden con la referencia externa, de la más reciente a la más antigua.
     *
     * @return list<array<string, mixed>>
     *
     * @throws ErrorMercadoPago
     */
    public function buscarPagosPorReferencia(string $referencia): array
    {
        try {
            $respuesta = $this->solicitud()->get('/v1/payments/search', [
                'external_reference' => $referencia,
                'sort' => 'date_created',
                'criteria' => 'desc',
            ]);
        } catch (ConnectionException $excepcion) {
            throw new ErrorMercadoPago('No se pudo conectar con Mercado Pago.', null, $excepcion);
        }

        $datos = $this->interpretar($respuesta);

        return array_values(array_filter($datos['results'] ?? [], is_array(...)));
    }

    private function solicitud(int $segundosTimeout = self::SEGUNDOS_TIMEOUT): PendingRequest
    {
        return Http::baseUrl((string) config('mercadopago.url_api'))
            ->withToken((string) config('mercadopago.access_token'))
            ->acceptJson()
            ->timeout($segundosTimeout);
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
