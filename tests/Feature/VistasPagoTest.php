<?php

namespace Tests\Feature;

use App\Enums\EstadoEnvio;
use App\Enums\EstadoIntentoPago;
use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Enums\TipoMedioPago;
use App\Models\Configuracion;
use App\Models\ItemPedido;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\ReglaPrecioPago;
use App\Models\Usuario;
use DOMDocument;
use DOMXPath;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as SolicitudHttp;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class VistasPagoTest extends TestCase
{
    use RefreshDatabase;

    private const string CLAVE_PUBLICA = 'TEST-public-key-123';

    private const string URL_PREFERENCIAS = 'https://api.mercadopago.com/checkout/preferences';

    private const string URL_PAGOS = 'https://api.mercadopago.com/v1/payments';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        config([
            'mercadopago.access_token' => 'TEST-token-de-prueba-123456',
            'mercadopago.public_key' => self::CLAVE_PUBLICA,
            'mercadopago.url_api' => 'https://api.mercadopago.com',
            'mercadopago.descriptor' => 'EL REFUGIO',
        ]);
    }

    public function test_la_pagina_muestra_cada_modalidad_con_su_importe_final_y_sin_datos_personales(): void
    {
        $pedido = $this->pedidoHabilitado(['localidad_envio' => 'Rosario']);
        $this->reglaActiva(TipoMedioPago::Debito, 1, '5');
        $this->reglaActiva(TipoMedioPago::DineroEnCuenta, 1, '5');
        $this->reglaActiva(TipoMedioPago::Credito, 1, '10');
        $this->reglaActiva(TipoMedioPago::Credito, 3, '20');

        $this->get(route('pago.mostrar', $pedido->token_pago))
            ->assertOk()
            ->assertViewIs('tienda.pago')
            ->assertSee('<title>Pagá tu pedido · El Refugio</title>', false)
            ->assertSee('Pagá tu pedido')
            ->assertSee($pedido->numero.' · Ana')
            ->assertSee('2 × Silla de jardín')
            ->assertSee('Envío por expreso a Rosario')
            ->assertSee('$1.000')
            ->assertSee('Débito')
            ->assertSee('$1.052,64')
            ->assertSee('Dinero en Mercado Pago')
            ->assertSee('$1.111,12')
            ->assertSee('Crédito en 3 cuotas')
            ->assertSee('3 cuotas de $416,67')
            ->assertSee('$1.250')
            ->assertSee('Cada forma de pago tiene su precio final')
            ->assertSee('https://sdk.mercadopago.com/js/v2', false)
            ->assertDontSee('10%')
            ->assertDontSee('20%')
            ->assertDontSee('porcentaje')
            ->assertDontSee('30111222')
            ->assertDontSee('ana@example.test')
            ->assertDontSee('Av. Test 4567')
            ->assertDontSee('+54 9 11 5555-0000')
            ->assertDontSee('Gómez');
    }

    public function test_la_cuota_se_redondea_hacia_arriba_al_centavo_en_la_pagina(): void
    {
        $pedido = $this->pedidoHabilitado();
        $this->reglaActiva(TipoMedioPago::Credito, 6, '30');

        $this->get(route('pago.mostrar', $pedido->token_pago))
            ->assertOk()
            ->assertSee('$1.428,58')
            ->assertSee('6 cuotas de $238,10');
    }

    public function test_sin_formas_de_pago_activas_la_pagina_lo_dice_y_no_muestra_el_formulario(): void
    {
        $pedido = $this->pedidoHabilitado();

        $this->get(route('pago.mostrar', $pedido->token_pago))
            ->assertOk()
            ->assertSee('Todavía no hay formas de pago disponibles para este pedido.')
            ->assertDontSee('name="regla_precio_pago_id"', false);
    }

    public function test_continuar_con_htmx_devuelve_el_bloque_del_brick_con_su_configuracion(): void
    {
        Http::fake();

        $pedido = $this->pedidoHabilitado();
        $regla = $this->reglaActiva(TipoMedioPago::Credito, 3, '20');
        $url = route('pago.intentos.crear', $pedido->token_pago);

        $respuesta = $this->htmx('POST', $url, ['regla_precio_pago_id' => $regla->id])
            ->assertOk()
            ->assertViewIs('tienda.partials.pago-brick')
            ->assertSee('Vas a pagar')
            ->assertSee('$1.250')
            ->assertSee('en 3 cuotas de $416,67')
            ->assertSee('id="pago-brick"', false)
            ->assertSee('data-public-key="'.self::CLAVE_PUBLICA.'"', false)
            ->assertSee('data-importe="1250.00"', false)
            ->assertSee('data-tipo="credito"', false)
            ->assertSee('data-cuotas="3"', false);

        $intento = Pago::query()->sole();

        $respuesta
            ->assertSee('data-pago-id="'.$intento->id.'"', false)
            ->assertSee('data-url-tarjeta="'.route('pago.intentos.tarjeta', [$pedido->token_pago, $intento->id]).'"', false)
            ->assertSee('data-url-estado="'.route('pago.estado', $pedido->token_pago).'"', false);

        Http::assertNothingSent();
    }

    public function test_dinero_en_cuenta_con_htmx_entrega_la_preferencia_y_no_tiene_url_de_tarjeta(): void
    {
        Http::fake([self::URL_PREFERENCIAS => Http::response(['id' => 'pref-123'], 201)]);

        $pedido = $this->pedidoHabilitado();
        $regla = $this->reglaActiva(TipoMedioPago::DineroEnCuenta, 1, '5');

        $this->htmx('POST', route('pago.intentos.crear', $pedido->token_pago), ['regla_precio_pago_id' => $regla->id])
            ->assertOk()
            ->assertSee('data-tipo="dinero_en_cuenta"', false)
            ->assertSee('data-preference-id="pref-123"', false)
            ->assertSee('data-url-tarjeta=""', false)
            ->assertSee('$1.052,64');
    }

    public function test_continuar_sin_htmx_redirige_a_la_pagina_con_la_modalidad_elegida_marcada(): void
    {
        $pedido = $this->pedidoHabilitado();
        $this->reglaActiva(TipoMedioPago::Debito, 1, '5');
        $regla = $this->reglaActiva(TipoMedioPago::Credito, 3, '20');

        $this->post(route('pago.intentos.crear', $pedido->token_pago), ['regla_precio_pago_id' => $regla->id])
            ->assertRedirect(route('pago.mostrar', $pedido->token_pago));

        $respuesta = $this->get(route('pago.mostrar', $pedido->token_pago))
            ->assertOk()
            ->assertSee('id="pago-brick"', false)
            ->assertSee('en 3 cuotas de $416,67');

        $this->assertSame((string) $regla->id, $this->modalidadMarcada($respuesta));
    }

    public function test_la_preferencia_vigente_muestra_el_brick_y_al_vencer_deja_elegir_de_nuevo(): void
    {
        $this->freezeSecond();
        $pedido = $this->pedidoHabilitado();
        $regla = $this->reglaActiva(TipoMedioPago::DineroEnCuenta, 1, '20');
        $this->pagoConPreferencia($pedido);
        $url = route('pago.mostrar', $pedido->token_pago);

        $vigente = $this->get($url)
            ->assertOk()
            ->assertSee('id="pago-brick"', false)
            ->assertSee('data-preference-id="pref-123"', false);

        $this->assertSame((string) $regla->id, $this->modalidadMarcada($vigente));

        $this->travel(31)->minutes();

        $vencida = $this->get($url)
            ->assertOk()
            ->assertSee('Elegí cómo pagar')
            ->assertSee('Dinero en Mercado Pago')
            ->assertDontSee('id="pago-brick"', false);

        $this->assertNull($this->modalidadMarcada($vencida));
    }

    public function test_el_regreso_aprobado_confirma_el_pedido_y_muestra_el_resultado_sin_opciones_de_pago(): void
    {
        $pedido = $this->pedidoHabilitado();
        $pago = $this->pagoConPreferencia($pedido);
        $this->reglaActiva(TipoMedioPago::DineroEnCuenta, 1, '20');
        Configuracion::query()->create(['clave' => 'contacto.whatsapp', 'valor' => '2284123456']);
        Http::fake([self::URL_PAGOS.'/559' => Http::response($this->respuestaMp($pedido, $pago, 'approved', 'accredited'))]);
        $url = route('pago.mostrar', [$pedido->token_pago, 'payment_id' => '559', 'status' => 'approved']);

        $this->get($url)
            ->assertOk()
            ->assertViewIs('tienda.pago')
            ->assertSee('¡Listo! Recibimos tu pago.')
            ->assertSee('2 × Silla de jardín')
            ->assertSee('¿Dudas con el pago?')
            ->assertDontSee('name="regla_precio_pago_id"', false)
            ->assertDontSee('Todavía no hay formas de pago')
            ->assertDontSee('id="pago-brick"', false)
            ->assertDontSee('sdk.mercadopago.com', false);

        $pedido->refresh();
        $this->assertSame(EstadoPago::Pagado, $pedido->estado_pago);
        $this->assertSame(EstadoPedido::Confirmado, $pedido->estado_pedido);
        $this->assertSame(EstadoIntentoPago::Pagado, $pago->fresh()->estado);

        $this->get($url)->assertOk()->assertSee('¡Listo! Recibimos tu pago.');
        Http::assertSentCount(1);
    }

    public function test_el_regreso_a_un_pedido_ya_pagado_muestra_el_resultado_sin_consultar_la_api(): void
    {
        $pedido = $this->pedidoPagado();

        $this->get(route('pago.mostrar', [$pedido->token_pago, 'payment_id' => '559']))
            ->assertOk()
            ->assertSee('¡Listo! Recibimos tu pago.')
            ->assertDontSee('Todavía no hay formas de pago')
            ->assertDontSee('sdk.mercadopago.com', false);

        Http::assertNothingSent();
    }

    #[TestWith(['payment_id=559&status=approved'])]
    #[TestWith(['payment_id=&collection_id=559&collection_status=pending'])]
    public function test_el_regreso_con_error_500_y_estado_en_curso_deja_el_pago_pendiente(string $consulta): void
    {
        $pedido = $this->pedidoHabilitado();
        $pago = $this->pagoConPreferencia($pedido);
        Http::fake([self::URL_PAGOS.'/559' => Http::response([], 500)]);

        $this->get(route('pago.mostrar', $pedido->token_pago).'?'.$consulta)
            ->assertOk()
            ->assertSee('Estamos confirmando tu pago.')
            ->assertSee('hx-trigger="every 5s"', false)
            ->assertSee('hx-get="'.route('pago.estado', $pedido->token_pago).'"', false)
            ->assertDontSee('id="pago-brick"', false);

        $pago->refresh();
        $this->assertSame(EstadoIntentoPago::Pendiente, $pago->estado);
        $this->assertSame('regreso_sin_confirmar', $pago->detalle_estado);
        $this->assertNull($pago->id_pago_mercado_pago);
        $this->assertSame(EstadoPago::Pendiente, $pedido->fresh()->estado_pago);
        Http::assertSentCount(1);
    }

    #[TestWith(['payment_id=559'])]
    #[TestWith(['payment_id=559&status=rejected'])]
    public function test_el_regreso_con_error_500_sin_estado_en_curso_conserva_el_intento(string $consulta): void
    {
        $pedido = $this->pedidoHabilitado();
        $pago = $this->pagoConPreferencia($pedido);
        Http::fake([self::URL_PAGOS.'/559' => Http::response([], 500)]);

        $this->get(route('pago.mostrar', $pedido->token_pago).'?'.$consulta)
            ->assertOk()
            ->assertSee('id="pago-brick"', false)
            ->assertDontSee('Estamos confirmando tu pago.');

        $this->assertSame(EstadoIntentoPago::Creado, $pago->fresh()->estado);
        $this->assertSame(EstadoPago::Habilitado, $pedido->fresh()->estado_pago);
        Http::assertSentCount(1);
    }

    public function test_el_regreso_espera_a_la_api_8_segundos_y_si_vence_deja_el_pago_pendiente(): void
    {
        $pedido = $this->pedidoHabilitado();
        $pago = $this->pagoConPreferencia($pedido);
        $segundosDeEspera = null;

        Http::fake(function (SolicitudHttp $solicitud, array $opciones) use (&$segundosDeEspera): PromiseInterface {
            $segundosDeEspera = $opciones['timeout'] ?? null;

            return Http::failedConnection('cURL error 28: Operation timed out')($solicitud);
        });

        $this->get(route('pago.mostrar', [$pedido->token_pago, 'payment_id' => '559', 'status' => 'approved']))
            ->assertOk()
            ->assertSee('Estamos confirmando tu pago.')
            ->assertSee('hx-trigger="every 5s"', false);

        $this->assertSame(8, $segundosDeEspera);

        $pago->refresh();
        $this->assertSame(EstadoIntentoPago::Pendiente, $pago->estado);
        $this->assertSame('regreso_sin_confirmar', $pago->detalle_estado);
        $this->assertSame(EstadoPago::Pendiente, $pedido->fresh()->estado_pago);
    }

    public function test_el_regreso_con_un_pago_de_otro_pedido_conserva_los_dos_pedidos(): void
    {
        $pedido = $this->pedidoHabilitado();
        $pago = $this->pagoConPreferencia($pedido);
        $otroPedido = $this->pedidoHabilitado();
        $otroPago = $this->pagoConPreferencia($otroPedido);
        Http::fake([self::URL_PAGOS.'/559' => Http::response($this->respuestaMp($otroPedido, $otroPago, 'approved', 'accredited'))]);

        $this->get(route('pago.mostrar', [$pedido->token_pago, 'payment_id' => '559', 'status' => 'approved']))
            ->assertOk()
            ->assertSee($pedido->numero)
            ->assertDontSee($otroPedido->numero)
            ->assertDontSee('¡Listo! Recibimos tu pago.');

        $this->assertSame(EstadoPago::Habilitado, $pedido->fresh()->estado_pago);
        $this->assertSame(EstadoPago::Habilitado, $otroPedido->fresh()->estado_pago);
        $this->assertSame(EstadoIntentoPago::Creado, $pago->fresh()->estado);
        $this->assertSame(EstadoIntentoPago::Creado, $otroPago->fresh()->estado);
    }

    #[TestWith(['payment_id=null&collection_id=559'])]
    #[TestWith(['payment_id=123456789012345678901'])]
    #[TestWith(['payment_id[]=559'])]
    public function test_el_regreso_con_id_invalido_muestra_la_pagina_habitual_sin_consultar_la_api(string $consulta): void
    {
        $pedido = $this->pedidoHabilitado();
        $this->pagoConPreferencia($pedido);

        $this->get(route('pago.mostrar', $pedido->token_pago).'?'.$consulta.'&status=approved')
            ->assertOk()
            ->assertViewIs('tienda.pago')
            ->assertSee('id="pago-brick"', false);

        Http::assertNothingSent();
    }

    public function test_el_regreso_rechazado_muestra_el_motivo_y_las_modalidades_para_reintentar(): void
    {
        $pedido = $this->pedidoHabilitado();
        $pago = $this->pagoConPreferencia($pedido);
        $this->reglaActiva(TipoMedioPago::DineroEnCuenta, 1, '20');
        Http::fake([self::URL_PAGOS.'/559' => Http::response($this->respuestaMp($pedido, $pago, 'rejected', 'cc_rejected_high_risk'))]);

        $respuesta = $this->get(route('pago.mostrar', [$pedido->token_pago, 'payment_id' => '559', 'status' => 'approved']))
            ->assertOk()
            ->assertSee('El pago no se aprobó')
            ->assertSee('El pago no pasó los controles de seguridad.')
            ->assertSee('name="regla_precio_pago_id"', false)
            ->assertDontSee('id="pago-brick"', false);

        $this->assertNull($this->modalidadMarcada($respuesta));
        $this->assertSame(EstadoIntentoPago::Rechazado, $pago->fresh()->estado);
        $this->assertSame(EstadoPago::Habilitado, $pedido->fresh()->estado_pago);

        // Con un intento nuevo, recargar la dirección del regreso ya no muestra el rechazo anterior.
        $this->pagoConPreferencia($pedido);

        $this->get(route('pago.mostrar', [$pedido->token_pago, 'payment_id' => '559', 'status' => 'approved']))
            ->assertOk()
            ->assertDontSee('El pago no se aprobó')
            ->assertSee('id="pago-brick"', false);
    }

    public function test_el_regreso_a_un_pedido_cancelado_o_token_invalido_devuelve_404_sin_consultar_la_api(): void
    {
        $pedido = $this->pedidoHabilitado(['estado_pedido' => EstadoPedido::Cancelado, 'estado_pago' => EstadoPago::Cancelado]);

        $this->get(route('pago.mostrar', [$pedido->token_pago, 'payment_id' => '559', 'status' => 'approved']))
            ->assertNotFound()
            ->assertViewIs('tienda.pago-inactivo');

        $this->get(route('pago.mostrar', [str_repeat('a', 48), 'payment_id' => '559', 'status' => 'approved']))
            ->assertNotFound()
            ->assertViewIs('tienda.pago-inactivo');

        Http::assertNothingSent();
    }

    public function test_los_parametros_de_regreso_se_ignoran_en_la_respuesta_json(): void
    {
        $pedido = $this->pedidoHabilitado();
        $pago = $this->pagoConPreferencia($pedido);

        $this->getJson(route('pago.mostrar', [$pedido->token_pago, 'payment_id' => '559', 'status' => 'approved']))
            ->assertOk()
            ->assertJsonPath('estado_pago', 'habilitado')
            ->assertJsonPath('intento_vigente.id', $pago->id);

        Http::assertNothingSent();
    }

    public function test_una_modalidad_inexistente_muestra_el_aviso_con_htmx_y_redirige_sin_htmx(): void
    {
        $pedido = $this->pedidoHabilitado();
        $url = route('pago.intentos.crear', $pedido->token_pago);

        $this->htmx('POST', $url, ['regla_precio_pago_id' => 999999])
            ->assertStatus(422)
            ->assertViewIs('tienda.partials.pago-error')
            ->assertSee('La modalidad de pago seleccionada no está disponible.');

        $this->post($url, ['regla_precio_pago_id' => 999999])
            ->assertRedirect(route('pago.mostrar', $pedido->token_pago))
            ->assertSessionHas('error', 'La modalidad de pago seleccionada no está disponible.');
    }

    public function test_sin_clave_publica_muestra_el_aviso_y_no_carga_el_sdk(): void
    {
        config(['mercadopago.public_key' => '']);

        $pedido = $this->pedidoHabilitado();
        $regla = $this->reglaActiva(TipoMedioPago::Credito, 3, '20');

        $this->get(route('pago.mostrar', $pedido->token_pago))
            ->assertOk()
            ->assertDontSee('sdk.mercadopago.com', false);

        $this->htmx('POST', route('pago.intentos.crear', $pedido->token_pago), ['regla_precio_pago_id' => $regla->id])
            ->assertOk()
            ->assertSee('Mercado Pago todavía no está configurado en esta tienda.')
            ->assertDontSee('id="pago-brick"', false);

        $this->get(route('pago.mostrar', $pedido->token_pago))
            ->assertOk()
            ->assertDontSee('sdk.mercadopago.com', false)
            ->assertSee('Mercado Pago todavía no está configurado en esta tienda.');
    }

    public function test_un_link_que_ya_no_sirve_muestra_una_pagina_amable_con_404(): void
    {
        $this->get('/pago/'.str_repeat('a', 48))
            ->assertNotFound()
            ->assertSee('Este link de pago ya no está activo')
            ->assertSee('Si ya pagaste este pedido, no tenés que hacer nada más.')
            ->assertSee(route('cuenta.pedidos.index'));

        $pagado = $this->pedidoHabilitado(['estado_pedido' => EstadoPedido::Confirmado, 'estado_pago' => EstadoPago::Pagado]);

        $this->get(route('pago.mostrar', $pagado->token_pago))
            ->assertNotFound()
            ->assertSee('Este link de pago ya no está activo');

        $this->htmx('POST', route('pago.intentos.crear', $pagado->token_pago), ['regla_precio_pago_id' => 1])
            ->assertStatus(422)
            ->assertSee('Este link de pago ya no está activo');
    }

    public function test_el_estado_con_htmx_se_consulta_solo_mientras_el_pago_esta_pendiente(): void
    {
        $pedido = $this->pedidoHabilitado(['estado_pago' => EstadoPago::Pendiente]);
        $pago = $this->pagoCon($pedido, EstadoIntentoPago::Pendiente);
        $url = route('pago.estado', $pedido->token_pago);

        $this->htmx('GET', $url)
            ->assertOk()
            ->assertSee('hx-trigger="every 5s"', false)
            ->assertSee('Estamos confirmando tu pago.');

        $this->htmx('GET', $url, [], ['X-Sondeo' => '1'])
            ->assertNoContent();

        $pedido->update(['estado_pago' => EstadoPago::Pagado, 'estado_pedido' => EstadoPedido::Confirmado]);
        $pago->update(['estado' => EstadoIntentoPago::Pagado]);

        $this->htmx('GET', $url, [], ['X-Sondeo' => '1'])
            ->assertOk()
            ->assertDontSee('every 5s')
            ->assertSee('¡Listo! Recibimos tu pago.');
    }

    public function test_la_pagina_con_un_pago_pendiente_arranca_consultando_el_estado(): void
    {
        $pedido = $this->pedidoHabilitado(['estado_pago' => EstadoPago::Pendiente]);
        $this->pagoCon($pedido, EstadoIntentoPago::Pendiente);

        $this->get(route('pago.mostrar', $pedido->token_pago))
            ->assertOk()
            ->assertSee('hx-trigger="every 5s"', false)
            ->assertSee('Estamos confirmando tu pago.');
    }

    public function test_un_pago_rechazado_explica_el_motivo_y_permite_probar_de_nuevo(): void
    {
        $pedido = $this->pedidoHabilitado();
        $this->pagoCon($pedido, EstadoIntentoPago::Rechazado, 'cc_rejected_insufficient_amount');

        $this->htmx('GET', route('pago.estado', $pedido->token_pago))
            ->assertOk()
            ->assertSee('El pago no se aprobó')
            ->assertSee('La tarjeta no tiene saldo o límite suficiente.')
            ->assertSee('Probar de nuevo')
            ->assertSee('href="'.route('pago.mostrar', $pedido->token_pago).'"', false)
            ->assertDontSee('every 5s');
    }

    public function test_un_pago_en_revision_pide_contactar_por_whatsapp_sin_seguir_consultando(): void
    {
        $pedido = $this->pedidoHabilitado(['estado_pago' => EstadoPago::Pendiente]);
        $this->pagoCon($pedido, EstadoIntentoPago::RequiereRevision);

        $this->htmx('GET', route('pago.estado', $pedido->token_pago))
            ->assertOk()
            ->assertSee('Estamos revisando tu pago.')
            ->assertSee('Te contactamos por WhatsApp.')
            ->assertDontSee('every 5s');
    }

    public function test_el_pago_aprobado_ofrece_ver_el_pedido_al_cliente_dueno(): void
    {
        $usuario = Usuario::factory()->create();
        $pedido = $this->pedidoPagado(['usuario_id' => $usuario->getKey()]);

        $this->actingAs($usuario);

        $this->htmx('GET', route('pago.estado', $pedido->token_pago))
            ->assertOk()
            ->assertSee('¡Listo! Recibimos tu pago.')
            ->assertSee('Ver mi pedido')
            ->assertSee(route('cuenta.pedidos.mostrar', $pedido->numero));
    }

    public function test_el_pago_aprobado_sin_sesion_no_ofrece_ver_el_pedido(): void
    {
        $pedido = $this->pedidoPagado();

        $this->htmx('GET', route('pago.estado', $pedido->token_pago))
            ->assertOk()
            ->assertSee('¡Listo! Recibimos tu pago.')
            ->assertDontSee('Ver mi pedido');
    }

    public function test_el_estado_sin_htmx_sigue_siendo_json_y_el_intento_responde_json(): void
    {
        $pedido = $this->pedidoHabilitado();
        $regla = $this->reglaActiva(TipoMedioPago::Credito, 3, '20');

        $this->getJson(route('pago.estado', $pedido->token_pago))
            ->assertOk()
            ->assertExactJson(['estado_pago' => 'habilitado', 'intento_vigente' => null]);

        $this->postJson(route('pago.intentos.crear', $pedido->token_pago), ['regla_precio_pago_id' => $regla->id])
            ->assertCreated()
            ->assertJsonPath('intento.importe', '1250.00')
            ->assertJsonPath('intento.cuotas', 3)
            ->assertJsonMissingPath('intento.url_tarjeta');

        $this->getJson(route('pago.mostrar', $pedido->token_pago))
            ->assertOk()
            ->assertJsonStructure(['numero', 'nombre_cliente', 'items', 'total_base', 'opciones', 'public_key', 'intento_vigente'])
            ->assertJsonMissingPath('opciones.0.porcentaje');
    }

    /**
     * Solicitud de htmx. Las cabeceras se limpian después para que no pasen a la solicitud siguiente de la prueba.
     *
     * @param  array<string, mixed>  $datos
     * @param  array<string, string>  $cabeceras
     */
    private function htmx(string $metodo, string $url, array $datos = [], array $cabeceras = []): TestResponse
    {
        $solicitud = $this->withHeaders(['HX-Request' => 'true', ...$cabeceras]);
        $respuesta = $metodo === 'GET' ? $solicitud->get($url) : $solicitud->post($url, $datos);

        $this->flushHeaders();

        return $respuesta;
    }

    private function modalidadMarcada(TestResponse $respuesta): ?string
    {
        $documento = new DOMDocument;
        libxml_use_internal_errors(true);
        $documento->loadHTML($respuesta->getContent());
        libxml_clear_errors();

        $marcada = (new DOMXPath($documento))->query('//input[@type="radio" and @checked]')->item(0);

        return $marcada?->getAttribute('value');
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

    private function reglaActiva(TipoMedioPago $tipo, int $cuotas, string $porcentaje): ReglaPrecioPago
    {
        return ReglaPrecioPago::factory()->activa()->create([
            'tipo_medio_pago' => $tipo,
            'cuotas' => $cuotas,
            'porcentaje' => $porcentaje,
        ]);
    }

    /** @param  array<string, mixed>  $atributos */
    private function pedidoPagado(array $atributos = []): Pedido
    {
        return $this->pedidoHabilitado(array_merge([
            'estado_pedido' => EstadoPedido::Confirmado,
            'estado_pago' => EstadoPago::Pagado,
        ], $atributos));
    }

    /** @param  array{tipo_medio_pago?: TipoMedioPago, cuotas?: int, id_orden_mercado_pago?: string}  $atributos */
    private function pagoCon(Pedido $pedido, EstadoIntentoPago $estado, ?string $detalle = null, array $atributos = []): Pago
    {
        return Pago::factory()->for($pedido, 'pedido')->create([
            'estado' => $estado,
            'detalle_estado' => $detalle,
            'tipo_medio_pago' => TipoMedioPago::Credito,
            'cuotas' => 3,
            'importe_base' => '1000.00',
            'porcentaje_aplicado' => '20.000000',
            'importe_cobrado' => '1250.00',
            ...$atributos,
        ]);
    }

    private function pagoConPreferencia(Pedido $pedido): Pago
    {
        return $this->pagoCon($pedido, EstadoIntentoPago::Creado, atributos: [
            'tipo_medio_pago' => TipoMedioPago::DineroEnCuenta,
            'cuotas' => 1,
            'id_orden_mercado_pago' => 'pref-123',
        ]);
    }

    /** @return array{id: int, status: string, status_detail: string, currency_id: string, transaction_amount: int, installments: int, payment_type_id: string, payment_method_id: string, external_reference: string} */
    private function respuestaMp(Pedido $pedido, Pago $pago, string $estado, string $detalle): array
    {
        return [
            'id' => 559,
            'status' => $estado,
            'status_detail' => $detalle,
            'currency_id' => 'ARS',
            'transaction_amount' => 1250,
            'installments' => 1,
            'payment_type_id' => 'account_money',
            'payment_method_id' => 'account_money',
            'external_reference' => "{$pedido->numero}:{$pago->id}",
        ];
    }
}
