<?php

namespace App\Servicios\Pagos;

use App\Enums\EstadoIntentoPago;
use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Enums\TipoMedioPago;
use App\Models\ItemPedido;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\ReglaPrecioPago;
use App\Models\Usuario;
use App\Notifications\Pedidos\AlertaPagoEnRevision;
use App\Notifications\Pedidos\PagoAprobado;
use App\Servicios\Auditoria\AuditoriaService;
use App\Servicios\Precios\CompensacionPagoService;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class PagoService
{
    private const string MENSAJE_EN_PROCESO = 'Ya hay un pago en proceso. Esperá unos minutos o escribinos por WhatsApp.';

    private const string MENSAJE_CONFIRMANDO = 'Estamos confirmando tu pago. No lo vuelvas a intentar: en unos minutos te confirmamos el resultado.';

    private const string MENSAJE_PAGADO = 'Recibimos tu pago. ¡Gracias por tu compra!';

    private const string MENSAJE_RECHAZADO = 'El pago fue rechazado. Podés intentar de nuevo con otra tarjeta u otra modalidad de pago.';

    private const string MENSAJE_NO_PROCESADO = 'No pudimos procesar el pago. Revisá los datos e intentá de nuevo.';

    private const string MENSAJE_REVISION = 'Recibimos tu pago y lo estamos revisando. Te contactamos por WhatsApp.';

    private const string MENSAJE_SIN_TOKEN = 'El pago con tarjeta no está disponible en este momento. Escribinos por WhatsApp.';

    private const string MENSAJE_PREFERENCIA = 'No pudimos preparar el pago con tu cuenta de Mercado Pago. Intentá de nuevo en unos minutos.';

    private const string MENSAJE_EN_REVISION = 'Hay un pago en revisión para este pedido. Te contactamos por WhatsApp.';

    /** @var list<int> Respuestas 4xx que no garantizan que Mercado Pago no haya procesado la operación. */
    private const array ESTADOS_4XX_AMBIGUOS = [408, 409, 425];

    public function __construct(
        private AuditoriaService $auditoria,
        private CompensacionPagoService $compensacion,
        private MercadoPagoCliente $mercadoPago,
    ) {}

    /**
     * El link de pago solo funciona mientras el pedido espera pago: habilitado o con un pago en curso.
     *
     * @throws HttpException
     */
    public function pedidoPorToken(string $token): Pedido
    {
        $pedido = Pedido::query()
            ->where('token_pago', $token)
            ->where('estado_pedido', EstadoPedido::PendienteCoordinacion)
            ->whereIn('estado_pago', [EstadoPago::Habilitado, EstadoPago::Pendiente])
            ->first();

        if (! $pedido instanceof Pedido) {
            abort(404);
        }

        return $pedido;
    }

    /**
     * Consulta de estado para el polling del cliente. Sigue respondiendo después del pago para mostrar el resultado.
     *
     * @throws HttpException
     */
    public function pedidoDelToken(string $token): Pedido
    {
        $pedido = Pedido::query()->where('token_pago', $token)->first();

        if (! $pedido instanceof Pedido) {
            abort(404);
        }

        return $pedido;
    }

    /**
     * Vista pública del pedido: sin DNI, email, teléfono, dirección ni token.
     *
     * @return array<string, mixed>
     */
    public function resumen(Pedido $pedido): array
    {
        $pedido->loadMissing('items');

        return [
            'numero' => $pedido->numero,
            'nombre_cliente' => Str::before(trim($pedido->nombre_cliente), ' '),
            'items' => $pedido->items->map(fn (ItemPedido $item): array => [
                'nombre_producto' => $item->nombre_producto,
                'color' => $item->color,
                'etiqueta' => $item->etiqueta,
                'cantidad' => $item->cantidad,
                'subtotal' => $item->subtotal,
            ])->values()->all(),
            'subtotal_productos' => $pedido->subtotal_productos,
            'expreso' => $pedido->expreso,
            'costo_envio' => $pedido->costo_envio,
            'total_base' => $pedido->total_base,
            'estado_pago' => $pedido->estado_pago->value,
            'opciones' => $this->opciones($pedido),
            'public_key' => config('mercadopago.public_key'),
            'intento_vigente' => $this->intentoVigente($pedido),
        ];
    }

    /**
     * @return array{estado_pago: string, intento_vigente: ?array<string, mixed>}
     */
    public function estado(Pedido $pedido): array
    {
        return [
            'estado_pago' => $pedido->estado_pago->value,
            'intento_vigente' => $this->intentoVigente($pedido),
        ];
    }

    /**
     * @return array{id: int, estado: string, tipo_medio_pago: string, cuotas: int, importe_cobrado: string}|null
     */
    public function intentoVigente(Pedido $pedido): ?array
    {
        $pago = $this->pagoVigente($pedido);

        if (! $pago instanceof Pago) {
            return null;
        }

        return [
            'id' => $pago->getKey(),
            'estado' => $pago->estado->value,
            'tipo_medio_pago' => $pago->tipo_medio_pago->value,
            'cuotas' => $pago->cuotas,
            'importe_cobrado' => $pago->importe_cobrado,
        ];
    }

    /**
     * Intento que todavía puede cambiar de resultado: creado o pendiente. Null cuando no hay ninguno.
     */
    public function pagoVigente(Pedido $pedido): ?Pago
    {
        return Pago::query()
            ->where('pedido_id', $pedido->getKey())
            ->whereIn('estado', EstadoIntentoPago::abiertos())
            ->latest('id')
            ->first();
    }

    /**
     * Datos que necesita la página de pago para mostrar el Brick de un intento ya creado.
     *
     * @return array{pago_id: int, importe: string, tipo_medio_pago: string, cuotas: int, public_key: ?string, preference_id: ?string}
     */
    public function configuracionDelIntento(Pago $pago): array
    {
        return [
            'pago_id' => $pago->getKey(),
            'importe' => (string) $pago->importe_cobrado,
            'tipo_medio_pago' => $pago->tipo_medio_pago->value,
            'cuotas' => $pago->cuotas,
            'public_key' => config('mercadopago.public_key'),
            'preference_id' => $pago->id_orden_mercado_pago,
        ];
    }

    /**
     * Detalle que dejó Mercado Pago en el último intento rechazado, para explicarlo en la página. Null si no hay.
     */
    public function motivoDelUltimoRechazo(Pedido $pedido): ?string
    {
        $detalle = Pago::query()
            ->where('pedido_id', $pedido->getKey())
            ->where('estado', EstadoIntentoPago::Rechazado->value)
            ->latest('id')
            ->value('detalle_estado');

        return is_string($detalle) ? $detalle : null;
    }

    /**
     * Fija la modalidad elegida: el porcentaje y el importe quedan guardados en el intento.
     *
     * Para dinero en cuenta crea además la preferencia de Mercado Pago, fuera de cualquier transacción.
     *
     * @return array{pago_id: int, importe: string, tipo_medio_pago: string, cuotas: int, public_key: ?string, preference_id: ?string}
     *
     * @throws ValidationException
     */
    public function iniciarIntento(Pedido $pedido, int $reglaId): array
    {
        $pago = DB::transaction(function () use ($pedido, $reglaId): Pago {
            $pedido = $this->bloquearPedido($pedido);
            $this->exigirPagoHabilitado($pedido);

            $regla = $this->reglaDisponible($reglaId);
            $importeBase = bcadd((string) $pedido->total_base, '0', 2);
            $porcentaje = (string) $regla->porcentaje;
            $importeCobrado = $this->compensacion->calcularImporte($importeBase, $porcentaje);
            $tipo = $regla->tipo_medio_pago;

            Pago::query()
                ->where('pedido_id', $pedido->getKey())
                ->where('estado', EstadoIntentoPago::Creado->value)
                ->update(['estado' => EstadoIntentoPago::Cancelado->value, 'updated_at' => now()]);

            $pago = Pago::query()->create([
                'pedido_id' => $pedido->getKey(),
                'regla_precio_pago_id' => $regla->getKey(),
                'clave_idempotencia' => (string) Str::uuid(),
                'tipo_medio_pago' => $tipo,
                'cuotas' => $regla->cuotas,
                'importe_base' => $importeBase,
                'porcentaje_aplicado' => $porcentaje,
                'importe_cobrado' => $importeCobrado,
                'estado' => EstadoIntentoPago::Creado,
            ]);

            $this->auditoria->registrar('pago.intento_creado', $pago, [], [
                'tipo_medio_pago' => $tipo->value,
                'cuotas' => $regla->cuotas,
                'porcentaje_aplicado' => $porcentaje,
                'importe_base' => $importeBase,
                'importe_cobrado' => $importeCobrado,
            ]);

            return $pago;
        });

        $pago->refresh();

        if ($pago->tipo_medio_pago === TipoMedioPago::DineroEnCuenta) {
            $this->vincularPreferencia($pedido, $pago);
            $pago->refresh();
        }

        return $this->configuracionDelIntento($pago);
    }

    /**
     * La preferencia restringe el cobro a dinero en cuenta. El webhook verifica igualmente medio, cuotas e importe.
     *
     * @throws ValidationException
     */
    private function vincularPreferencia(Pedido $pedido, Pago $pago): void
    {
        try {
            $preferencia = $this->mercadoPago->crearPreferencia(
                $this->cuerpoDePreferencia($pedido, $pago),
                $pago->clave_idempotencia,
            );
            $idPreferencia = (string) ($preferencia['id'] ?? '');

            if ($idPreferencia === '') {
                throw new ErrorMercadoPago('Mercado Pago no devolvió el id de la preferencia.');
            }
        } catch (ErrorMercadoPago $error) {
            Log::warning('No se pudo crear la preferencia de cuenta de Mercado Pago.', [
                'pago_id' => $pago->getKey(),
                'status' => $error->status,
            ]);

            $this->cancelarIntentoSinCobro($pedido, $pago);

            throw ValidationException::withMessages(['pago' => self::MENSAJE_PREFERENCIA]);
        }

        DB::transaction(function () use ($pedido, $pago, $idPreferencia): void {
            $this->bloquearPedido($pedido);
            $pagoBloqueado = $this->bloquearPago($pago);
            $pagoBloqueado->id_orden_mercado_pago = $idPreferencia;
            $pagoBloqueado->save();
        });
    }

    private function cancelarIntentoSinCobro(Pedido $pedido, Pago $pago): void
    {
        DB::transaction(function () use ($pedido, $pago): void {
            $this->bloquearPedido($pedido);
            $pagoBloqueado = $this->bloquearPago($pago);

            if ($pagoBloqueado->estado !== EstadoIntentoPago::Creado) {
                return;
            }

            $pagoBloqueado->estado = EstadoIntentoPago::Cancelado;
            $pagoBloqueado->detalle_estado = 'sin_preferencia';
            $pagoBloqueado->save();
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function cuerpoDePreferencia(Pedido $pedido, Pago $pago): array
    {
        $urlDelPedido = url('/pago/'.$pedido->token_pago);

        return [
            'items' => [[
                'title' => "Pedido {$pedido->numero} - El Refugio",
                'quantity' => 1,
                // Único lugar donde el importe pasa a float: la API lo recibe como número en el JSON.
                'unit_price' => (float) $pago->importe_cobrado,
                'currency_id' => config('mercadopago.moneda'),
            ]],
            'external_reference' => $this->referenciaExterna($pedido, $pago),
            'notification_url' => url('/webhooks/mercadopago'),
            'back_urls' => [
                'success' => $urlDelPedido,
                'pending' => $urlDelPedido,
                'failure' => $urlDelPedido,
            ],
            'auto_return' => 'approved',
            'payment_methods' => [
                'excluded_payment_types' => array_map(
                    fn (string $tipo): array => ['id' => $tipo],
                    ['credit_card', 'debit_card', 'ticket', 'atm', 'bank_transfer', 'prepaid_card'],
                ),
                'installments' => 1,
            ],
            'statement_descriptor' => (string) config('mercadopago.descriptor'),
        ];
    }

    private function hayPagoEnRevision(Pedido $pedido): bool
    {
        return Pago::query()
            ->where('pedido_id', $pedido->getKey())
            ->where('estado', EstadoIntentoPago::RequiereRevision->value)
            ->exists();
    }

    private function aceptarRevision(Pedido $pedido, Pago $pago): void
    {
        if ($pedido->estado_pedido === EstadoPedido::Cancelado) {
            throw ValidationException::withMessages(['pedido' => 'No se puede aceptar un pago de un pedido cancelado.']);
        }

        $otroPagado = Pago::query()
            ->where('pedido_id', $pedido->getKey())
            ->where('id', '!=', $pago->getKey())
            ->where('estado', EstadoIntentoPago::Pagado->value)
            ->exists();

        if ($otroPagado) {
            throw ValidationException::withMessages(['pedido' => 'El pedido ya tiene otro pago confirmado.']);
        }

        $pagadoEn = $pago->pagado_en ?? now();
        $yaPagado = $pedido->estado_pago === EstadoPago::Pagado;

        $pago->estado = EstadoIntentoPago::Pagado;
        $pago->detalle_estado = 'aceptado_por_administrador';
        $pago->pagado_en = $pagadoEn;
        $pago->save();

        $pedido->estado_pedido = EstadoPedido::Confirmado;
        $pedido->estado_pago = EstadoPago::Pagado;
        $pedido->pagado_en = $pagadoEn;
        $pedido->save();

        Pago::query()
            ->where('pedido_id', $pedido->getKey())
            ->where('id', '!=', $pago->getKey())
            ->whereIn('estado', EstadoIntentoPago::abiertos())
            ->update(['estado' => EstadoIntentoPago::Cancelado->value, 'updated_at' => now()]);

        if (! $yaPagado) {
            $this->notificarPagoAprobado($pedido, $pago);
        }
    }

    private function descartarRevision(Pedido $pedido, Pago $pago): void
    {
        $pago->estado = EstadoIntentoPago::Cancelado;
        $pago->detalle_estado = 'descartado_por_administrador';
        $pago->save();

        if (
            $pedido->estado_pedido !== EstadoPedido::Cancelado
            && $pedido->estado_pago === EstadoPago::Pendiente
            && ! $this->hayOtroPagoPendiente($pedido, $pago)
            && ! $this->hayPagoEnRevision($pedido)
        ) {
            $pedido->estado_pago = EstadoPago::Habilitado;
            $pedido->save();
        }
    }

    /**
     * Valida el intento contra lo fijado antes de llamar a Mercado Pago. Si la API no confirma el pago,
     * el intento queda pendiente y el pedido no vuelve a habilitarse hasta que se resuelva.
     *
     * @param  array{metodo_seleccionado: string, token: string, payment_method_id: string, issuer_id?: string|int|null, installments: int|string, transaction_amount: string, payer: array{email: string, identification: array{type: string, number: string}}}  $datos
     * @return array{estado: string, mensaje: string}
     *
     * @throws ValidationException
     */
    public function procesarConTarjeta(Pedido $pedido, Pago $pago, array $datos): array
    {
        $this->validarIntentoConTarjeta($pedido, $pago, $datos);

        $pago = $this->marcarPendiente($pedido, $pago);

        try {
            $respuesta = $this->mercadoPago->crearPago(
                $this->cuerpoDelPago($pedido, $pago, $datos),
                $pago->clave_idempotencia,
            );
        } catch (ErrorMercadoPago $error) {
            if (! $this->esRechazoDefinitivo($error)) {
                Log::warning('Pago con tarjeta sin confirmar: queda pendiente.', ['pago_id' => $pago->getKey(), 'status' => $error->status]);

                return ['estado' => EstadoIntentoPago::Pendiente->value, 'mensaje' => self::MENSAJE_CONFIRMANDO];
            }

            $this->rechazarPorError($pedido, $pago, 'error_api_'.$error->status);

            return ['estado' => EstadoIntentoPago::Rechazado->value, 'mensaje' => self::MENSAJE_NO_PROCESADO];
        }

        $pagoAplicado = $this->aplicarPagoMercadoPago($respuesta);

        if (! $pagoAplicado instanceof Pago) {
            return ['estado' => EstadoIntentoPago::Pendiente->value, 'mensaje' => self::MENSAJE_CONFIRMANDO];
        }

        return [
            'estado' => $pagoAplicado->estado->value,
            'mensaje' => $this->mensajeDelEstado($pagoAplicado->estado),
        ];
    }

    /**
     * Único punto que marca un pago como pagado o cambia su estado a partir de Mercado Pago. Es idempotente:
     * aplicar dos veces la misma notificación no repite efectos.
     *
     * @param  array<string, mixed>  $pagoMp
     * @return ?Pago Null cuando la operación no corresponde a ningún intento local.
     */
    public function aplicarPagoMercadoPago(array $pagoMp): ?Pago
    {
        $localizado = $this->localizarPago($pagoMp);

        if (! $localizado instanceof Pago) {
            Log::warning('Operación de Mercado Pago sin intento de pago local.', [
                'id_pago_mercado_pago' => $pagoMp['id'] ?? null,
                'status' => $pagoMp['status'] ?? null,
            ]);

            return null;
        }

        return DB::transaction(function () use ($pagoMp, $localizado): Pago {
            $pedido = Pedido::query()->lockForUpdate()->findOrFail($localizado->pedido_id);
            $pago = $this->bloquearPago($localizado);

            return match ((string) ($pagoMp['status'] ?? '')) {
                'approved' => $this->aplicarAprobado($pedido, $pago, $pagoMp),
                'rejected', 'cancelled' => $this->aplicarNoAprobado($pedido, $pago, $pagoMp),
                'in_process', 'pending', 'authorized' => $this->aplicarEnCurso($pedido, $pago, $pagoMp),
                'refunded', 'charged_back' => $this->aplicarDevuelto($pago, $pagoMp),
                default => $this->ignorarEstado($pago, $pagoMp),
            };
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarIntentos(Pedido $pedido): array
    {
        return Pago::query()
            ->where('pedido_id', $pedido->getKey())
            ->orderByDesc('id')
            ->get()
            ->map(fn (Pago $pago): array => $this->representarIntento($pago))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function representarIntento(Pago $pago): array
    {
        return [
            'id' => $pago->getKey(),
            'estado' => $pago->estado->value,
            'tipo_medio_pago' => $pago->tipo_medio_pago->value,
            'cuotas' => $pago->cuotas,
            'importe_base' => $pago->importe_base,
            'porcentaje_aplicado' => $pago->porcentaje_aplicado,
            'importe_cobrado' => $pago->importe_cobrado,
            'id_pago_mercado_pago' => $pago->id_pago_mercado_pago,
            'id_orden_mercado_pago' => $pago->id_orden_mercado_pago,
            'medio_pago_mercado_pago' => $pago->medio_pago_mercado_pago,
            'detalle_estado' => $pago->detalle_estado,
            'pagado_en' => $pago->pagado_en?->toIso8601String(),
            'creado_en' => $pago->created_at?->toIso8601String(),
        ];
    }

    /**
     * Resuelve un pago en revisión con una nota obligatoria, que queda en la auditoría.
     *
     * Mientras haya un pago en revisión el pedido no admite nuevos intentos de pago ni cancelación. Al ingresar a
     * revisión el pedido no cambia: si estaba pendiente, sigue pendiente hasta que el administrador lo resuelva aquí.
     *
     * - aceptar: confirma el pedido, salvo que esté cancelado o ya tenga otro pago aprobado.
     * - descartar: usar después de devolver el dinero en Mercado Pago. El intento queda cancelado y, si el pedido
     *   estaba pendiente sin otro pago en curso ni en revisión, vuelve a habilitado.
     *
     * @throws ValidationException
     */
    public function resolverRevision(Usuario $administrador, Pedido $pedido, Pago $pago, string $decision, string $nota): Pago
    {
        return DB::transaction(function () use ($administrador, $pedido, $pago, $decision, $nota): Pago {
            $pedido = $this->bloquearPedido($pedido);
            $pago = $this->bloquearPago($pago);

            if ((int) $pago->pedido_id !== (int) $pedido->getKey()) {
                throw ValidationException::withMessages(['pago' => 'El pago no corresponde a este pedido.']);
            }

            if ($pago->estado !== EstadoIntentoPago::RequiereRevision) {
                throw ValidationException::withMessages(['pago' => 'Solo se resuelven pagos en revisión.']);
            }

            $anteriores = [
                'estado' => $pago->estado->value,
                'detalle_estado' => $pago->detalle_estado,
                'estado_pedido' => $pedido->estado_pedido->value,
                'estado_pago' => $pedido->estado_pago->value,
            ];

            match ($decision) {
                'aceptar' => $this->aceptarRevision($pedido, $pago),
                'descartar' => $this->descartarRevision($pedido, $pago),
                default => throw ValidationException::withMessages(['decision' => 'La decisión no es válida.']),
            };

            $this->auditoria->registrar('pago.revision_resuelta', $pago, $anteriores, [
                'decision' => $decision,
                'nota' => $nota,
                'estado' => $pago->estado->value,
                'estado_pedido' => $pedido->estado_pedido->value,
                'estado_pago' => $pedido->estado_pago->value,
            ], $administrador);

            return $pago;
        });
    }

    /**
     * @return list<array{regla_id: int, tipo_medio_pago: string, cuotas: int, importe: string}>
     */
    private function opciones(Pedido $pedido): array
    {
        if ($pedido->total_base === null) {
            return [];
        }

        return array_map(
            fn (array $opcion): array => Arr::only($opcion, ['regla_id', 'tipo_medio_pago', 'cuotas', 'importe']),
            $this->compensacion->opcionesDePago((string) $pedido->total_base),
        );
    }

    /**
     * @throws ValidationException
     */
    private function reglaDisponible(int $reglaId): ReglaPrecioPago
    {
        $regla = ReglaPrecioPago::query()->find($reglaId);

        if (! $regla instanceof ReglaPrecioPago) {
            throw ValidationException::withMessages(['modalidad' => 'La modalidad de pago seleccionada no está disponible.']);
        }

        return $this->compensacion->obtenerReglaActiva($regla->tipo_medio_pago, $regla->cuotas);
    }

    /**
     * @throws ValidationException
     */
    private function exigirPagoHabilitado(Pedido $pedido): void
    {
        if ($pedido->estado_pago === EstadoPago::Pendiente) {
            throw ValidationException::withMessages(['pago' => self::MENSAJE_EN_PROCESO]);
        }

        if ($this->hayPagoEnRevision($pedido)) {
            throw ValidationException::withMessages(['pago' => self::MENSAJE_EN_REVISION]);
        }

        if ($pedido->estado_pedido !== EstadoPedido::PendienteCoordinacion || $pedido->estado_pago !== EstadoPago::Habilitado) {
            throw ValidationException::withMessages(['pago' => 'El link de pago ya no está disponible para este pedido.']);
        }

        if ($pedido->total_base === null) {
            throw ValidationException::withMessages(['pago' => 'El total del pedido todavía no está definido.']);
        }
    }

    /**
     * @param  array<string, mixed>  $datos
     *
     * @throws ValidationException
     */
    private function validarIntentoConTarjeta(Pedido $pedido, Pago $pago, array $datos): void
    {
        if ((int) $pago->pedido_id !== (int) $pedido->getKey()) {
            throw ValidationException::withMessages(['pago' => 'El intento de pago no corresponde a este pedido.']);
        }

        if (trim((string) config('mercadopago.access_token')) === '') {
            throw ValidationException::withMessages(['pago' => self::MENSAJE_SIN_TOKEN]);
        }

        if ($pago->estado !== EstadoIntentoPago::Creado) {
            throw ValidationException::withMessages(['pago' => 'Este intento de pago ya no está disponible. Elegí la modalidad de nuevo.']);
        }

        $tipo = $pago->tipo_medio_pago;

        if ($tipo === TipoMedioPago::DineroEnCuenta) {
            throw ValidationException::withMessages(['metodo_seleccionado' => 'Esta modalidad se paga desde tu cuenta de Mercado Pago, no con tarjeta.']);
        }

        if ($datos['metodo_seleccionado'] !== $this->metodoDeTarjetaPara($tipo)) {
            throw ValidationException::withMessages(['metodo_seleccionado' => 'El medio de pago no coincide con la modalidad elegida.']);
        }

        if ((int) $datos['installments'] !== $pago->cuotas) {
            throw ValidationException::withMessages(['cuotas' => 'La cantidad de cuotas no coincide con la modalidad elegida.']);
        }

        if (bccomp((string) $datos['transaction_amount'], (string) $pago->importe_cobrado, 2) !== 0) {
            throw ValidationException::withMessages(['importe' => 'El importe no coincide con el que te mostramos. Actualizá la página e intentá de nuevo.']);
        }
    }

    private function metodoDeTarjetaPara(TipoMedioPago $tipo): string
    {
        return $tipo === TipoMedioPago::Credito ? 'credit_card' : 'debit_card';
    }

    /**
     * Primera transacción del cobro: deja el intento y el pedido en pendiente antes de llamar a Mercado Pago.
     *
     * @throws ValidationException
     */
    private function marcarPendiente(Pedido $pedido, Pago $pago): Pago
    {
        return DB::transaction(function () use ($pedido, $pago): Pago {
            $pedido = $this->bloquearPedido($pedido);
            $pago = $this->bloquearPago($pago);

            $this->exigirPagoHabilitado($pedido);

            if ((int) $pago->pedido_id !== (int) $pedido->getKey() || $pago->estado !== EstadoIntentoPago::Creado) {
                throw ValidationException::withMessages(['pago' => 'Este intento de pago ya no está disponible. Elegí la modalidad de nuevo.']);
            }

            $pago->estado = EstadoIntentoPago::Pendiente;
            $pago->save();

            $pedido->estado_pago = EstadoPago::Pendiente;
            $pedido->save();

            return $pago;
        });
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    private function cuerpoDelPago(Pedido $pedido, Pago $pago, array $datos): array
    {
        $cuerpo = [
            // Único lugar donde el importe pasa a float: la API lo exige como número en el JSON.
            'transaction_amount' => (float) $pago->importe_cobrado,
            'token' => $datos['token'],
            'description' => "Pedido {$pedido->numero} - El Refugio",
            'installments' => $pago->cuotas,
            'payment_method_id' => $datos['payment_method_id'],
            'payer' => [
                'email' => $datos['payer']['email'],
                'identification' => [
                    'type' => $datos['payer']['identification']['type'],
                    'number' => $datos['payer']['identification']['number'],
                ],
            ],
            'external_reference' => $this->referenciaExterna($pedido, $pago),
            'statement_descriptor' => (string) config('mercadopago.descriptor'),
            'notification_url' => url('/webhooks/mercadopago'),
            'metadata' => ['pago_id' => $pago->getKey()],
        ];

        if (($datos['issuer_id'] ?? null) !== null) {
            $cuerpo['issuer_id'] = $datos['issuer_id'];
        }

        return $cuerpo;
    }

    private function referenciaExterna(Pedido $pedido, Pago $pago): string
    {
        return "{$pedido->numero}:{$pago->getKey()}";
    }

    /**
     * Solo los 4xx que no son ambiguos prueban que Mercado Pago no procesó la operación.
     */
    private function esRechazoDefinitivo(ErrorMercadoPago $error): bool
    {
        return $error->status !== null
            && $error->status >= 400
            && $error->status < 500
            && ! in_array($error->status, self::ESTADOS_4XX_AMBIGUOS, true);
    }

    private function rechazarPorError(Pedido $pedido, Pago $pago, string $detalle): void
    {
        DB::transaction(function () use ($pedido, $pago, $detalle): void {
            $pedido = $this->bloquearPedido($pedido);
            $pago = $this->bloquearPago($pago);

            if (! in_array($pago->estado, EstadoIntentoPago::abiertos(), true)) {
                return;
            }

            $this->marcarRechazado($pedido, $pago, $detalle);
        });
    }

    private function marcarRechazado(Pedido $pedido, Pago $pago, string $detalle): void
    {
        $anterior = $pago->estado->value;

        $pago->estado = EstadoIntentoPago::Rechazado;
        $pago->detalle_estado = $detalle;
        $pago->save();

        if ($pedido->estado_pago === EstadoPago::Pendiente && ! $this->hayOtroPagoPendiente($pedido, $pago)) {
            $pedido->estado_pago = EstadoPago::Habilitado;
            $pedido->save();
        }

        $this->auditoria->registrar('pago.rechazado', $pago, ['estado' => $anterior], [
            'estado' => EstadoIntentoPago::Rechazado->value,
            'motivo' => $detalle,
        ]);
    }

    /**
     * Cierra un intento pendiente que Mercado Pago nunca registró: sin id y sin operaciones con su referencia.
     * Si el pedido no tiene otro pago en curso ni en revisión, vuelve a habilitado para que el link funcione de nuevo.
     * Si Mercado Pago informa el pago más tarde, aplicarPagoMercadoPago lo trata como una aprobación tardía.
     */
    public function cancelarSinPagoEnMercadoPago(Pago $pago): bool
    {
        return DB::transaction(function () use ($pago): bool {
            $pedido = Pedido::query()->lockForUpdate()->findOrFail($pago->pedido_id);
            $pagoBloqueado = $this->bloquearPago($pago);

            if ($pagoBloqueado->estado !== EstadoIntentoPago::Pendiente || $pagoBloqueado->id_pago_mercado_pago !== null) {
                return false;
            }

            $pagoBloqueado->estado = EstadoIntentoPago::Cancelado;
            $pagoBloqueado->detalle_estado = 'sin_pago_en_mercado_pago';
            $pagoBloqueado->save();

            if (
                $pedido->estado_pago === EstadoPago::Pendiente
                && ! $this->hayOtroPagoPendiente($pedido, $pagoBloqueado)
                && ! $this->hayPagoEnRevision($pedido)
            ) {
                $pedido->estado_pago = EstadoPago::Habilitado;
                $pedido->save();
            }

            $this->auditoria->registrar('pago.cancelado_por_conciliacion', $pagoBloqueado, ['estado' => EstadoIntentoPago::Pendiente->value], [
                'estado' => EstadoIntentoPago::Cancelado->value,
                'motivo' => 'sin_pago_en_mercado_pago',
            ]);

            return true;
        });
    }

    private function hayOtroPagoPendiente(Pedido $pedido, Pago $pago): bool
    {
        return Pago::query()
            ->where('pedido_id', $pedido->getKey())
            ->where('id', '!=', $pago->getKey())
            ->where('estado', EstadoIntentoPago::Pendiente->value)
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $pagoMp
     */
    private function aplicarAprobado(Pedido $pedido, Pago $pago, array $pagoMp): Pago
    {
        $mismoPago = ! $this->idDistinto($pago, $pagoMp);

        if ($pago->estado === EstadoIntentoPago::Pagado && $mismoPago) {
            return $pago;
        }

        if (! $mismoPago) {
            return $pago->estado === EstadoIntentoPago::Pagado
                ? $this->registrarInconsistencia($pago, $pagoMp, 'id_distinto')
                : $this->enRevision($pago, $pagoMp, 'id_distinto');
        }

        $motivo = $this->motivoDeRevision($pedido, $pago, $pagoMp);

        if ($motivo !== null) {
            return $this->enRevision($pago, $pagoMp, $motivo);
        }

        $pagadoEn = $this->fechaDeAprobacion($pagoMp);
        $this->vincular($pago, $pagoMp);

        $pago->estado = EstadoIntentoPago::Pagado;
        $pago->detalle_estado = $this->detalleDe($pagoMp);
        $pago->pagado_en = $pagadoEn;
        $pago->save();

        $anteriores = [
            'estado_pedido' => $pedido->estado_pedido->value,
            'estado_pago' => $pedido->estado_pago->value,
        ];

        $pedido->estado_pedido = EstadoPedido::Confirmado;
        $pedido->estado_pago = EstadoPago::Pagado;
        $pedido->pagado_en = $pagadoEn;
        $pedido->save();

        Pago::query()
            ->where('pedido_id', $pedido->getKey())
            ->where('id', '!=', $pago->getKey())
            ->whereIn('estado', EstadoIntentoPago::abiertos())
            ->update(['estado' => EstadoIntentoPago::Cancelado->value, 'updated_at' => now()]);

        $this->auditoria->registrar('pedido.pagado', $pedido, $anteriores, [
            'estado_pedido' => EstadoPedido::Confirmado->value,
            'estado_pago' => EstadoPago::Pagado->value,
            'pagado_en' => $pagadoEn->toIso8601String(),
            'pago_id' => $pago->getKey(),
        ]);

        $this->notificarPagoAprobado($pedido, $pago);

        return $pago;
    }

    /**
     * Devuelve el motivo por el que un pago aprobado no puede confirmar el pedido, o null si todo coincide.
     *
     * @param  array<string, mixed>  $pagoMp
     */
    private function motivoDeRevision(Pedido $pedido, Pago $pago, array $pagoMp): ?string
    {
        if ($pedido->estado_pedido === EstadoPedido::Cancelado) {
            return 'pedido_cancelado';
        }

        $otroPagado = Pago::query()
            ->where('pedido_id', $pedido->getKey())
            ->where('id', '!=', $pago->getKey())
            ->where('estado', EstadoIntentoPago::Pagado->value)
            ->exists();

        if ($otroPagado) {
            return 'pedido_ya_pagado';
        }

        if ($pedido->estado_pedido !== EstadoPedido::PendienteCoordinacion || ! in_array($pedido->estado_pago, [EstadoPago::Habilitado, EstadoPago::Pendiente], true)) {
            return 'pedido_no_pagable';
        }

        if (($pagoMp['currency_id'] ?? null) !== config('mercadopago.moneda')) {
            return 'moneda_distinta';
        }

        if (bccomp($this->importeDe($pagoMp['transaction_amount'] ?? null), (string) $pago->importe_cobrado, 2) !== 0) {
            return 'importe_distinto';
        }

        if ((int) ($pagoMp['installments'] ?? 0) !== $pago->cuotas) {
            return 'cuotas_distintas';
        }

        if (($pagoMp['payment_type_id'] ?? null) !== $this->tipoDeMercadoPagoPara($pago->tipo_medio_pago)) {
            return 'medio_distinto';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $pagoMp
     */
    private function aplicarNoAprobado(Pedido $pedido, Pago $pago, array $pagoMp): Pago
    {
        if ($this->idDistinto($pago, $pagoMp)) {
            return $this->registrarInconsistencia($pago, $pagoMp, 'id_distinto');
        }

        if (! in_array($pago->estado, EstadoIntentoPago::abiertos(), true)) {
            return $pago;
        }

        $this->vincular($pago, $pagoMp);
        $this->marcarRechazado($pedido, $pago, $this->detalleDe($pagoMp) ?? 'rechazado_por_mercado_pago');

        return $pago;
    }

    /**
     * @param  array<string, mixed>  $pagoMp
     */
    private function aplicarEnCurso(Pedido $pedido, Pago $pago, array $pagoMp): Pago
    {
        if ($this->idDistinto($pago, $pagoMp)) {
            return $this->registrarInconsistencia($pago, $pagoMp, 'id_distinto');
        }

        if (! in_array($pago->estado, EstadoIntentoPago::abiertos(), true)) {
            return $pago;
        }

        $this->vincular($pago, $pagoMp);
        $pago->estado = EstadoIntentoPago::Pendiente;
        $pago->detalle_estado = $this->detalleDe($pagoMp);
        $pago->save();

        if ($pedido->estado_pedido === EstadoPedido::PendienteCoordinacion && $pedido->estado_pago === EstadoPago::Habilitado) {
            $pedido->estado_pago = EstadoPago::Pendiente;
            $pedido->save();
        }

        return $pago;
    }

    /**
     * @param  array<string, mixed>  $pagoMp
     */
    private function aplicarDevuelto(Pago $pago, array $pagoMp): Pago
    {
        if ($this->idDistinto($pago, $pagoMp)) {
            return $this->registrarInconsistencia($pago, $pagoMp, 'id_distinto');
        }

        return $this->enRevision($pago, $pagoMp, ($pagoMp['status'] ?? null) === 'charged_back' ? 'contracargo' : 'reembolsado');
    }

    /**
     * El pedido no cambia: si estaba pendiente, queda pendiente y bloquea nuevos intentos hasta que el
     * administrador resuelva la revisión (ver resolverRevision).
     *
     * @param  array<string, mixed>  $pagoMp
     */
    private function enRevision(Pago $pago, array $pagoMp, string $motivo): Pago
    {
        if ($pago->estado === EstadoIntentoPago::RequiereRevision && $pago->detalle_estado === $motivo) {
            return $pago;
        }

        $anterior = $pago->estado->value;

        $this->vincular($pago, $pagoMp);
        $pago->estado = EstadoIntentoPago::RequiereRevision;
        $pago->detalle_estado = $motivo;
        $pago->save();

        Log::warning('Pago de Mercado Pago que requiere revisión.', [
            'pago_id' => $pago->getKey(),
            'motivo' => $motivo,
            'status' => $pagoMp['status'] ?? null,
        ]);

        $this->auditoria->registrar('pago.requiere_revision', $pago, ['estado' => $anterior], [
            'estado' => EstadoIntentoPago::RequiereRevision->value,
            'motivo' => $motivo,
            'estado_mercado_pago' => $pagoMp['status'] ?? null,
        ]);

        if ($anterior !== EstadoIntentoPago::RequiereRevision->value) {
            $this->alertarPagoEnRevision($pago);
        }

        return $pago;
    }

    private function notificarPagoAprobado(Pedido $pedido, Pago $pago): void
    {
        Notification::route('mail', $pedido->email_cliente)->notify(new PagoAprobado($pedido, $pago));
    }

    private function alertarPagoEnRevision(Pago $pago): void
    {
        $administradores = Usuario::query()
            ->where('rol', 'administrador')
            ->where('activo', true)
            ->get();

        Notification::send($administradores, new AlertaPagoEnRevision($pago->pedido, $pago));
    }

    /**
     * Notificación de otra operación de Mercado Pago para un intento que ya tiene la suya: se audita sin cambiar el intento.
     *
     * @param  array<string, mixed>  $pagoMp
     */
    private function registrarInconsistencia(Pago $pago, array $pagoMp, string $motivo): Pago
    {
        Log::warning('Operación de Mercado Pago inconsistente con el intento de pago.', [
            'pago_id' => $pago->getKey(),
            'motivo' => $motivo,
            'status' => $pagoMp['status'] ?? null,
        ]);

        $this->auditoria->registrar('pago.notificacion_inconsistente', $pago, [], [
            'motivo' => $motivo,
            'estado_mercado_pago' => $pagoMp['status'] ?? null,
        ]);

        return $pago;
    }

    /**
     * @param  array<string, mixed>  $pagoMp
     */
    private function ignorarEstado(Pago $pago, array $pagoMp): Pago
    {
        Log::info('Estado de Mercado Pago sin acción sobre el intento de pago.', [
            'pago_id' => $pago->getKey(),
            'status' => $pagoMp['status'] ?? null,
        ]);

        return $pago;
    }

    /**
     * Busca el intento por el id de Mercado Pago o, si todavía no tiene, por la referencia "{numero}:{pago_id}".
     *
     * @param  array<string, mixed>  $pagoMp
     */
    private function localizarPago(array $pagoMp): ?Pago
    {
        $idMercadoPago = $pagoMp['id'] ?? null;

        if (! is_scalar($idMercadoPago)) {
            return null;
        }

        $referencia = (string) ($pagoMp['external_reference'] ?? '');

        $pago = Pago::query()->with('pedido')->where('id_pago_mercado_pago', (string) $idMercadoPago)->first();

        if (! $pago instanceof Pago && preg_match('/^[^:]+:(\d+)$/', $referencia, $coincidencias) === 1) {
            $pago = Pago::query()->with('pedido')->find((int) $coincidencias[1]);
        }

        if (! $pago instanceof Pago || $pago->pedido?->numero === null) {
            return null;
        }

        return "{$pago->pedido->numero}:{$pago->getKey()}" === $referencia ? $pago : null;
    }

    /**
     * @param  array<string, mixed>  $pagoMp
     */
    private function idDistinto(Pago $pago, array $pagoMp): bool
    {
        return $pago->id_pago_mercado_pago !== null && $pago->id_pago_mercado_pago !== (string) $pagoMp['id'];
    }

    /**
     * @param  array<string, mixed>  $pagoMp
     */
    private function vincular(Pago $pago, array $pagoMp): void
    {
        $pago->id_pago_mercado_pago ??= (string) $pagoMp['id'];

        if (isset($pagoMp['payment_method_id'])) {
            $pago->medio_pago_mercado_pago ??= Str::limit((string) $pagoMp['payment_method_id'], 40, '');
        }
    }

    /**
     * @param  array<string, mixed>  $pagoMp
     */
    private function detalleDe(array $pagoMp): ?string
    {
        $detalle = (string) ($pagoMp['status_detail'] ?? '');

        return $detalle === '' ? null : Str::limit($detalle, 255, '');
    }

    /**
     * @param  array<string, mixed>  $pagoMp
     */
    private function fechaDeAprobacion(array $pagoMp): Carbon
    {
        $fecha = $pagoMp['date_approved'] ?? null;

        if (! is_string($fecha) || $fecha === '') {
            return now();
        }

        try {
            return Carbon::parse($fecha);
        } catch (Throwable) {
            return now();
        }
    }

    /**
     * Los importes llegan como número en el JSON de Mercado Pago. Se formatean a dos decimales y la comparación es bcmath.
     */
    private function importeDe(mixed $valor): string
    {
        return sprintf('%.2f', (float) $valor);
    }

    private function tipoDeMercadoPagoPara(TipoMedioPago $tipo): string
    {
        return match ($tipo) {
            TipoMedioPago::Credito => 'credit_card',
            TipoMedioPago::Debito => 'debit_card',
            TipoMedioPago::DineroEnCuenta => 'account_money',
        };
    }

    private function mensajeDelEstado(EstadoIntentoPago $estado): string
    {
        return match ($estado) {
            EstadoIntentoPago::Pagado => self::MENSAJE_PAGADO,
            EstadoIntentoPago::Rechazado => self::MENSAJE_RECHAZADO,
            EstadoIntentoPago::RequiereRevision => self::MENSAJE_REVISION,
            default => self::MENSAJE_CONFIRMANDO,
        };
    }

    private function bloquearPedido(Pedido $pedido): Pedido
    {
        return Pedido::query()->lockForUpdate()->findOrFail($pedido->getKey());
    }

    private function bloquearPago(Pago $pago): Pago
    {
        return Pago::query()->lockForUpdate()->findOrFail($pago->getKey());
    }
}
