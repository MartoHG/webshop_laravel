<?php

namespace Tests\Feature;

use App\Enums\EstadoEnvio;
use App\Enums\EstadoIntentoPago;
use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Enums\TipoMedioPago;
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
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PagosMercadoPagoTest extends TestCase
{
    use RefreshDatabase;

    private const string URL_PAGOS = 'https://api.mercadopago.com/v1/payments';

    private const string TOKEN_ACCESO = 'TEST-token-de-prueba-123456';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        config([
            'mercadopago.access_token' => self::TOKEN_ACCESO,
            'mercadopago.public_key' => 'TEST-public-key-123',
            'mercadopago.url_api' => 'https://api.mercadopago.com',
            'mercadopago.descriptor' => 'EL REFUGIO',
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
