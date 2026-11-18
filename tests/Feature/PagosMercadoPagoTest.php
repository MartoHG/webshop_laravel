<?php

namespace Tests\Feature;

use App\Enums\EstadoEnvio;
use App\Enums\EstadoIntentoPago;
use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Enums\TipoMedioPago;
use App\Jobs\ProcesarNotificacionMercadoPago;
use App\Models\ItemPedido;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\RegistroAuditoria;
use App\Models\ReglaPrecioPago;
use App\Models\Usuario;
use App\Servicios\Pagos\ErrorMercadoPago;
use App\Servicios\Pagos\MercadoPagoCliente;
use App\Servicios\Pagos\PagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PagosMercadoPagoTest extends TestCase
{
    use RefreshDatabase;

    private const string URL_PAGOS = 'https://api.mercadopago.com/v1/payments';

    private const string TOKEN_ACCESO = 'TEST-token-de-prueba-123456';

    private const string URL_PREFERENCIAS = 'https://api.mercadopago.com/checkout/preferences';

    private const string SECRETO_WEBHOOK = 'secreto-webhook-de-prueba';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        config([
            'mercadopago.access_token' => self::TOKEN_ACCESO,
            'mercadopago.public_key' => 'TEST-public-key-123',
            'mercadopago.url_api' => 'https://api.mercadopago.com',
            'mercadopago.descriptor' => 'EL REFUGIO',
            'mercadopago.webhook_secret' => self::SECRETO_WEBHOOK,
        ]);
    }

    public function test_link_de_pago_muestra_resumen_y_opciones_sin_datos_personales(): void
    {
        $pedido = $this->pedidoHabilitado();
        ReglaPrecioPago::factory()->activa()->create(['tipo_medio_pago' => TipoMedioPago::Credito, 'cuotas' => 1, 'porcentaje' => '10']);
        ReglaPrecioPago::factory()->create(['tipo_medio_pago' => TipoMedioPago::Credito, 'cuotas' => 6, 'porcentaje' => '30']);

        $this->getJson("/pago/{$pedido->token_pago}")
            ->assertOk()
            ->assertJsonPath('numero', $pedido->numero)
            ->assertJsonPath('nombre_cliente', 'Ana')
            ->assertJsonPath('items.0.nombre_producto', 'Silla de jardín')
            ->assertJsonPath('items.0.cantidad', 2)
            ->assertJsonPath('total_base', '1000.00')
            ->assertJsonPath('estado_pago', 'habilitado')
            ->assertJsonPath('public_key', 'TEST-public-key-123')
            ->assertJsonCount(1, 'opciones')
            ->assertJsonPath('opciones.0.tipo_medio_pago', 'credito')
            ->assertJsonPath('opciones.0.importe', '1111.12')
            ->assertJsonMissingPath('opciones.0.porcentaje')
            ->assertJsonPath('intento_vigente', null)
            ->assertJsonMissingPath('token_pago')
            ->assertDontSee('30111222')
            ->assertDontSee('ana@example.test')
            ->assertDontSee('Av. Test 4567')
            ->assertDontSee('+54 9 11 5555-0000')
            ->assertDontSee('Gómez')
            ->assertDontSee($pedido->token_pago);
    }

    public function test_link_invalido_o_de_pedido_no_habilitado_da_404(): void
    {
        $this->getJson('/pago/'.str_repeat('a', 48))->assertNotFound();
        $this->getJson('/pago/token-con-guiones')->assertNotFound();

        $deshabilitado = $this->pedidoHabilitado(['estado_pago' => EstadoPago::Deshabilitado]);
        $pagado = $this->pedidoHabilitado(['estado_pedido' => EstadoPedido::Confirmado, 'estado_pago' => EstadoPago::Pagado]);
        $cancelado = $this->pedidoHabilitado(['estado_pedido' => EstadoPedido::Cancelado, 'estado_pago' => EstadoPago::Cancelado]);

        foreach ([$deshabilitado, $pagado, $cancelado] as $pedido) {
            $this->getJson("/pago/{$pedido->token_pago}")->assertNotFound();
            $this->postJson("/pago/{$pedido->token_pago}/intentos", ['regla_precio_pago_id' => 1])->assertNotFound();
        }

        $pendiente = $this->pedidoHabilitado(['estado_pago' => EstadoPago::Pendiente]);

        $this->getJson("/pago/{$pendiente->token_pago}")->assertOk()->assertJsonPath('estado_pago', 'pendiente');
    }

    public function test_iniciar_intento_fija_porcentaje_e_importe_y_rechaza_regla_inactiva(): void
    {
        Http::fake();

        $pedido = $this->pedidoHabilitado();
        $credito1 = $this->reglaCredito(1, '10');
        $credito3 = $this->reglaCredito(3, '20');
        $inactiva = ReglaPrecioPago::factory()->create([
            'tipo_medio_pago' => TipoMedioPago::Debito,
            'cuotas' => 1,
            'porcentaje' => '5',
            'activo' => false,
        ]);
        $url = "/pago/{$pedido->token_pago}/intentos";

        $this->postJson($url, ['regla_precio_pago_id' => $credito3->id])
            ->assertCreated()
            ->assertJsonPath('intento.importe', '1250.00')
            ->assertJsonPath('intento.tipo_medio_pago', 'credito')
            ->assertJsonPath('intento.cuotas', 3)
            ->assertJsonPath('intento.public_key', 'TEST-public-key-123');

        $primero = Pago::query()->sole();

        $this->assertSame(EstadoIntentoPago::Creado, $primero->estado);
        $this->assertSame($credito3->id, $primero->regla_precio_pago_id);
        $this->assertSame('20.000000', $primero->porcentaje_aplicado);
        $this->assertSame('1000.00', $primero->importe_base);
        $this->assertSame('1250.00', $primero->importe_cobrado);
        $this->assertNotNull($primero->clave_idempotencia);

        $this->postJson($url, ['regla_precio_pago_id' => $credito1->id])
            ->assertCreated()
            ->assertJsonPath('intento.importe', '1111.12');

        $this->assertSame(EstadoIntentoPago::Cancelado, $primero->fresh()->estado);
        $this->assertSame(2, Pago::query()->count());

        $this->postJson($url, ['regla_precio_pago_id' => $inactiva->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('modalidad');

        $this->postJson($url, ['regla_precio_pago_id' => 999999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('modalidad');

        $this->postJson($url, ['regla_precio_pago_id' => 'abc'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('regla_precio_pago_id');

        $this->assertSame(2, Pago::query()->count());
        $this->assertSame(2, RegistroAuditoria::query()->where('accion', 'pago.intento_creado')->count());
        Http::assertNothingSent();
    }

    public function test_cliente_de_mercado_pago_devuelve_el_pago_y_traduce_los_errores(): void
    {
        Http::fake([
            self::URL_PAGOS.'/777' => Http::response(['id' => 777, 'status' => 'approved'], 200),
            self::URL_PAGOS.'/404' => Http::response(['message' => 'not found'], 404),
            self::URL_PAGOS.'/999' => Http::failedConnection('timeout'),
        ]);

        $cliente = app(MercadoPagoCliente::class);

        $this->assertSame('approved', $cliente->obtenerPago('777')['status']);

        try {
            $cliente->obtenerPago('404');
            $this->fail('Se esperaba ErrorMercadoPago para una respuesta 404.');
        } catch (ErrorMercadoPago $error) {
            $this->assertSame(404, $error->status);
        }

        try {
            $cliente->obtenerPago('999');
            $this->fail('Se esperaba ErrorMercadoPago para una falla de conexión.');
        } catch (ErrorMercadoPago $error) {
            $this->assertNull($error->status);
        }
    }

    public function test_cuotas_medio_o_importe_distintos_al_intento_se_rechazan_sin_llamar_a_mercado_pago(): void
    {
        Http::fake();

        $pedido = $this->pedidoHabilitado();
        $this->reglaCredito(3, '20');
        $this->postJson("/pago/{$pedido->token_pago}/intentos", ['regla_precio_pago_id' => ReglaPrecioPago::query()->sole()->id])
            ->assertCreated();

        $pago = Pago::query()->sole();
        $url = "/pago/{$pedido->token_pago}/intentos/{$pago->id}/tarjeta";

        $casos = [
            'cuotas distintas' => [['installments' => 1], 'cuotas'],
            'medio distinto' => [['metodo_seleccionado' => 'debit_card'], 'metodo_seleccionado'],
            'importe mayor' => [['transaction_amount' => '1250.01'], 'importe'],
            'importe menor' => [['transaction_amount' => '1249.99'], 'importe'],
        ];

        foreach ($casos as $descripcion => [$cambios, $clave]) {
            $this->postJson($url, $this->datosTarjeta($cambios))
                ->assertUnprocessable()
                ->assertJsonValidationErrors($clave);
        }

        $pagoDineroEnCuenta = Pago::factory()->for($pedido, 'pedido')->create([
            'tipo_medio_pago' => TipoMedioPago::DineroEnCuenta,
            'cuotas' => 1,
            'importe_cobrado' => '1000.00',
        ]);

        $this->postJson("/pago/{$pedido->token_pago}/intentos/{$pagoDineroEnCuenta->id}/tarjeta", $this->datosTarjeta([
            'metodo_seleccionado' => 'credit_card',
            'installments' => 1,
            'transaction_amount' => '1000.00',
        ]))->assertUnprocessable()->assertJsonValidationErrors('metodo_seleccionado');

        $otroPedido = $this->pedidoHabilitado();
        $pagoAjeno = $this->intentoCreado($otroPedido, ReglaPrecioPago::query()->sole());

        $this->postJson("/pago/{$pedido->token_pago}/intentos/{$pagoAjeno->id}/tarjeta", $this->datosTarjeta())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pago');

        $this->assertSame(EstadoIntentoPago::Creado, $pago->fresh()->estado);
        $this->assertSame(EstadoIntentoPago::Creado, $pagoAjeno->fresh()->estado);
        Http::assertNothingSent();
    }

    public function test_pago_aprobado_confirma_y_bloquea_el_pedido(): void
    {
        $pedido = $this->pedidoHabilitado();
        $pago = $this->intentoCreado($pedido, $this->reglaCredito(3, '20'));
        $referencia = "{$pedido->numero}:{$pago->id}";

        Http::fake([self::URL_PAGOS => Http::response($this->respuestaMp([
            'external_reference' => $referencia,
        ]), 201)]);

        $this->postJson("/pago/{$pedido->token_pago}/intentos/{$pago->id}/tarjeta", $this->datosTarjeta())
            ->assertOk()
            ->assertJsonPath('estado', 'pagado');

        Http::assertSent(fn (Request $request): bool => $request->url() === self::URL_PAGOS
            && $request->method() === 'POST'
            && $request->hasHeader('X-Idempotency-Key', $pago->clave_idempotencia)
            && $request->hasHeader('Authorization', 'Bearer '.self::TOKEN_ACCESO)
            && $request['transaction_amount'] === 1250.0
            && $request['installments'] === 3
            && $request['external_reference'] === $referencia
            && $request['token'] === 'tok_prueba_abcdefghij123456'
            && $request['payment_method_id'] === 'visa'
            && $request['issuer_id'] === '25'
            && $request['statement_descriptor'] === 'EL REFUGIO'
            && $request['metadata'] === ['pago_id' => $pago->id]
            && $request['notification_url'] === url('/webhooks/mercadopago'));
        Http::assertSentCount(1);

        $pago->refresh();
        $pedido->refresh();

        $this->assertSame(EstadoIntentoPago::Pagado, $pago->estado);
        $this->assertSame('1234567890', $pago->id_pago_mercado_pago);
        $this->assertSame('visa', $pago->medio_pago_mercado_pago);
        $this->assertNotNull($pago->pagado_en);
        $this->assertSame(EstadoPedido::Confirmado, $pedido->estado_pedido);
        $this->assertSame(EstadoPago::Pagado, $pedido->estado_pago);
        $this->assertNotNull($pedido->pagado_en);

        $this->getJson("/pago/{$pedido->token_pago}")->assertNotFound();
        $this->getJson("/pago/{$pedido->token_pago}/estado")
            ->assertOk()
            ->assertJsonPath('estado_pago', 'pagado')
            ->assertJsonPath('intento_vigente', null);
    }

    public function test_pago_rechazado_vuelve_el_pedido_a_habilitado_para_reintentar(): void
    {
        $pedido = $this->pedidoHabilitado();
        $regla = $this->reglaCredito(3, '20');
        $pago = $this->intentoCreado($pedido, $regla);

        Http::fake([self::URL_PAGOS => Http::response($this->respuestaMp([
            'status' => 'rejected',
            'status_detail' => 'cc_rejected_insufficient_amount',
            'external_reference' => "{$pedido->numero}:{$pago->id}",
        ]), 201)]);

        $this->postJson("/pago/{$pedido->token_pago}/intentos/{$pago->id}/tarjeta", $this->datosTarjeta())
            ->assertOk()
            ->assertJsonPath('estado', 'rechazado');

        $pago->refresh();

        $this->assertSame(EstadoIntentoPago::Rechazado, $pago->estado);
        $this->assertSame('cc_rejected_insufficient_amount', $pago->detalle_estado);
        $this->assertSame(EstadoPago::Habilitado, $pedido->fresh()->estado_pago);

        $this->getJson("/pago/{$pedido->token_pago}")
            ->assertOk()
            ->assertJsonPath('estado_pago', 'habilitado')
            ->assertJsonPath('intento_vigente', null);

        $this->postJson("/pago/{$pedido->token_pago}/intentos", ['regla_precio_pago_id' => $regla->id])
            ->assertCreated();

        $this->assertSame(2, Pago::query()->count());
        $this->assertSame(1, RegistroAuditoria::query()->where('accion', 'pago.rechazado')->count());
    }

    public function test_pago_en_proceso_queda_pendiente_y_no_se_puede_iniciar_otro(): void
    {
        $pedido = $this->pedidoHabilitado();
        $regla = $this->reglaCredito(3, '20');
        $pago = $this->intentoCreado($pedido, $regla);

        Http::fake([self::URL_PAGOS => Http::response($this->respuestaMp([
            'status' => 'in_process',
            'status_detail' => 'pending_review_manual',
            'external_reference' => "{$pedido->numero}:{$pago->id}",
        ]), 201)]);

        $this->postJson("/pago/{$pedido->token_pago}/intentos/{$pago->id}/tarjeta", $this->datosTarjeta())
            ->assertOk()
            ->assertJsonPath('estado', 'pendiente');

        $this->assertSame(EstadoIntentoPago::Pendiente, $pago->fresh()->estado);
        $this->assertSame(EstadoPago::Pendiente, $pedido->fresh()->estado_pago);

        $this->getJson("/pago/{$pedido->token_pago}/estado")
            ->assertOk()
            ->assertJsonPath('estado_pago', 'pendiente')
            ->assertJsonPath('intento_vigente.id', $pago->id)
            ->assertJsonPath('intento_vigente.estado', 'pendiente');

        $this->postJson("/pago/{$pedido->token_pago}/intentos", ['regla_precio_pago_id' => $regla->id])
            ->assertUnprocessable()
            ->assertJsonPath('errors.pago.0', 'Ya hay un pago en proceso. Esperá unos minutos o escribinos por WhatsApp.');

        $this->assertSame(1, Pago::query()->count());
    }

    public function test_error_de_red_deja_el_pago_pendiente(): void
    {
        $pedido = $this->pedidoHabilitado();
        $pago = $this->intentoCreado($pedido, $this->reglaCredito(3, '20'));

        Http::fake([self::URL_PAGOS => Http::failedConnection('timeout')]);

        $this->postJson("/pago/{$pedido->token_pago}/intentos/{$pago->id}/tarjeta", $this->datosTarjeta())
            ->assertOk()
            ->assertJsonPath('estado', 'pendiente')
            ->assertJsonPath('mensaje', 'Estamos confirmando tu pago. No lo vuelvas a intentar: en unos minutos te confirmamos el resultado.');

        $this->assertSame(EstadoIntentoPago::Pendiente, $pago->fresh()->estado);
        $this->assertSame(EstadoPago::Pendiente, $pedido->fresh()->estado_pago);
        $this->assertNull($pago->fresh()->id_pago_mercado_pago);
    }

    public function test_error_de_servidor_deja_el_pago_pendiente(): void
    {
        $pedido = $this->pedidoHabilitado();
        $pago = $this->intentoCreado($pedido, $this->reglaCredito(3, '20'));

        Http::fake([self::URL_PAGOS => Http::response(['message' => 'internal'], 503)]);

        $this->postJson("/pago/{$pedido->token_pago}/intentos/{$pago->id}/tarjeta", $this->datosTarjeta())
            ->assertOk()
            ->assertJsonPath('estado', 'pendiente');

        $this->assertSame(EstadoIntentoPago::Pendiente, $pago->fresh()->estado);
        $this->assertSame(EstadoPago::Pendiente, $pedido->fresh()->estado_pago);
    }

    public function test_error_de_api_que_rechaza_la_solicitud_vuelve_el_pedido_a_habilitado(): void
    {
        $pedido = $this->pedidoHabilitado();
        $pago = $this->intentoCreado($pedido, $this->reglaCredito(3, '20'));

        Http::fake([self::URL_PAGOS => Http::response(['message' => 'invalid'], 400)]);

        $this->postJson("/pago/{$pedido->token_pago}/intentos/{$pago->id}/tarjeta", $this->datosTarjeta())
            ->assertOk()
            ->assertJsonPath('estado', 'rechazado');

        $pago->refresh();

        $this->assertSame(EstadoIntentoPago::Rechazado, $pago->estado);
        $this->assertSame('error_api_400', $pago->detalle_estado);
        $this->assertSame(EstadoPago::Habilitado, $pedido->fresh()->estado_pago);
    }

    public function test_sin_access_token_no_se_procesa_el_pago(): void
    {
        Http::fake();
        config(['mercadopago.access_token' => null]);

        $pedido = $this->pedidoHabilitado();
        $pago = $this->intentoCreado($pedido, $this->reglaCredito(3, '20'));

        $this->postJson("/pago/{$pedido->token_pago}/intentos/{$pago->id}/tarjeta", $this->datosTarjeta())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pago');

        $this->assertSame(EstadoIntentoPago::Creado, $pago->fresh()->estado);
        $this->assertSame(EstadoPago::Habilitado, $pedido->fresh()->estado_pago);
        Http::assertNothingSent();
    }

    public function test_aplicar_pago_aprobado_dos_veces_no_duplica_efectos(): void
    {
        $servicio = app(PagoService::class);
        $pedido = $this->pedidoHabilitado();
        $intento = $servicio->iniciarIntento($pedido, $this->reglaCredito(3, '20')->id);
        $pago = Pago::query()->findOrFail($intento['pago_id']);
        $otroCreado = Pago::factory()->for($pedido, 'pedido')->create();
        $respuesta = $this->respuestaMp(['external_reference' => "{$pedido->numero}:{$pago->id}"]);

        $primero = $servicio->aplicarPagoMercadoPago($respuesta);
        $pagadoEn = $pedido->fresh()->pagado_en;
        $segundo = $servicio->aplicarPagoMercadoPago($respuesta);

        $this->assertSame(EstadoIntentoPago::Pagado, $primero->estado);
        $this->assertSame(EstadoIntentoPago::Pagado, $segundo->estado);
        $this->assertSame(EstadoIntentoPago::Cancelado, $otroCreado->fresh()->estado);
        $this->assertEquals($pagadoEn, $pedido->fresh()->pagado_en);
        $this->assertSame(1, RegistroAuditoria::query()->where('accion', 'pedido.pagado')->count());
        $this->assertSame(1, Pago::query()->where('estado', EstadoIntentoPago::Pagado->value)->count());
    }

    public function test_pago_aprobado_con_importe_o_cuotas_distintos_queda_en_revision(): void
    {
        $servicio = app(PagoService::class);
        $pedido = $this->pedidoHabilitado(['estado_pago' => EstadoPago::Pendiente]);

        $conImporteDistinto = $this->pagoPendiente($pedido);
        $conCuotasDistintas = $this->pagoPendiente($pedido);

        $revisadoImporte = $servicio->aplicarPagoMercadoPago($this->respuestaMp([
            'id' => 1001,
            'transaction_amount' => 1250.01,
            'external_reference' => "{$pedido->numero}:{$conImporteDistinto->id}",
        ]));

        $revisadoCuotas = $servicio->aplicarPagoMercadoPago($this->respuestaMp([
            'id' => 1002,
            'installments' => 1,
            'external_reference' => "{$pedido->numero}:{$conCuotasDistintas->id}",
        ]));

        $this->assertSame(EstadoIntentoPago::RequiereRevision, $revisadoImporte->estado);
        $this->assertSame('importe_distinto', $revisadoImporte->detalle_estado);
        $this->assertSame(EstadoIntentoPago::RequiereRevision, $revisadoCuotas->estado);
        $this->assertSame('cuotas_distintas', $revisadoCuotas->detalle_estado);

        $pedido->refresh();
        $this->assertSame(EstadoPedido::PendienteCoordinacion, $pedido->estado_pedido);
        $this->assertSame(EstadoPago::Pendiente, $pedido->estado_pago);
        $this->assertNull($pedido->pagado_en);

        $registros = RegistroAuditoria::query()->where('accion', 'pago.requiere_revision')->get();
        $this->assertCount(2, $registros);
        $this->assertSame(['importe_distinto', 'cuotas_distintas'], $registros->pluck('datos_nuevos.motivo')->all());
        $this->assertNull($registros->first()->usuario_id);
    }

    public function test_pago_aprobado_para_pedido_cancelado_o_ya_pagado_queda_en_revision(): void
    {
        $servicio = app(PagoService::class);

        $cancelado = Pedido::factory()->cancelado()->create();
        $pagoDeCancelado = $this->pagoPendiente($cancelado);

        $revisadoCancelado = $servicio->aplicarPagoMercadoPago($this->respuestaMp([
            'id' => 2001,
            'external_reference' => "{$cancelado->numero}:{$pagoDeCancelado->id}",
        ]));

        $this->assertSame(EstadoIntentoPago::RequiereRevision, $revisadoCancelado->estado);
        $this->assertSame('pedido_cancelado', $revisadoCancelado->detalle_estado);
        $this->assertSame(EstadoPedido::Cancelado, $cancelado->fresh()->estado_pedido);
        $this->assertSame(EstadoPago::Cancelado, $cancelado->fresh()->estado_pago);

        $pagadoAnterior = $this->pedidoHabilitado([
            'estado_pedido' => EstadoPedido::Confirmado,
            'estado_pago' => EstadoPago::Pagado,
            'pagado_en' => now()->subDay(),
        ]);
        $pagoConfirmado = Pago::factory()->pagado()->for($pagadoAnterior, 'pedido')->create([
            'tipo_medio_pago' => TipoMedioPago::Credito,
            'cuotas' => 3,
            'importe_cobrado' => '1250.00',
            'id_pago_mercado_pago' => '555',
        ]);
        $pagoSegundo = $this->pagoPendiente($pagadoAnterior);
        $fechaPagado = $pagadoAnterior->fresh()->pagado_en;

        $revisadoSegundo = $servicio->aplicarPagoMercadoPago($this->respuestaMp([
            'id' => 2002,
            'external_reference' => "{$pagadoAnterior->numero}:{$pagoSegundo->id}",
        ]));

        $this->assertSame(EstadoIntentoPago::RequiereRevision, $revisadoSegundo->estado);
        $this->assertSame('pedido_ya_pagado', $revisadoSegundo->detalle_estado);
        $this->assertSame(EstadoIntentoPago::Pagado, $pagoConfirmado->fresh()->estado);
        $this->assertSame('555', $pagoConfirmado->fresh()->id_pago_mercado_pago);
        $this->assertEquals($fechaPagado, $pagadoAnterior->fresh()->pagado_en);
        $this->assertSame(EstadoPedido::Confirmado, $pagadoAnterior->fresh()->estado_pedido);
    }

    public function test_pedido_pagado_no_se_cotiza_ni_se_cancela(): void
    {
        $administrador = Usuario::factory()->administrador()->create();

        $pagado = $this->pedidoHabilitado(['estado_pedido' => EstadoPedido::Confirmado, 'estado_pago' => EstadoPago::Pagado]);
        $pendiente = $this->pedidoHabilitado(['estado_pago' => EstadoPago::Pendiente]);

        $this->actingAs($administrador)
            ->patchJson("/administracion/pedidos/{$pagado->id}/envio/cotizacion", ['expreso' => 'Via Cargo', 'costo_envio' => '300'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pedido');

        $this->actingAs($administrador)
            ->postJson("/administracion/pedidos/{$pagado->id}/importes/recalcular")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pedido');

        $this->actingAs($administrador)
            ->patchJson("/administracion/pedidos/{$this->pedidoHabilitado(['estado_pago' => EstadoPago::Pendiente])->id}/envio/cotizacion", ['expreso' => 'Via Cargo', 'costo_envio' => '300'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pago');

        foreach ([$pagado, $pendiente] as $pedido) {
            $this->actingAs($administrador)
                ->postJson("/administracion/pedidos/{$pedido->id}/cancelar", ['motivo' => 'cliente_desistio'])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('pedido');
        }

        $this->assertSame(EstadoPedido::Confirmado, $pagado->fresh()->estado_pedido);
        $this->assertSame(EstadoPago::Pendiente, $pendiente->fresh()->estado_pago);
    }

    public function test_cancelar_pedido_cancela_los_intentos_creados(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $pedido = $this->pedidoHabilitado();
        $intento = Pago::factory()->for($pedido, 'pedido')->create();

        $this->actingAs($administrador)
            ->postJson("/administracion/pedidos/{$pedido->id}/cancelar", ['motivo' => 'cliente_desistio'])
            ->assertOk()
            ->assertJsonPath('pedido.estado_pago', 'cancelado');

        $this->assertSame(EstadoIntentoPago::Cancelado, $intento->fresh()->estado);
    }

    public function test_administracion_lista_los_intentos_del_pedido(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $pedido = $this->pedidoHabilitado();
        $this->intentoCreado($pedido, $this->reglaCredito(3, '20'));

        $this->actingAs($administrador)
            ->getJson("/administracion/pedidos/{$pedido->id}/pagos")
            ->assertOk()
            ->assertJsonCount(1, 'pagos')
            ->assertJsonPath('pagos.0.estado', 'creado')
            ->assertJsonPath('pagos.0.importe_cobrado', '1250.00')
            ->assertJsonPath('pagos.0.porcentaje_aplicado', '20.000000')
            ->assertJsonMissingPath('pagos.0.clave_idempotencia');
    }

    public function test_monto_como_numero_json_se_valida_sin_perder_centavos(): void
    {
        $pedido = $this->pedidoHabilitado();
        $pago = $this->intentoCreado($pedido, $this->reglaCredito(3, '20'));
        $url = "/pago/{$pedido->token_pago}/intentos/{$pago->id}/tarjeta";

        Http::fake([self::URL_PAGOS => Http::response($this->respuestaMp([
            'status' => 'rejected',
            'status_detail' => 'cc_rejected_other_reason',
            'external_reference' => "{$pedido->numero}:{$pago->id}",
        ]), 201)]);

        $this->postJson($url, $this->datosTarjeta(['transaction_amount' => 1250.001]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('transaction_amount');

        $this->postJson($url, $this->datosTarjeta(['transaction_amount' => 1250.5]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('importe');

        $this->postJson($url, $this->datosTarjeta(['transaction_amount' => 1250.0]))
            ->assertOk()
            ->assertJsonPath('estado', 'rechazado');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request['transaction_amount'] === 1250.0);
    }

    public function test_webhook_con_firma_valida_despacha_el_procesamiento(): void
    {
        Queue::fake();

        $this->postJson('/webhooks/mercadopago?data.id=555', $this->cuerpoWebhook('555'), $this->cabecerasWebhook('555'))
            ->assertOk();

        Queue::assertPushed(
            ProcesarNotificacionMercadoPago::class,
            fn (ProcesarNotificacionMercadoPago $job): bool => $job->idPago === '555',
        );
        Queue::assertPushedTimes(ProcesarNotificacionMercadoPago::class, 1);
    }

    public function test_webhook_acepta_data_id_con_guion_bajo_y_normaliza_a_minusculas(): void
    {
        Queue::fake();

        $this->postJson('/webhooks/mercadopago?data_id=ABC', $this->cuerpoWebhook('ABC'), $this->cabecerasWebhook('ABC'))
            ->assertOk();

        Queue::assertPushed(
            ProcesarNotificacionMercadoPago::class,
            fn (ProcesarNotificacionMercadoPago $job): bool => $job->idPago === 'abc',
        );
    }

    public function test_webhook_con_firma_invalida_o_incompleta_responde_401_sin_hacer_nada(): void
    {
        Queue::fake();

        $firmaValida = $this->cabecerasWebhook('555');
        $casos = [
            'secreto distinto' => $this->cabecerasWebhook('555', secreto: 'otro-secreto'),
            'sin x-signature' => array_diff_key($firmaValida, ['x-signature' => true]),
            'sin x-request-id' => array_diff_key($firmaValida, ['x-request-id' => true]),
            'firma sin v1' => ['x-signature' => 'ts=1760000000'] + ['x-request-id' => 'req-abc'],
            'data.id alterado' => $firmaValida,
        ];

        foreach ($casos as $descripcion => $cabeceras) {
            $dataId = $descripcion === 'data.id alterado' ? '556' : '555';

            $this->postJson("/webhooks/mercadopago?data.id={$dataId}", $this->cuerpoWebhook($dataId), $cabeceras)
                ->assertUnauthorized();
        }

        Queue::assertNothingPushed();
    }

    public function test_webhook_sin_secreto_configurado_responde_401(): void
    {
        Queue::fake();
        config(['mercadopago.webhook_secret' => null]);

        $this->postJson('/webhooks/mercadopago?data.id=555', $this->cuerpoWebhook('555'), $this->cabecerasWebhook('555'))
            ->assertUnauthorized();

        Queue::assertNothingPushed();
    }

    public function test_webhook_ignora_otros_tipos_de_notificacion(): void
    {
        Queue::fake();

        $this->postJson('/webhooks/mercadopago?data.id=555', ['type' => 'merchant_order', 'data' => ['id' => '555']], $this->cabecerasWebhook('555'))
            ->assertOk()
            ->assertJsonPath('mensaje', 'Notificación ignorada.');

        Queue::assertNothingPushed();
    }

    public function test_procesar_notificacion_aprobada_confirma_el_pedido_y_no_duplica_efectos(): void
    {
        $pedido = $this->pedidoHabilitado(['estado_pago' => EstadoPago::Pendiente]);
        $pago = $this->pagoPendiente($pedido);

        Http::fake([self::URL_PAGOS.'/555' => Http::response($this->respuestaMp([
            'id' => 555,
            'external_reference' => "{$pedido->numero}:{$pago->id}",
        ]), 200)]);

        $job = new ProcesarNotificacionMercadoPago('555');
        $job->handle(app(MercadoPagoCliente::class), app(PagoService::class));
        $job->handle(app(MercadoPagoCliente::class), app(PagoService::class));

        $this->assertSame(EstadoPedido::Confirmado, $pedido->fresh()->estado_pedido);
        $this->assertSame(EstadoPago::Pagado, $pedido->fresh()->estado_pago);
        $this->assertSame(EstadoIntentoPago::Pagado, $pago->fresh()->estado);
        $this->assertSame(1, RegistroAuditoria::query()->where('accion', 'pedido.pagado')->count());
        Http::assertSentCount(2);
    }

    public function test_notificacion_con_consulta_rechazada_no_se_reintenta(): void
    {
        Http::fake([self::URL_PAGOS.'/999' => Http::response(['message' => 'not found'], 404)]);

        (new ProcesarNotificacionMercadoPago('999'))->handle(app(MercadoPagoCliente::class), app(PagoService::class));

        Http::assertSentCount(1);
    }

    public function test_dinero_en_cuenta_crea_preferencia_restringida_y_guarda_su_id(): void
    {
        $this->travelTo('2026-10-10 12:00:00.123 UTC');
        Http::fake([self::URL_PREFERENCIAS => Http::response(['id' => 'pref-123'], 201)]);

        $pedido = $this->pedidoHabilitado();
        ReglaPrecioPago::factory()->activa()->create([
            'tipo_medio_pago' => TipoMedioPago::DineroEnCuenta,
            'cuotas' => 1,
            'porcentaje' => '4',
        ]);

        $this->postJson("/pago/{$pedido->token_pago}/intentos", ['regla_precio_pago_id' => ReglaPrecioPago::query()->sole()->id])
            ->assertCreated()
            ->assertJsonPath('intento.preference_id', 'pref-123')
            ->assertJsonPath('intento.importe', '1041.67')
            ->assertJsonPath('intento.tipo_medio_pago', 'dinero_en_cuenta');

        $pago = Pago::query()->sole();

        $this->assertSame('pref-123', $pago->id_orden_mercado_pago);

        Http::assertSent(function (Request $request) use ($pago, $pedido): bool {
            $metodos = $request['payment_methods'];
            $excluidos = collect($metodos['excluded_payment_types'])->pluck('id')->sort()->values()->all();

            return $request->url() === self::URL_PREFERENCIAS
                && $request->hasHeader('X-Idempotency-Key', $pago->clave_idempotencia)
                && $request['items'][0]['unit_price'] === 1041.67
                && $request['items'][0]['quantity'] === 1
                && $request['items'][0]['currency_id'] === 'ARS'
                && $request['items'][0]['title'] === "Pedido {$pedido->numero} - El Refugio"
                && $request['external_reference'] === "{$pedido->numero}:{$pago->id}"
                && $request['notification_url'] === url('/webhooks/mercadopago')
                && $request['back_urls']['success'] === url("/pago/{$pedido->token_pago}")
                && $request['auto_return'] === 'approved'
                && $request['expires'] === true
                && $request['expiration_date_from'] === '2026-10-10T11:59:00.123+00:00'
                && $request['expiration_date_to'] === '2026-10-10T12:30:00.123+00:00'
                && $metodos['installments'] === 1
                && $excluidos === ['atm', 'bank_transfer', 'credit_card', 'crypto_transfer', 'debit_card', 'digital_currency', 'digital_wallet', 'prepaid_card', 'ticket'];
        });
    }

    public function test_error_al_crear_preferencia_cancela_el_intento(): void
    {
        Http::fake([self::URL_PREFERENCIAS => Http::response(['message' => 'error'], 500)]);

        $pedido = $this->pedidoHabilitado();
        $regla = ReglaPrecioPago::factory()->activa()->create([
            'tipo_medio_pago' => TipoMedioPago::DineroEnCuenta,
            'cuotas' => 1,
            'porcentaje' => '4',
        ]);

        $this->postJson("/pago/{$pedido->token_pago}/intentos", ['regla_precio_pago_id' => $regla->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pago');

        $this->assertSame(EstadoIntentoPago::Cancelado, Pago::query()->sole()->estado);
        $this->assertSame(EstadoPago::Habilitado, $pedido->fresh()->estado_pago);
    }

    public function test_pagos_conciliar_aplica_pendientes_por_id_y_por_referencia(): void
    {
        $porId = $this->pedidoHabilitado(['estado_pago' => EstadoPago::Pendiente]);
        $pagoPorId = $this->pagoPendiente($porId);
        $pagoPorId->update(['id_pago_mercado_pago' => '555']);

        $porReferencia = $this->pedidoHabilitado(['estado_pago' => EstadoPago::Pendiente]);
        $pagoPorReferencia = $this->pagoPendiente($porReferencia);

        $fallido = $this->pedidoHabilitado(['estado_pago' => EstadoPago::Pendiente]);
        $pagoFallido = $this->pagoPendiente($fallido);
        $pagoFallido->update(['id_pago_mercado_pago' => '557']);

        $reciente = $this->pedidoHabilitado(['estado_pago' => EstadoPago::Pendiente]);
        $pagoReciente = $this->pagoPendiente($reciente);
        $pagoReciente->update(['id_pago_mercado_pago' => '558']);

        DB::table('pagos')
            ->whereIn('id', [$pagoPorId->id, $pagoPorReferencia->id, $pagoFallido->id])
            ->update(['updated_at' => now()->subMinutes(30)]);

        Http::fake([
            self::URL_PAGOS.'/555' => Http::response($this->respuestaMp([
                'id' => 555,
                'external_reference' => "{$porId->numero}:{$pagoPorId->id}",
            ]), 200),
            self::URL_PAGOS.'/557' => Http::response(['message' => 'error'], 500),
            self::URL_PAGOS.'/search*' => Http::response(['results' => [$this->respuestaMp([
                'id' => 556,
                'external_reference' => "{$porReferencia->numero}:{$pagoPorReferencia->id}",
            ])]], 200),
        ]);

        $this->artisan('pagos:conciliar')
            ->expectsOutputToContain('Pagos conciliados: 2. Con error: 1.')
            ->assertSuccessful();

        $this->assertSame(EstadoIntentoPago::Pagado, $pagoPorId->fresh()->estado);
        $this->assertSame(EstadoPedido::Confirmado, $porId->fresh()->estado_pedido);
        $this->assertSame(EstadoIntentoPago::Pagado, $pagoPorReferencia->fresh()->estado);
        $this->assertSame('556', $pagoPorReferencia->fresh()->id_pago_mercado_pago);
        $this->assertSame(EstadoIntentoPago::Pendiente, $pagoFallido->fresh()->estado);
        $this->assertSame(EstadoIntentoPago::Pendiente, $pagoReciente->fresh()->estado);
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/558'));
    }

    public function test_conciliacion_recupera_un_pago_con_dinero_en_cuenta_sin_aviso(): void
    {
        $this->freezeSecond();
        $pedido = $this->pedidoHabilitado();
        $pago = $this->intentoConPreferencia($pedido, 11);
        $referencia = "{$pedido->numero}:{$pago->id}";

        Http::fake([self::URL_PAGOS.'/search*' => Http::response(['results' => [$this->respuestaMp([
            'id' => 559,
            'external_reference' => $referencia,
            'transaction_amount' => 1111.12,
            'installments' => 1,
            'payment_type_id' => 'account_money',
            'payment_method_id' => 'account_money',
        ])]])]);

        $this->artisan('pagos:conciliar')
            ->expectsOutputToContain('Pagos conciliados: 1. Con error: 0.')
            ->assertSuccessful();

        $pago->refresh();
        $pedido->refresh();
        $this->assertSame(EstadoIntentoPago::Pagado, $pago->estado);
        $this->assertSame('559', $pago->id_pago_mercado_pago);
        $this->assertSame(EstadoPago::Pagado, $pedido->estado_pago);
        $this->assertSame(EstadoPedido::Confirmado, $pedido->estado_pedido);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'external_reference='.rawurlencode($referencia)));
    }

    public function test_conciliacion_cancela_solo_la_preferencia_sin_pago_que_supera_el_margen(): void
    {
        $this->freezeSecond();
        $pedido = $this->pedidoHabilitado();
        $vencido = $this->intentoConPreferencia($pedido, 61);
        $dentroDelMargen = $this->intentoConPreferencia($this->pedidoHabilitado(), 40);
        $reciente = $this->intentoConPreferencia($this->pedidoHabilitado(), 9);
        Http::fake([self::URL_PAGOS.'/search*' => Http::response(['results' => []])]);

        $this->artisan('pagos:conciliar')
            ->expectsOutputToContain('Pagos conciliados: 0. Con error: 0.')
            ->expectsOutputToContain('Pagos cancelados por no tener pago en Mercado Pago: 1.')
            ->assertSuccessful();

        $vencido->refresh();
        $this->assertSame(EstadoIntentoPago::Cancelado, $vencido->estado);
        $this->assertSame('preferencia_vencida', $vencido->detalle_estado);
        $this->assertSame(EstadoPago::Habilitado, $pedido->fresh()->estado_pago);
        $this->assertSame(EstadoIntentoPago::Creado, $dentroDelMargen->fresh()->estado);
        $this->assertSame(EstadoIntentoPago::Creado, $reciente->fresh()->estado);

        $registro = RegistroAuditoria::query()->where('accion', 'pago.cancelado_por_conciliacion')->sole();
        $this->assertSame($vencido->id, $registro->entidad_id);
        $this->assertSame(['estado' => 'creado'], $registro->datos_anteriores);
        $this->assertSame(['estado' => 'cancelado', 'motivo' => 'preferencia_vencida'], $registro->datos_nuevos);
        Http::assertSentCount(2);
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), rawurlencode("{$reciente->pedido->numero}:{$reciente->id}")));
    }

    public function test_admin_acepta_la_revision_y_confirma_el_pedido(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $pedido = $this->pedidoHabilitado(['estado_pago' => EstadoPago::Pendiente]);
        $pago = $this->pagoEnRevision($pedido, '900', 'importe_distinto');
        $otro = Pago::factory()->for($pedido, 'pedido')->create();

        $this->actingAs($administrador)
            ->postJson("/administracion/pedidos/{$pedido->id}/pagos/{$pago->id}/resolver", [
                'decision' => 'aceptar',
                'nota' => 'Confirmado con el cliente por WhatsApp.',
            ])
            ->assertOk()
            ->assertJsonPath('pago.estado', 'pagado');

        $pedido->refresh();

        $this->assertSame(EstadoPedido::Confirmado, $pedido->estado_pedido);
        $this->assertSame(EstadoPago::Pagado, $pedido->estado_pago);
        $this->assertNotNull($pedido->pagado_en);
        $this->assertSame(EstadoIntentoPago::Cancelado, $otro->fresh()->estado);

        $registro = RegistroAuditoria::query()->where('accion', 'pago.revision_resuelta')->sole();

        $this->assertSame($administrador->id, $registro->usuario_id);
        $this->assertSame('aceptar', $registro->datos_nuevos['decision']);
        $this->assertSame('Confirmado con el cliente por WhatsApp.', $registro->datos_nuevos['nota']);
    }

    public function test_admin_descarta_la_revision_y_el_link_vuelve_a_funcionar(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $pedido = $this->pedidoHabilitado(['estado_pago' => EstadoPago::Pendiente]);
        $pago = $this->pagoEnRevision($pedido, '901', 'reembolsado');

        $this->actingAs($administrador)
            ->postJson("/administracion/pedidos/{$pedido->id}/pagos/{$pago->id}/resolver", [
                'decision' => 'descartar',
                'nota' => 'Devuelto desde Mercado Pago.',
            ])
            ->assertOk()
            ->assertJsonPath('pago.estado', 'cancelado');

        $this->assertSame('descartado_por_administrador', $pago->fresh()->detalle_estado);
        $this->assertSame(EstadoPago::Habilitado, $pedido->fresh()->estado_pago);
        $this->assertSame(EstadoPedido::PendienteCoordinacion, $pedido->fresh()->estado_pedido);

        $this->getJson("/pago/{$pedido->token_pago}")
            ->assertOk()
            ->assertJsonPath('estado_pago', 'habilitado');

        $registro = RegistroAuditoria::query()->where('accion', 'pago.revision_resuelta')->sole();

        $this->assertSame('descartar', $registro->datos_nuevos['decision']);
        $this->assertSame($administrador->id, $registro->usuario_id);
    }

    public function test_revision_pendiente_bloquea_nuevos_intentos_y_cancelacion(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $pedido = $this->pedidoHabilitado();
        $this->pagoEnRevision($pedido, '902', 'importe_distinto');

        $this->postJson("/pago/{$pedido->token_pago}/intentos", ['regla_precio_pago_id' => $this->reglaCredito(1, '10')->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pago');

        $this->actingAs($administrador)
            ->postJson("/administracion/pedidos/{$pedido->id}/cancelar", ['motivo' => 'cliente_desistio'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pedido');
    }

    public function test_resolver_rechaza_pagos_ajenos_o_que_no_estan_en_revision(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $pedido = $this->pedidoHabilitado();
        $ajeno = $this->pedidoHabilitado();
        $pagoAjeno = $this->pagoEnRevision($ajeno, '903', 'importe_distinto');
        $pagoCreado = Pago::factory()->for($pedido, 'pedido')->create();
        $cancelado = $this->pedidoHabilitado(['estado_pedido' => EstadoPedido::Cancelado, 'estado_pago' => EstadoPago::Cancelado]);
        $pagoCancelado = $this->pagoEnRevision($cancelado, '904', 'importe_distinto');

        $this->actingAs($administrador)
            ->postJson("/administracion/pedidos/{$pedido->id}/pagos/{$pagoAjeno->id}/resolver", ['decision' => 'aceptar', 'nota' => 'Nota.'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pago');

        $this->actingAs($administrador)
            ->postJson("/administracion/pedidos/{$pedido->id}/pagos/{$pagoCreado->id}/resolver", ['decision' => 'aceptar', 'nota' => 'Nota.'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pago');

        $this->actingAs($administrador)
            ->postJson("/administracion/pedidos/{$cancelado->id}/pagos/{$pagoCancelado->id}/resolver", ['decision' => 'aceptar', 'nota' => 'Nota.'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pedido');

        $this->actingAs($administrador)
            ->postJson("/administracion/pedidos/{$pedido->id}/pagos/{$pagoCreado->id}/resolver", ['decision' => 'otra', 'nota' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['decision', 'nota']);

        $this->assertSame(EstadoIntentoPago::RequiereRevision, $pagoAjeno->fresh()->estado);
        $this->assertSame(0, RegistroAuditoria::query()->where('accion', 'pago.revision_resuelta')->count());
    }

    public function test_cliente_no_puede_resolver_revisiones(): void
    {
        $cliente = Usuario::factory()->create();
        $pedido = $this->pedidoHabilitado();
        $pago = $this->pagoEnRevision($pedido, '905', 'importe_distinto');

        $this->actingAs($cliente)
            ->postJson("/administracion/pedidos/{$pedido->id}/pagos/{$pago->id}/resolver", ['decision' => 'aceptar', 'nota' => 'Nota.'])
            ->assertForbidden();

        $this->assertSame(EstadoIntentoPago::RequiereRevision, $pago->fresh()->estado);
    }

    private function pagoEnRevision(Pedido $pedido, string $idMercadoPago, string $motivo): Pago
    {
        return Pago::factory()->for($pedido, 'pedido')->create([
            'estado' => EstadoIntentoPago::RequiereRevision,
            'id_pago_mercado_pago' => $idMercadoPago,
            'medio_pago_mercado_pago' => 'visa',
            'detalle_estado' => $motivo,
            'tipo_medio_pago' => TipoMedioPago::Credito,
            'cuotas' => 3,
            'importe_base' => '1000.00',
            'porcentaje_aplicado' => '20.000000',
            'importe_cobrado' => '1250.00',
        ]);
    }

    /** @return array<string, mixed> */
    private function cuerpoWebhook(string $dataId): array
    {
        return ['action' => 'payment.updated', 'type' => 'payment', 'data' => ['id' => $dataId]];
    }

    /**
     * Firma igual a la de Mercado Pago: HMAC-SHA256 del manifiesto id, x-request-id y ts.
     *
     * @return array<string, string>
     */
    private function cabecerasWebhook(string $dataId, string $requestId = 'req-abc', string $secreto = self::SECRETO_WEBHOOK, string $ts = '1760000000'): array
    {
        $manifiesto = 'id:'.strtolower($dataId).";request-id:{$requestId};ts:{$ts};";

        return [
            'x-signature' => "ts={$ts},v1=".hash_hmac('sha256', $manifiesto, $secreto),
            'x-request-id' => $requestId,
        ];
    }

    public function test_conciliacion_cancela_pago_pendiente_sin_pago_en_mercado_pago_despues_de_30_minutos(): void
    {
        Http::fake([self::URL_PAGOS.'/search*' => Http::response(['results' => []], 200)]);

        $antiguo = $this->pedidoHabilitado(['estado_pago' => EstadoPago::Pendiente]);
        $pagoAntiguo = $this->pagoPendiente($antiguo);
        $reciente = $this->pedidoHabilitado(['estado_pago' => EstadoPago::Pendiente]);
        $pagoReciente = $this->pagoPendiente($reciente);

        DB::table('pagos')->where('id', $pagoAntiguo->id)->update(['updated_at' => now()->subMinutes(31)]);
        DB::table('pagos')->where('id', $pagoReciente->id)->update(['updated_at' => now()->subMinutes(15)]);

        $this->artisan('pagos:conciliar')
            ->expectsOutputToContain('Pagos cancelados por no tener pago en Mercado Pago: 1.')
            ->assertSuccessful();

        $pagoAntiguo->refresh();

        $this->assertSame(EstadoIntentoPago::Cancelado, $pagoAntiguo->estado);
        $this->assertSame('sin_pago_en_mercado_pago', $pagoAntiguo->detalle_estado);
        $this->assertSame(EstadoPago::Habilitado, $antiguo->fresh()->estado_pago);

        $registro = RegistroAuditoria::query()->where('accion', 'pago.cancelado_por_conciliacion')->sole();

        $this->assertNull($registro->usuario_id);
        $this->assertSame($pagoAntiguo->id, $registro->entidad_id);

        // A los 15 minutos todavía se reintenta: el intento sigue pendiente y el pedido también.
        $this->assertSame(EstadoIntentoPago::Pendiente, $pagoReciente->fresh()->estado);
        $this->assertSame(EstadoPago::Pendiente, $reciente->fresh()->estado_pago);
        Http::assertSentCount(2);
    }

    public function test_cancelacion_por_conciliacion_no_habilita_el_pedido_con_otro_pago_en_curso(): void
    {
        Http::fake([self::URL_PAGOS.'/search*' => Http::response(['results' => []], 200)]);

        $pedido = $this->pedidoHabilitado(['estado_pago' => EstadoPago::Pendiente]);
        $antiguo = $this->pagoPendiente($pedido);
        DB::table('pagos')->where('id', $antiguo->id)->update(['updated_at' => now()->subMinutes(31)]);
        $enCurso = $this->pagoPendiente($pedido);
        $enCurso->update(['id_pago_mercado_pago' => '560']);

        $this->artisan('pagos:conciliar')->assertSuccessful();

        $this->assertSame(EstadoIntentoPago::Cancelado, $antiguo->fresh()->estado);
        $this->assertSame(EstadoPago::Pendiente, $pedido->fresh()->estado_pago);
    }

    public function test_pago_aprobado_tarde_de_un_intento_cancelado_se_acepta_si_el_pedido_sigue_pagable(): void
    {
        Http::fake([self::URL_PAGOS.'/search*' => Http::response(['results' => []], 200)]);
        $servicio = app(PagoService::class);

        $pedido = $this->pedidoHabilitado(['estado_pago' => EstadoPago::Pendiente]);
        $cancelado = $this->pagoPendiente($pedido);
        DB::table('pagos')->where('id', $cancelado->id)->update(['updated_at' => now()->subMinutes(31)]);

        $this->artisan('pagos:conciliar')->assertSuccessful();

        $this->assertSame(EstadoIntentoPago::Cancelado, $cancelado->fresh()->estado);
        $this->assertSame(EstadoPago::Habilitado, $pedido->fresh()->estado_pago);

        $aceptado = $servicio->aplicarPagoMercadoPago($this->respuestaMp([
            'id' => 701,
            'external_reference' => "{$pedido->numero}:{$cancelado->id}",
        ]));

        $this->assertSame(EstadoIntentoPago::Pagado, $aceptado->estado);
        $this->assertSame('701', $aceptado->id_pago_mercado_pago);
        $pedido->refresh();
        $this->assertSame(EstadoPedido::Confirmado, $pedido->estado_pedido);
        $this->assertSame(EstadoPago::Pagado, $pedido->estado_pago);
        $this->assertNotNull($pedido->pagado_en);

        // Lo mismo vale para un intento rechazado que Mercado Pago aprueba más tarde.
        $otro = $this->pedidoHabilitado();
        $rechazado = Pago::factory()->for($otro, 'pedido')->create([
            'estado' => EstadoIntentoPago::Rechazado,
            'tipo_medio_pago' => TipoMedioPago::Credito,
            'cuotas' => 3,
            'importe_base' => '1000.00',
            'porcentaje_aplicado' => '20.000000',
            'importe_cobrado' => '1250.00',
        ]);

        $tardio = $servicio->aplicarPagoMercadoPago($this->respuestaMp([
            'id' => 702,
            'external_reference' => "{$otro->numero}:{$rechazado->id}",
        ]));

        $this->assertSame(EstadoIntentoPago::Pagado, $tardio->estado);
        $this->assertSame(EstadoPedido::Confirmado, $otro->fresh()->estado_pedido);
    }

    public function test_pago_aprobado_tarde_con_pedido_ya_pagado_queda_en_revision(): void
    {
        $servicio = app(PagoService::class);
        $pedido = $this->pedidoHabilitado([
            'estado_pedido' => EstadoPedido::Confirmado,
            'estado_pago' => EstadoPago::Pagado,
            'pagado_en' => now()->subHour(),
        ]);
        $confirmado = Pago::factory()->pagado()->for($pedido, 'pedido')->create([
            'id_pago_mercado_pago' => '555',
            'tipo_medio_pago' => TipoMedioPago::Credito,
            'cuotas' => 3,
            'importe_base' => '1000.00',
            'porcentaje_aplicado' => '20.000000',
            'importe_cobrado' => '1250.00',
        ]);
        $tardio = Pago::factory()->for($pedido, 'pedido')->create([
            'estado' => EstadoIntentoPago::Cancelado,
            'tipo_medio_pago' => TipoMedioPago::Credito,
            'cuotas' => 3,
            'importe_base' => '1000.00',
            'porcentaje_aplicado' => '20.000000',
            'importe_cobrado' => '1250.00',
        ]);
        $pagadoEn = $pedido->fresh()->pagado_en;

        $revisado = $servicio->aplicarPagoMercadoPago($this->respuestaMp([
            'id' => 703,
            'external_reference' => "{$pedido->numero}:{$tardio->id}",
        ]));

        $this->assertSame(EstadoIntentoPago::RequiereRevision, $revisado->estado);
        $this->assertSame('pedido_ya_pagado', $revisado->detalle_estado);
        $this->assertSame(EstadoIntentoPago::Pagado, $confirmado->fresh()->estado);
        $this->assertSame(EstadoPedido::Confirmado, $pedido->fresh()->estado_pedido);
        $this->assertEquals($pagadoEn, $pedido->fresh()->pagado_en);
    }

    /** @param  array<string, mixed>  $atributos */
    private function pedidoHabilitado(array $atributos = []): Pedido
    {
        $pedido = Pedido::factory()->create(array_merge([
            'nombre_cliente' => 'Ana',
            'apellido_cliente' => 'Gómez',
            'dni_cliente' => '30111222',
            'email_cliente' => 'ana@example.test',
            'telefono_cliente' => '+54 9 11 5555-0000',
            'direccion_envio' => 'Av. Test 4567',
            'subtotal_productos' => '800.00',
            'costo_envio' => '200.00',
            'total_base' => '1000.00',
            'expreso' => 'Andreani',
            'estado_envio' => EstadoEnvio::Aceptado,
            'estado_pago' => EstadoPago::Habilitado,
            'token_pago' => bin2hex(random_bytes(24)),
        ], $atributos));

        ItemPedido::factory()->for($pedido, 'pedido')->create([
            'nombre_producto' => 'Silla de jardín',
            'color' => 'Verde',
            'etiqueta' => null,
            'cantidad' => 2,
            'precio_unitario' => '400.00',
            'subtotal' => '800.00',
        ]);

        return $pedido->refresh();
    }

    private function reglaCredito(int $cuotas, string $porcentaje): ReglaPrecioPago
    {
        return ReglaPrecioPago::factory()->activa()->create([
            'tipo_medio_pago' => TipoMedioPago::Credito,
            'cuotas' => $cuotas,
            'porcentaje' => $porcentaje,
        ]);
    }

    private function intentoCreado(Pedido $pedido, ReglaPrecioPago $regla): Pago
    {
        $this->postJson("/pago/{$pedido->token_pago}/intentos", ['regla_precio_pago_id' => $regla->id])
            ->assertCreated();

        return Pago::query()->where('pedido_id', $pedido->getKey())->latest('id')->firstOrFail();
    }

    private function pagoPendiente(Pedido $pedido): Pago
    {
        return Pago::factory()->for($pedido, 'pedido')->create([
            'estado' => EstadoIntentoPago::Pendiente,
            'tipo_medio_pago' => TipoMedioPago::Credito,
            'cuotas' => 3,
            'importe_base' => '1000.00',
            'porcentaje_aplicado' => '20.000000',
            'importe_cobrado' => '1250.00',
        ]);
    }

    private function intentoConPreferencia(Pedido $pedido, int $antiguedadEnMinutos): Pago
    {
        $pago = Pago::factory()->for($pedido, 'pedido')->create([
            'tipo_medio_pago' => TipoMedioPago::DineroEnCuenta,
            'id_orden_mercado_pago' => 'pref-'.$pedido->getKey(),
        ]);

        DB::table('pagos')->where('id', $pago->id)->update(['created_at' => now()->subMinutes($antiguedadEnMinutos)]);

        return $pago;
    }

    /** @param  array<string, mixed>  $cambios */
    private function datosTarjeta(array $cambios = []): array
    {
        return array_replace_recursive([
            'metodo_seleccionado' => 'credit_card',
            'token' => 'tok_prueba_abcdefghij123456',
            'payment_method_id' => 'visa',
            'issuer_id' => '25',
            'installments' => 3,
            'transaction_amount' => '1250.00',
            'payer' => [
                'email' => 'ana@example.test',
                'identification' => ['type' => 'DNI', 'number' => '30111222'],
            ],
        ], $cambios);
    }

    /**
     * Respuesta de /v1/payments tal como la envía Mercado Pago: importes como número JSON.
     *
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    private function respuestaMp(array $cambios = []): array
    {
        return array_replace([
            'id' => 1234567890,
            'status' => 'approved',
            'status_detail' => 'accredited',
            'currency_id' => 'ARS',
            'transaction_amount' => 1250.0,
            'installments' => 3,
            'payment_type_id' => 'credit_card',
            'payment_method_id' => 'visa',
            'date_approved' => '2026-10-10T12:00:00.000-03:00',
            'external_reference' => null,
        ], $cambios);
    }
}
