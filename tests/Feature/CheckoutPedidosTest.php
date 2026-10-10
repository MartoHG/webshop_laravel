<?php

namespace Tests\Feature;

use App\Enums\EstadoEnvio;
use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Models\Configuracion;
use App\Models\ItemPedido;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\RegistroAuditoria;
use App\Models\Usuario;
use App\Models\VarianteProducto;
use App\Servicios\Pedidos\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CheckoutPedidosTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_exige_iniciar_sesion(): void
    {
        $this->configurarAjuste('10');

        $this->getJson('/checkout')->assertUnauthorized();
        $this->postJson('/checkout', $this->datosEnvioValidos(str_repeat('a', 64)))->assertUnauthorized();
        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_resumen_informa_si_el_email_no_esta_verificado_y_confirmar_lo_exige(): void
    {
        $this->configurarAjuste('10');
        $variante = $this->crearVarianteDisponible('100.00');
        $usuario = Usuario::factory()->noVerificado()->create();
        $this->postJson('/carrito/items', ['variante_producto_id' => $variante->id, 'cantidad' => 1])->assertOk();

        $resumen = $this->actingAs($usuario)
            ->getJson('/checkout')
            ->assertOk()
            ->assertJsonPath('checkout.email_verificado', false)
            ->assertJsonPath('checkout.cliente.email', $usuario->email)
            ->assertJsonPath('checkout.carrito.subtotal', '110.00')
            ->json('checkout');

        $this->postJson('/checkout', $this->datosEnvioValidos($resumen['huella']))
            ->assertForbidden()
            ->assertJsonPath('mensaje', 'Verificá tu email para confirmar el pedido.');

        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_confirmar_crea_pedido_con_snapshot_items_y_estados_iniciales(): void
    {
        $this->configurarAjuste('10');
        $variante = $this->crearVarianteDisponible('100.00');
        $otraVariante = $this->crearVarianteDisponible('50.00');
        $usuario = Usuario::factory()->create([
            'nombre' => 'Ana',
            'apellido' => 'Pérez',
            'dni' => '30111222',
            'email' => 'ana@example.com',
            'telefono' => '+54 9 11 5555-1234',
        ]);
        $this->postJson('/carrito/items', ['variante_producto_id' => $variante->id, 'cantidad' => 2])->assertOk();
        $this->postJson('/carrito/items', ['variante_producto_id' => $otraVariante->id, 'cantidad' => 1])->assertOk();

        $huella = $this->actingAs($usuario)->getJson('/checkout')->assertOk()->json('checkout.huella');

        $respuesta = $this->postJson('/checkout', array_merge(
            $this->datosEnvioValidos($huella),
            ['codigo_postal' => 'b7400abc'],
        ))
            ->assertCreated()
            ->assertJsonPath('pedido.estado_pedido', 'pendiente_coordinacion')
            ->assertJsonPath('pedido.estado_envio', 'pendiente_coordinacion')
            ->assertJsonPath('pedido.estado_pago', 'deshabilitado')
            ->assertJsonPath('pedido.costo_envio', null)
            ->assertJsonPath('pedido.total_base', null)
            ->assertJsonPath('pedido.subtotal_productos', '275.00')
            ->assertJsonPath('pedido.cliente.dni', '30111222')
            ->assertJsonPath('pedido.envio.provincia', 'cordoba')
            ->assertJsonPath('pedido.envio.provincia_nombre', 'Córdoba')
            ->assertJsonPath('pedido.envio.codigo_postal', 'B7400ABC')
            ->assertJsonCount(2, 'pedido.items')
            ->assertJsonPath('pedido.items.0.precio_unitario', '110.00')
            ->assertJsonPath('pedido.items.0.cantidad', 2)
            ->assertJsonPath('pedido.items.0.subtotal', '220.00')
            ->assertJsonMissingPath('pedido.huella_checkout');

        $numero = $respuesta->json('pedido.numero');
        $this->assertMatchesRegularExpression('/^ER-\d{4}-\d{6}$/', $numero);

        $pedido = Pedido::query()->where('numero', $numero)->sole();
        $this->assertSame($usuario->id, $pedido->usuario_id);
        $this->assertSame('ana@example.com', $pedido->email_cliente);
        $this->assertSame('Pérez', $pedido->apellido_cliente);
        $this->assertNull($pedido->token_pago);
        $this->assertSame($huella, $pedido->huella_checkout);
        $this->assertDatabaseHas('items_pedido', [
            'pedido_id' => $pedido->id,
            'variante_producto_id' => $variante->id,
            'sku' => $variante->sku,
            'precio_unitario' => '110.00',
            'cantidad' => 2,
            'subtotal' => '220.00',
        ]);
        $this->assertEmpty(session('carrito', []));
    }

    public function test_confirmar_dos_veces_seguidas_no_duplica_el_pedido(): void
    {
        $this->configurarAjuste('0');
        $variante = $this->crearVarianteDisponible('100.00');
        $usuario = Usuario::factory()->create();
        $this->postJson('/carrito/items', ['variante_producto_id' => $variante->id, 'cantidad' => 2])->assertOk();
        $huella = $this->actingAs($usuario)->getJson('/checkout')->assertOk()->json('checkout.huella');

        $numero = $this->postJson('/checkout', $this->datosEnvioValidos($huella))
            ->assertCreated()
            ->json('pedido.numero');

        // Simula la sesión obsoleta del doble envío: el carrito vuelve a tener las mismas líneas.
        $this->postJson('/carrito/items', ['variante_producto_id' => $variante->id, 'cantidad' => 2])->assertOk();

        $this->postJson('/checkout', $this->datosEnvioValidos($huella))
            ->assertOk()
            ->assertJsonPath('pedido.numero', $numero)
            ->assertJsonMissingPath('pedido.huella_checkout');

        $this->assertDatabaseCount('pedidos', 1);
        $this->assertDatabaseCount('items_pedido', 1);
        $this->assertEmpty(session('carrito', []));
    }

    public function test_confirmar_rechaza_si_el_precio_cambio_desde_el_resumen(): void
    {
        $this->configurarAjuste('0');
        $variante = $this->crearVarianteDisponible('100.00');
        $usuario = Usuario::factory()->create();
        $this->postJson('/carrito/items', ['variante_producto_id' => $variante->id, 'cantidad' => 1])->assertOk();

        $huella = $this->actingAs($usuario)->getJson('/checkout')->assertOk()->json('checkout.huella');

        $variante->update(['precio_base' => '120.00']);

        $this->postJson('/checkout', $this->datosEnvioValidos($huella))
            ->assertUnprocessable()
            ->assertJsonPath('errors.carrito.0', 'Los precios o la disponibilidad cambiaron. Revisá el resumen antes de confirmar.');

        $this->assertDatabaseCount('pedidos', 0);
        $this->assertSame([$variante->id => 1], session('carrito'));
    }

    public function test_confirmar_rechaza_carrito_vacio_o_con_items_no_disponibles(): void
    {
        $this->configurarAjuste('10');
        $variante = $this->crearVarianteDisponible('100.00');
        $usuario = Usuario::factory()->create();

        $huellaVacia = $this->actingAs($usuario)->getJson('/checkout')->assertOk()->json('checkout.huella');

        $this->postJson('/checkout', $this->datosEnvioValidos($huellaVacia))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('carrito');

        $this->postJson('/carrito/items', ['variante_producto_id' => $variante->id, 'cantidad' => 1])->assertOk();
        $huella = $this->getJson('/checkout')->assertOk()->json('checkout.huella');

        $variante->forceFill(['disponible' => false])->save();

        $this->postJson('/checkout', $this->datosEnvioValidos($huella))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('carrito');

        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_snapshot_no_cambia_al_editar_el_perfil_ni_el_precio_de_la_variante(): void
    {
        $this->configurarAjuste('0');
        $variante = $this->crearVarianteDisponible('100.00');
        $usuario = Usuario::factory()->create(['telefono' => '+54 9 11 1111-1111']);
        $dniOriginal = $usuario->dni;
        $this->postJson('/carrito/items', ['variante_producto_id' => $variante->id, 'cantidad' => 2])->assertOk();
        $huella = $this->actingAs($usuario)->getJson('/checkout')->assertOk()->json('checkout.huella');

        $numero = $this->postJson('/checkout', $this->datosEnvioValidos($huella))
            ->assertCreated()
            ->json('pedido.numero');

        $this->actingAs($usuario)->putJson('/cuenta/perfil', [
            'dni' => '31222333',
            'telefono' => '+54 9 11 2222-2222',
            'email' => $usuario->email,
        ])->assertOk();

        $variante->update(['precio_base' => '999.00']);

        $this->actingAs($usuario)->getJson("/cuenta/pedidos/{$numero}")
            ->assertOk()
            ->assertJsonPath('pedido.cliente.telefono', '+54 9 11 1111-1111')
            ->assertJsonPath('pedido.cliente.dni', $dniOriginal)
            ->assertJsonPath('pedido.items.0.precio_unitario', '100.00')
            ->assertJsonPath('pedido.subtotal_productos', '200.00');

        $this->assertDatabaseHas('pedidos', ['numero' => $numero, 'telefono_cliente' => '+54 9 11 1111-1111', 'dni_cliente' => $dniOriginal]);
        $this->assertDatabaseHas('usuarios', ['id' => $usuario->id, 'dni' => '31222333']);
    }

    public function test_datos_de_envio_invalidos_se_rechazan(): void
    {
        $this->configurarAjuste('10');
        $variante = $this->crearVarianteDisponible('100.00');
        $usuario = Usuario::factory()->create();
        $this->postJson('/carrito/items', ['variante_producto_id' => $variante->id, 'cantidad' => 1])->assertOk();
        $huella = $this->actingAs($usuario)->getJson('/checkout')->assertOk()->json('checkout.huella');

        $invalidos = [
            ['provincia' => 'provincia_inventada'],
            ['codigo_postal' => 'ABC'],
            ['direccion' => ''],
            ['numero' => str_repeat('1', 11)],
            ['observaciones' => str_repeat('x', 501)],
            ['huella' => 'corta'],
        ];

        foreach ($invalidos as $cambio) {
            $this->postJson('/checkout', array_merge($this->datosEnvioValidos($huella), $cambio))
                ->assertUnprocessable()
                ->assertJsonValidationErrors(array_key_first($cambio));
        }

        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_usuario_desactivado_no_puede_confirmar(): void
    {
        $this->configurarAjuste('0');
        $variante = $this->crearVarianteDisponible('100.00');
        $usuario = Usuario::factory()->create();
        $this->postJson('/carrito/items', ['variante_producto_id' => $variante->id, 'cantidad' => 1])->assertOk();
        $huella = $this->actingAs($usuario)->getJson('/checkout')->assertOk()->json('checkout.huella');

        $usuario->update(['activo' => false]);

        $this->actingAs($usuario)
            ->postJson('/checkout', $this->datosEnvioValidos($huella))
            ->assertForbidden();

        $this->assertDatabaseCount('pedidos', 0);

        try {
            app(CheckoutService::class)->confirmar($usuario, $this->datosEnvioSinHuella(), $huella);
            $this->fail('El servicio debía rechazar al usuario desactivado.');
        } catch (ValidationException $excepcion) {
            $this->assertArrayHasKey('usuario', $excepcion->errors());
        }

        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_cliente_ve_solo_sus_pedidos_y_no_ve_el_token_de_pago(): void
    {
        $cliente = Usuario::factory()->create();
        $otro = Usuario::factory()->create();
        $propio = Pedido::factory()->conTokenPago()->create(['usuario_id' => $cliente->id, 'numero' => 'ER-2026-000201']);
        $ajeno = Pedido::factory()->conTokenPago()->create(['usuario_id' => $otro->id, 'numero' => 'ER-2026-000202']);
        $tokenPropio = $propio->token_pago;

        $this->actingAs($cliente)->getJson('/cuenta/pedidos')
            ->assertOk()
            ->assertJsonCount(1, 'pedidos.data')
            ->assertJsonPath('pedidos.data.0.numero', $propio->numero)
            ->assertJsonMissingPath('pedidos.data.0.token_pago')
            ->assertDontSee($tokenPropio)
            ->assertDontSee($ajeno->numero);

        $this->actingAs($cliente)->getJson("/cuenta/pedidos/{$propio->numero}")
            ->assertOk()
            ->assertJsonPath('pedido.numero', $propio->numero)
            ->assertDontSee($tokenPropio);

        $this->actingAs($cliente)->getJson("/cuenta/pedidos/{$ajeno->numero}")->assertNotFound();
    }

    public function test_administrador_lista_filtra_y_busca_pedidos(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        // Datos fijos y distintos: la búsqueda solo puede coincidir con la fila buscada, nunca con datos aleatorios.
        $pendiente = Pedido::factory()->create([
            'numero' => 'ER-2026-000101',
            'apellido_cliente' => 'Gómez',
            'dni_cliente' => '30123456',
            'email_cliente' => 'ana@example.com',
            'estado_envio' => EstadoEnvio::Cotizado,
            'created_at' => now()->subDays(5),
        ]);
        $pagado = Pedido::factory()->pagado()->create([
            'numero' => 'ER-2026-000102',
            'apellido_cliente' => 'Pérez',
            'dni_cliente' => '40111222',
            'email_cliente' => 'luis@example.com',
            'created_at' => now()->subDay(),
        ]);
        $cancelado = Pedido::factory()->cancelado()->create([
            'numero' => 'ER-2026-000103',
            'apellido_cliente' => 'Fernández',
            'dni_cliente' => '40333444',
            'email_cliente' => 'marta@example.com',
            'created_at' => now(),
        ]);
        $item = ItemPedido::factory()->for($pagado, 'pedido')->create(['cantidad' => 2]);

        $this->actingAs($administrador)->getJson('/administracion/pedidos')
            ->assertOk()
            ->assertJsonCount(3, 'pedidos.data')
            ->assertJsonPath('pedidos.data.0.id', $cancelado->id)
            ->assertJsonPath('pedidos.data.1.items.0.variante_producto_id', $item->variante_producto_id)
            ->assertJsonPath('pedidos.data.1.items.0.cantidad', 2)
            ->assertJsonMissingPath('pedidos.data.1.huella_checkout')
            ->assertJsonPath('pedidos.data.2.id', $pendiente->id);

        $this->actingAs($administrador)->getJson('/administracion/pedidos?estado_pago=pagado')
            ->assertOk()
            ->assertJsonCount(1, 'pedidos.data')
            ->assertJsonPath('pedidos.data.0.id', $pagado->id);

        $this->actingAs($administrador)->getJson('/administracion/pedidos?estado_pedido=cancelado&estado_envio=pendiente_coordinacion')
            ->assertOk()
            ->assertJsonCount(1, 'pedidos.data')
            ->assertJsonPath('pedidos.data.0.id', $cancelado->id);

        $this->actingAs($administrador)->getJson('/administracion/pedidos?buscar=30123456')
            ->assertOk()
            ->assertJsonCount(1, 'pedidos.data')
            ->assertJsonPath('pedidos.data.0.id', $pendiente->id);

        $this->actingAs($administrador)->getJson('/administracion/pedidos?buscar=luis@example')
            ->assertOk()
            ->assertJsonCount(1, 'pedidos.data')
            ->assertJsonPath('pedidos.data.0.id', $pagado->id);

        $this->actingAs($administrador)->getJson('/administracion/pedidos?buscar=Gómez')
            ->assertOk()
            ->assertJsonCount(1, 'pedidos.data')
            ->assertJsonPath('pedidos.data.0.id', $pendiente->id);

        $this->actingAs($administrador)->getJson('/administracion/pedidos?buscar='.$pagado->numero)
            ->assertOk()
            ->assertJsonCount(1, 'pedidos.data')
            ->assertJsonPath('pedidos.data.0.id', $pagado->id);

        $this->actingAs($administrador)->getJson('/administracion/pedidos?desde='.now()->subDays(2)->toDateString())
            ->assertOk()
            ->assertJsonCount(2, 'pedidos.data');

        $this->actingAs($administrador)->getJson('/administracion/pedidos?estado_pago=inventado')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('estado_pago');
    }

    public function test_cliente_no_accede_a_la_administracion_de_pedidos(): void
    {
        $cliente = Usuario::factory()->create();
        $pedido = Pedido::factory()->create();

        $this->actingAs($cliente)->getJson('/administracion/pedidos')->assertForbidden();
        $this->actingAs($cliente)->getJson("/administracion/pedidos/{$pedido->id}")->assertForbidden();
        $this->actingAs($cliente)
            ->postJson("/administracion/pedidos/{$pedido->id}/cancelar", ['motivo' => 'otro', 'detalle' => 'Prueba'])
            ->assertForbidden();

        $this->assertSame(EstadoPago::Deshabilitado, $pedido->fresh()->estado_pago);
    }

    public function test_administrador_cancela_pedido_con_motivo_y_queda_auditado(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $pedido = Pedido::factory()->conTokenPago()->create();
        $tokenPago = $pedido->token_pago;
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.20']);

        $this->actingAs($administrador)
            ->postJson("/administracion/pedidos/{$pedido->id}/cancelar", ['motivo' => 'cliente_desistio'])
            ->assertOk()
            ->assertJsonPath('pedido.estado_pedido', 'cancelado')
            ->assertJsonPath('pedido.estado_pago', 'cancelado')
            ->assertJsonPath('pedido.motivo_cancelacion', 'cliente_desistio')
            ->assertJsonPath('pedido.cancelado_por.id', $administrador->id)
            ->assertDontSee($tokenPago);

        $pedido->refresh();
        $this->assertNull($pedido->token_pago);
        $this->assertNotNull($pedido->cancelado_en);
        $this->assertSame(EstadoPedido::Cancelado, $pedido->estado_pedido);

        $registro = RegistroAuditoria::query()->where('accion', 'pedido.cancelado')->sole();
        $this->assertSame($administrador->id, $registro->usuario_id);
        $this->assertSame('pedidos', $registro->entidad);
        $this->assertSame($pedido->id, $registro->entidad_id);
        $this->assertSame('203.0.113.20', $registro->ip);
        $this->assertEqualsCanonicalizing(
            ['estado_pedido' => 'pendiente_coordinacion', 'estado_pago' => 'deshabilitado'],
            $registro->datos_anteriores,
        );
        $this->assertEqualsCanonicalizing([
            'estado_pedido' => 'cancelado',
            'estado_pago' => 'cancelado',
            'motivo_cancelacion' => 'cliente_desistio',
            'detalle_cancelacion' => null,
        ], $registro->datos_nuevos);
    }

    public function test_no_se_cancela_pedido_pagado_con_pago_pendiente_ya_cancelado_ni_con_motivo_reemplazado(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $pagado = Pedido::factory()->pagado()->create();
        $pagoPendiente = Pedido::factory()->pagoPendiente()->create();
        $yaCancelado = Pedido::factory()->cancelado()->create();
        $elegible = Pedido::factory()->create();

        foreach ([$pagado, $pagoPendiente, $yaCancelado] as $pedido) {
            $this->actingAs($administrador)
                ->postJson("/administracion/pedidos/{$pedido->id}/cancelar", ['motivo' => 'cliente_desistio'])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('pedido');
        }

        $this->actingAs($administrador)
            ->postJson("/administracion/pedidos/{$elegible->id}/cancelar", ['motivo' => 'reemplazado'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('motivo');

        $this->assertSame(EstadoPedido::Confirmado, $pagado->fresh()->estado_pedido);
        $this->assertSame(EstadoPago::Pendiente, $pagoPendiente->fresh()->estado_pago);
        $this->assertSame(EstadoPedido::PendienteCoordinacion, $elegible->fresh()->estado_pedido);
        $this->assertCount(0, RegistroAuditoria::query()->where('accion', 'pedido.cancelado')->get());
    }

    public function test_motivo_otro_exige_detalle(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $pedido = Pedido::factory()->create();
        $url = "/administracion/pedidos/{$pedido->id}/cancelar";

        $this->actingAs($administrador)
            ->postJson($url, ['motivo' => 'otro'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('detalle');

        $this->actingAs($administrador)
            ->postJson($url, ['motivo' => 'otro', 'detalle' => '   '])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('detalle');

        $this->actingAs($administrador)
            ->postJson($url, ['motivo' => 'motivo_inventado'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('motivo');

        $this->actingAs($administrador)
            ->postJson($url, ['motivo' => 'otro', 'detalle' => 'El cliente pidió otra talla'])
            ->assertOk()
            ->assertJsonPath('pedido.motivo_cancelacion', 'otro')
            ->assertJsonPath('pedido.detalle_cancelacion', 'El cliente pidió otra talla');
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
        return array_merge($this->datosEnvioSinHuella(), ['huella' => $huella]);
    }

    /** @return array<string, string> */
    private function datosEnvioSinHuella(): array
    {
        return [
            'provincia' => 'cordoba',
            'localidad' => 'Río Cuarto',
            'codigo_postal' => '5800',
            'direccion' => 'Av. Italia',
            'numero' => '123',
            'direccion_adicional' => 'Piso 2 Depto B',
            'observaciones' => 'Timbre roto, llamar antes',
        ];
    }
}
