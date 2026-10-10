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
use App\Models\Producto;
use App\Models\RegistroAuditoria;
use App\Models\ReglaPrecioPago;
use App\Models\Usuario;
use App\Models\VarianteProducto;
use App\Notifications\Pedidos\AlertaPagoEnRevision;
use App\Notifications\Pedidos\NoPudimosContactarte;
use App\Notifications\Pedidos\PagoAprobado;
use App\Notifications\Pedidos\PedidoCreado;
use App\Notifications\Pedidos\PedidoDespachado;
use App\Notifications\Pedidos\RecordatorioPedido;
use App\Servicios\Pagos\PagoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailsPedidoTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_envia_pedido_creado_al_email_del_snapshot_y_no_al_repetir_la_confirmacion(): void
    {
        Notification::fake();
        $this->configurarAjuste('0');
        $variante = $this->crearVarianteDisponible('100.00');
        $usuario = Usuario::factory()->create(['email' => 'ana@example.com']);
        $this->postJson('/carrito/items', ['variante_producto_id' => $variante->id, 'cantidad' => 2])->assertOk();
        $huella = $this->actingAs($usuario)->getJson('/checkout')->assertOk()->json('checkout.huella');

        $numero = $this->postJson('/checkout', $this->datosEnvioValidos($huella))
            ->assertCreated()
            ->json('pedido.numero');

        Notification::assertSentOnDemandTimes(PedidoCreado::class, 1);
        Notification::assertSentOnDemand(PedidoCreado::class, function (PedidoCreado $notificacion, array $canales, AnonymousNotifiable $destino) use ($numero): bool {
            return $destino->routeNotificationFor('mail') === 'ana@example.com'
                && $notificacion->pedido->numero === $numero;
        });
        Notification::assertNotSentTo($usuario, PedidoCreado::class);

        // El perfil cambia después de confirmar: el pedido conserva el email del momento de la compra.
        $usuario->update(['email' => 'nueva@example.com']);
        $this->postJson('/carrito/items', ['variante_producto_id' => $variante->id, 'cantidad' => 2])->assertOk();

        $this->postJson('/checkout', $this->datosEnvioValidos($huella))
            ->assertOk()
            ->assertJsonPath('pedido.numero', $numero);

        Notification::assertSentOnDemandTimes(PedidoCreado::class, 1);
    }

    public function test_pago_aprobado_envia_un_solo_email_aunque_se_aplique_dos_veces(): void
    {
        Notification::fake();
        $servicio = app(PagoService::class);
        $pedido = $this->pedidoHabilitado();
        $intento = $servicio->iniciarIntento($pedido, $this->reglaCredito(3, '20')->id);
        $pago = Pago::query()->findOrFail($intento['pago_id']);
        $respuesta = $this->respuestaMp(['external_reference' => "{$pedido->numero}:{$pago->id}"]);

        $servicio->aplicarPagoMercadoPago($respuesta);
        $servicio->aplicarPagoMercadoPago($respuesta);

        Notification::assertSentOnDemandTimes(PagoAprobado::class, 1);
        Notification::assertSentOnDemand(PagoAprobado::class, function (PagoAprobado $notificacion, array $canales, AnonymousNotifiable $destino) use ($pedido, $pago): bool {
            return $destino->routeNotificationFor('mail') === $pedido->email_cliente
                && $notificacion->pago->is($pago)
                && $notificacion->pedido->is($pedido);
        });
    }

    public function test_aceptar_un_pago_en_revision_envia_pago_aprobado(): void
    {
        Notification::fake();
        $administrador = Usuario::factory()->administrador()->create();
        $pedido = $this->pedidoHabilitado(['estado_pago' => EstadoPago::Pendiente]);
        $pago = $this->pagoEnRevision($pedido, '900', 'importe_distinto');

        $this->actingAs($administrador)
            ->postJson("/administracion/pedidos/{$pedido->id}/pagos/{$pago->id}/resolver", [
                'decision' => 'aceptar',
                'nota' => 'Confirmado con el cliente por WhatsApp.',
            ])
            ->assertOk();

        Notification::assertSentOnDemandTimes(PagoAprobado::class, 1);
        Notification::assertSentOnDemand(PagoAprobado::class, function (PagoAprobado $notificacion, array $canales, AnonymousNotifiable $destino) use ($pedido): bool {
            return $destino->routeNotificationFor('mail') === $pedido->email_cliente;
        });
    }

    public function test_pago_en_revision_alerta_a_los_administradores_activos(): void
    {
        Notification::fake();
        $administrador = Usuario::factory()->administrador()->create();
        $otroAdministrador = Usuario::factory()->administrador()->create();
        $administradorInactivo = Usuario::factory()->administrador()->create(['activo' => false]);
        $cliente = Usuario::factory()->create();
        $servicio = app(PagoService::class);
        $pedido = $this->pedidoHabilitado();
        $intento = $servicio->iniciarIntento($pedido, $this->reglaCredito(3, '20')->id);
        $pago = Pago::query()->findOrFail($intento['pago_id']);

        $servicio->aplicarPagoMercadoPago($this->respuestaMp([
            'external_reference' => "{$pedido->numero}:{$pago->id}",
            'transaction_amount' => 999.0,
        ]));

        Notification::assertSentTo($administrador, AlertaPagoEnRevision::class);
        Notification::assertSentTo($otroAdministrador, AlertaPagoEnRevision::class);
        Notification::assertNotSentTo($administradorInactivo, AlertaPagoEnRevision::class);
        Notification::assertNotSentTo($cliente, AlertaPagoEnRevision::class);
        Notification::assertSentTimes(AlertaPagoEnRevision::class, 2);
        Notification::assertSentOnDemandTimes(PagoAprobado::class, 0);
    }

    public function test_despachar_no_envia_email_automaticamente(): void
    {
        Notification::fake();
        $administrador = Usuario::factory()->administrador()->create();
        $pedido = Pedido::factory()->pagado()->create([
            'estado_envio' => EstadoEnvio::Preparado,
            'expreso' => 'Andreani',
        ]);

        $this->actingAs($administrador)
            ->patchJson("/administracion/pedidos/{$pedido->id}/envio/estado", ['estado_envio' => 'despachado'])
            ->assertOk();

        $this->assertSame(EstadoEnvio::Despachado, $pedido->fresh()->estado_envio);
        Notification::assertNothingSent();
    }

    public function test_administrador_envia_emails_manuales_segun_el_estado_del_pedido(): void
    {
        Notification::fake();
        $administrador = Usuario::factory()->administrador()->create();
        $habilitado = [
            'estado_pago' => EstadoPago::Habilitado,
            'token_pago' => bin2hex(random_bytes(24)),
            'subtotal_productos' => '800.00',
            'costo_envio' => '200.00',
            'total_base' => '1000.00',
        ];
        $pagado = ['estado_pedido' => EstadoPedido::Confirmado, 'estado_pago' => EstadoPago::Pagado];
        $despachado = ['estado_envio' => EstadoEnvio::Despachado] + $pagado;

        // [tipo, atributos del pedido, pedido cancelado, permitido]
        $casos = [
            ['no-contactado', [], false, true],
            ['no-contactado', $pagado, false, true],
            ['no-contactado', [], true, false],
            ['recordatorio', [], false, true],
            ['recordatorio', $habilitado, false, true],
            ['recordatorio', ['estado_pago' => EstadoPago::Pendiente], false, false],
            ['recordatorio', $pagado, false, false],
            ['recordatorio', [], true, false],
            ['despachado', $despachado, false, true],
            ['despachado', ['estado_envio' => EstadoEnvio::Aceptado], false, false],
            ['despachado', $despachado, true, false],
        ];

        $enviados = 0;

        foreach ($casos as [$tipo, $atributos, $cancelado, $permitido]) {
            $pedido = $cancelado
                ? Pedido::factory()->cancelado()->create(array_merge($atributos, [
                    'estado_pedido' => EstadoPedido::Cancelado,
                    'estado_pago' => EstadoPago::Cancelado,
                ]))
                : Pedido::factory()->create($atributos);

            $respuesta = $this->actingAs($administrador)
                ->postJson("/administracion/pedidos/{$pedido->id}/emails/{$tipo}", ['mensaje' => 'Nota de prueba.']);

            if ($permitido) {
                $respuesta->assertAccepted()->assertJsonPath('mensaje', 'El email quedó en cola para enviarse.');
                $enviados++;
            } else {
                $respuesta->assertUnprocessable()->assertJsonValidationErrors(['email']);
            }
        }

        Notification::assertSentOnDemandTimes(NoPudimosContactarte::class, 2);
        Notification::assertSentOnDemandTimes(RecordatorioPedido::class, 2);
        Notification::assertSentOnDemandTimes(PedidoDespachado::class, 1);
        $this->assertSame($enviados, RegistroAuditoria::query()->where('accion', 'pedido.email_enviado')->count());
    }

    public function test_no_se_repite_el_mismo_email_manual_en_10_minutos(): void
    {
        Notification::fake();
        $administrador = Usuario::factory()->administrador()->create();
        $pedido = Pedido::factory()->create();
        $url = "/administracion/pedidos/{$pedido->id}/emails/recordatorio";

        $this->actingAs($administrador)->postJson($url)->assertAccepted();

        $this->actingAs($administrador)->postJson($url)
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'Ya enviaste este email hace un momento.');

        // Otro tipo de email para el mismo pedido no comparte el límite.
        $this->actingAs($administrador)
            ->postJson("/administracion/pedidos/{$pedido->id}/emails/no-contactado")
            ->assertAccepted();

        $this->travel(11)->minutes();

        $this->actingAs($administrador)->postJson($url)->assertAccepted();

        Notification::assertSentOnDemandTimes(RecordatorioPedido::class, 2);
        Notification::assertSentOnDemandTimes(NoPudimosContactarte::class, 1);
    }

    public function test_recordatorio_con_pago_habilitado_incluye_el_link_de_pago(): void
    {
        $token = bin2hex(random_bytes(24));
        $pedido = $this->pedidoHabilitado(['token_pago' => $token]);

        $mail = (new RecordatorioPedido($pedido))->toMail(new AnonymousNotifiable);
        $html = $mail->render();

        $this->assertSame(url('/pago/'.$token), $mail->actionUrl);
        $this->assertSame('Pagar mi pedido', $mail->actionText);
        $this->assertStringContainsString(url('/pago/'.$token), $html);
        $this->assertStringContainsString('$1.000,00', $html);
        $this->assertStringContainsString($pedido->numero, $html);
    }

    public function test_cliente_no_puede_enviar_emails_manuales(): void
    {
        Notification::fake();
        $cliente = Usuario::factory()->create();
        $pedido = $this->pedidoHabilitado();

        $this->actingAs($cliente)
            ->postJson("/administracion/pedidos/{$pedido->id}/emails/recordatorio")
            ->assertForbidden();

        Notification::assertNothingSent();
    }

    public function test_emails_se_encolan(): void
    {
        $notificaciones = [
            PedidoCreado::class,
            PagoAprobado::class,
            AlertaPagoEnRevision::class,
            NoPudimosContactarte::class,
            RecordatorioPedido::class,
            PedidoDespachado::class,
        ];

        foreach ($notificaciones as $notificacion) {
            $this->assertContains(ShouldQueue::class, class_implements($notificacion), $notificacion);
            $this->assertTrue(get_class_vars($notificacion)['afterCommit'], $notificacion);
            $this->assertSame(5, get_class_vars($notificacion)['tries'], $notificacion);
        }
    }

    private function configurarAjuste(string $porcentaje): void
    {
        Configuracion::query()->updateOrCreate(
            ['clave' => 'ajuste_ecommerce_porcentaje'],
            ['valor' => $porcentaje],
        );
    }

    private function crearVarianteDisponible(string $precioBase = '100.00'): VarianteProducto
    {
        $producto = Producto::factory()->create([
            'nombre' => 'Mesa de jardín',
            'activo' => true,
            'disponible' => true,
        ]);

        return VarianteProducto::factory()->create([
            'producto_id' => $producto->id,
            'color' => 'Verde',
            'precio_base' => $precioBase,
            'activo' => true,
            'disponible' => true,
        ]);
    }

    /** @return array<string, string> */
    private function datosEnvioValidos(string $huella): array
    {
        return array_merge([
            'provincia' => 'cordoba',
            'localidad' => 'Río Cuarto',
            'codigo_postal' => '5800',
            'direccion' => 'Av. Italia',
            'numero' => '123',
            'direccion_adicional' => 'Piso 2 Depto B',
            'observaciones' => 'Timbre roto, llamar antes',
        ], ['huella' => $huella]);
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
