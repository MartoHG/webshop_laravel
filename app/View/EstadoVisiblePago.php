<?php

namespace App\View;

/**
 * Traduce el estado de cobro de un pedido a lo que ve el cliente en la página de pago: título, texto, color
 * y si la página tiene que seguir consultando. Los motivos de rechazo de Mercado Pago se explican en lenguaje llano.
 */
class EstadoVisiblePago
{
    /** @var array<string, string> */
    private const array MOTIVOS_DE_RECHAZO = [
        'cc_rejected_bad_filled_card_number' => 'Revisá el número de la tarjeta.',
        'cc_rejected_bad_filled_date' => 'Revisá la fecha de vencimiento de la tarjeta.',
        'cc_rejected_bad_filled_other' => 'Revisá los datos de la tarjeta.',
        'cc_rejected_bad_filled_security_code' => 'Revisá el código de seguridad de la tarjeta.',
        'cc_rejected_blacklist' => 'Mercado Pago no pudo aprobar el pago con esta tarjeta.',
        'cc_rejected_call_for_authorize' => 'Tu banco pide que autorices el pago. Llamá a tu banco e intentá de nuevo.',
        'cc_rejected_card_disabled' => 'La tarjeta está deshabilitada. Llamá a tu banco para activarla.',
        'cc_rejected_duplicated_payment' => 'Ya hay un pago igual. Revisá tu cuenta antes de volver a pagar.',
        'cc_rejected_high_risk' => 'El pago no pasó los controles de seguridad. Probá con otra tarjeta.',
        'cc_rejected_insufficient_amount' => 'La tarjeta no tiene saldo o límite suficiente.',
        'cc_rejected_invalid_installments' => 'La tarjeta no acepta esta cantidad de cuotas.',
        'cc_rejected_max_attempts' => 'Se superó la cantidad de intentos con esta tarjeta. Probá con otra.',
        'cc_rejected_other_reason' => 'Tu banco no aprobó el pago. Probá con otra tarjeta o llamá a tu banco.',
    ];

    /**
     * Un pedido pagado se muestra pagado aunque tenga un intento en revisión: la revisión es para el administrador.
     * Si el pedido no está pagado, la revisión tiene prioridad sobre el resto de los estados.
     *
     * @param  ?array{estado: string}  $intentoVigente
     * @return array{clave: string, titulo: string, texto: string, color: string}
     */
    public static function paraPago(string $estadoPago, ?array $intentoVigente, bool $hayPagoEnRevision = false): array
    {
        return match (true) {
            $estadoPago === 'pagado' => self::estado(
                'aprobado',
                '¡Listo! Recibimos tu pago.',
                'Ya lo estamos preparando. Te mandamos el detalle a tu email.',
                'amarillo',
            ),
            $hayPagoEnRevision || ($estadoPago === 'pendiente' && ($intentoVigente['estado'] ?? null) !== 'pendiente') => self::estado(
                'revision',
                'Estamos revisando tu pago.',
                'Recibimos tu pago y lo estamos revisando. Te contactamos por WhatsApp.',
                'celeste',
            ),
            $estadoPago === 'pendiente' && ($intentoVigente['estado'] ?? null) === 'pendiente' => self::estado(
                'pendiente',
                'Estamos confirmando tu pago.',
                'Puede tardar unos minutos. No lo vuelvas a intentar: esta página se actualiza sola cuando tengamos la respuesta.',
                'celeste',
            ),
            $estadoPago === 'habilitado' => self::estado(
                'rechazado',
                'El pago no se aprobó',
                'Mercado Pago no lo aprobó. Podés probar de nuevo con otra tarjeta u otra forma de pago.',
                'naranja',
            ),
            default => self::estado(
                'sin_pagos',
                'Este pedido ya no admite pagos',
                'Si tenés dudas, escribinos por WhatsApp.',
                'grafito',
            ),
        };
    }

    /**
     * Explicación en lenguaje llano del rechazo. Null cuando Mercado Pago no informó un motivo conocido.
     */
    public static function motivoDeRechazo(?string $detalle): ?string
    {
        return $detalle === null ? null : (self::MOTIVOS_DE_RECHAZO[$detalle] ?? null);
    }

    /**
     * @return array{clave: string, titulo: string, texto: string, color: string}
     */
    private static function estado(string $clave, string $titulo, string $texto, string $color): array
    {
        return [
            'clave' => $clave,
            'titulo' => $titulo,
            'texto' => $texto,
            'color' => $color,
        ];
    }
}
