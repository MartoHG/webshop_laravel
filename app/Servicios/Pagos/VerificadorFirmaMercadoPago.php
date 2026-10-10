<?php

namespace App\Servicios\Pagos;

use Illuminate\Http\Request;

/**
 * Valida la firma de las notificaciones de Mercado Pago: HMAC-SHA256 sobre un manifiesto con el id del dato,
 * el x-request-id y el ts de la cabecera x-signature.
 */
class VerificadorFirmaMercadoPago
{
    public function esValida(Request $solicitud): bool
    {
        $secreto = (string) config('mercadopago.webhook_secret');

        if ($secreto === '') {
            return false;
        }

        $firma = $this->partesDeFirma((string) $solicitud->header('x-signature', ''));
        $ts = $firma['ts'] ?? null;
        $hash = $firma['v1'] ?? null;
        $idSolicitud = (string) $solicitud->header('x-request-id', '');

        if ($ts === null || $hash === null || $idSolicitud === '') {
            return false;
        }

        $idDelDato = $this->idDelDato($solicitud);
        $manifiesto = ($idDelDato !== null ? "id:{$idDelDato};" : '')."request-id:{$idSolicitud};ts:{$ts};";

        return hash_equals(hash_hmac('sha256', $manifiesto, $secreto), $hash);
    }

    /**
     * Lee data.id (o data_id) de la query cruda: PHP convierte el punto en guion bajo al parsear la query.
     */
    public function idDelDato(Request $solicitud): ?string
    {
        $idDelDato = $this->valorDeQuery($solicitud, 'data.id') ?? $this->valorDeQuery($solicitud, 'data_id');

        return $idDelDato === null ? null : strtolower($idDelDato);
    }

    /**
     * @return array<string, string>
     */
    private function partesDeFirma(string $cabecera): array
    {
        $partes = [];

        foreach (explode(',', $cabecera) as $parte) {
            $segmentos = explode('=', trim($parte), 2);

            if (count($segmentos) === 2) {
                $partes[$segmentos[0]] = $segmentos[1];
            }
        }

        return $partes;
    }

    private function valorDeQuery(Request $solicitud, string $clave): ?string
    {
        foreach (explode('&', (string) $solicitud->server->get('QUERY_STRING', '')) as $par) {
            [$nombre, $valor] = array_pad(explode('=', $par, 2), 2, '');

            if (urldecode($nombre) === $clave && $valor !== '') {
                return urldecode($valor);
            }
        }

        return null;
    }
}
