<?php

namespace Tests\Feature;

use App\Enums\EstadoContacto;
use App\Enums\EstadoEnvio;
use App\Enums\EstadoIntentoPago;
use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Enums\MotivoCancelacion;
use App\Enums\Provincia;
use App\Models\Configuracion;
use App\Models\ItemPedido;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\RegistroAuditoria;
use App\Models\ReglaPrecioPago;
use App\Models\Usuario;
use App\Models\VarianteProducto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EdicionPedidosTest extends TestCase
{
    use RefreshDatabase;

    private const string MENSAJE_CON_ORDEN_DE_PAGO = 'El pedido ya tiene una orden de pago. Para cambiarlo, reemplazalo por un pedido nuevo.';

    private const string MENSAJE_PAGADO = 'El pedido ya está pagado y no admite cambios.';

    private const string MENSAJE_CANCELADO = 'El pedido está cancelado y no admite cambios.';

    public function test_administrador_agrega_quita_y_cambia_cantidades_recalculando_totales(): void
    {
        $this->configurarAjuste('10');
        $administrador = Usuario::factory()->administrador()->create();
        $kayak = $this->varianteDisponible('100.00', 'Kayak Río Azul', 'KAY-AZ-001');
        $remo = $this->varianteDisponible('50.00', 'Remo doble', 'REM-DO-001');
        $pedido = Pedido::factory()->create(['costo_envio' => '100.00']);
        $this->lineaPedido($pedido, $kayak, '110.00', 2);
        $base = "/administracion/pedidos/{$pedido->id}";

        $pedidoConRemo = $this->actingAs($administrador)
            ->postJson("{$base}/items", ['variante_producto_id' => $remo->id, 'cantidad' => 1])
            ->assertOk()
            ->assertJsonPath('pedido.subtotal_productos', '275.00')
            ->assertJsonPath('pedido.total_base', '375.00')
            ->json('pedido');

        $lineaRemo = $this->lineaDe($pedidoConRemo, $remo->id);
        $this->assertSame('55.00', $lineaRemo['precio_unitario']);
        $this->assertSame('55.00', $lineaRemo['subtotal']);
        $this->assertSame('REM-DO-001', $lineaRemo['sku']);

        // El precio de la línea es un snapshot: cambiar el precio base no altera la línea al cambiar cantidades.
        $kayak->update(['precio_base' => '200.00']);
        $lineaKayak = $this->lineaDe($pedidoConRemo, $kayak->id);

        $pedidoConKayakActualizado = $this->actingAs($administrador)
            ->patchJson("{$base}/items/{$lineaKayak['id']}", ['cantidad' => 3])
            ->assertOk()
            ->assertJsonPath('pedido.subtotal_productos', '385.00')
            ->assertJsonPath('pedido.total_base', '485.00')
            ->json('pedido');

        $kayakActualizado = $this->lineaDe($pedidoConKayakActualizado, $kayak->id);
        $this->assertSame(3, $kayakActualizado['cantidad']);
        $this->assertSame('110.00', $kayakActualizado['precio_unitario']);
        $this->assertSame('330.00', $kayakActualizado['subtotal']);

        $this->actingAs($administrador)
            ->deleteJson("{$base}/items/{$lineaRemo['id']}")
            ->assertOk()
            ->assertJsonCount(1, 'pedido.items')
            ->assertJsonPath('pedido.subtotal_productos', '330.00')
            ->assertJsonPath('pedido.total_base', '430.00');

        $this->assertDatabaseCount('items_pedido', 1);
        $this->assertDatabaseHas('pedidos', ['id' => $pedido->id, 'subtotal_productos' => '330.00', 'total_base' => '430.00']);
    }

    public function test_cambiar_variante_resnapshotea_precio_y_fusiona_lineas(): void
    {
        $this->configurarAjuste('10');
        $administrador = Usuario::factory()->administrador()->create();
        $producto = $this->productoDisponible('Kayak Río Azul');
        $azul = $this->varianteDelProducto($producto, '100.00', 'Azul', 'KAY-AZ-002');
        $rojo = $this->varianteDelProducto($producto, '200.00', 'Rojo', 'KAY-RO-002');

        $sinFusion = Pedido::factory()->create();
        $lineaAzul = $this->lineaPedido($sinFusion, $azul, '110.00', 2);
        $base = "/administracion/pedidos/{$sinFusion->id}";

        $this->actingAs($administrador)
            ->patchJson("{$base}/items/{$lineaAzul->id}", ['variante_producto_id' => $rojo->id])
            ->assertOk()
            ->assertJsonCount(1, 'pedido.items')
            ->assertJsonPath('pedido.items.0.variante_producto_id', $rojo->id)
            ->assertJsonPath('pedido.items.0.color', 'Rojo')
            ->assertJsonPath('pedido.items.0.sku', 'KAY-RO-002')
            ->assertJsonPath('pedido.items.0.precio_unitario', '220.00')
            ->assertJsonPath('pedido.items.0.cantidad', 2)
            ->assertJsonPath('pedido.items.0.subtotal', '440.00')
            ->assertJsonPath('pedido.subtotal_productos', '440.00');

        $conFusion = Pedido::factory()->create();
        $lineaAzulFusion = $this->lineaPedido($conFusion, $azul, '110.00', 2);
        $this->lineaPedido($conFusion, $rojo, '220.00', 3);

        $this->actingAs($administrador)
            ->patchJson("/administracion/pedidos/{$conFusion->id}/items/{$lineaAzulFusion->id}", ['variante_producto_id' => $rojo->id])
            ->assertOk()
            ->assertJsonCount(1, 'pedido.items')
            ->assertJsonPath('pedido.items.0.variante_producto_id', $rojo->id)
            ->assertJsonPath('pedido.items.0.cantidad', 5)
            ->assertJsonPath('pedido.items.0.subtotal', '1100.00')
            ->assertJsonPath('pedido.subtotal_productos', '1100.00');

        $this->assertDatabaseMissing('items_pedido', ['id' => $lineaAzulFusion->id]);

        $sinEspacio = Pedido::factory()->create();
        $lineaAzulTope = $this->lineaPedido($sinEspacio, $azul, '110.00', 2);
        $this->lineaPedido($sinEspacio, $rojo, '220.00', 98);

        $this->actingAs($administrador)
            ->patchJson("/administracion/pedidos/{$sinEspacio->id}/items/{$lineaAzulTope->id}", ['variante_producto_id' => $rojo->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cantidad');

        $this->assertDatabaseHas('items_pedido', ['id' => $lineaAzulTope->id, 'variante_producto_id' => $azul->id, 'cantidad' => 2]);

        $this->actingAs($administrador)
            ->patchJson("/administracion/pedidos/{$sinEspacio->id}/items/{$lineaAzulTope->id}", ['variante_producto_id' => $azul->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('variante_producto_id');
    }

    public function test_cambios_con_envio_aceptado_vuelven_a_cotizado(): void
    {
        $this->configurarAjuste('10');
        $administrador = Usuario::factory()->administrador()->create();
        $kayak = $this->varianteDisponible('100.00', 'Kayak Río Azul', 'KAY-AZ-003');
        $remo = $this->varianteDisponible('50.00', 'Remo doble', 'REM-DO-003');
        $pedido = Pedido::factory()->create([
            'estado_envio' => EstadoEnvio::Cotizado,
            'expreso' => 'Andreani',
            'costo_envio' => '100.00',
        ]);
        $lineaKayak = $this->lineaPedido($pedido, $kayak, '110.00', 2);
        $base = "/administracion/pedidos/{$pedido->id}";

        $this->actingAs($administrador)->postJson("{$base}/envio/aceptacion")->assertOk();

        $this->actingAs($administrador)
            ->patchJson("{$base}/items/{$lineaKayak->id}", ['cantidad' => 3])
            ->assertOk()
            ->assertJsonPath('pedido.estado_envio', 'cotizado')
            ->assertJsonPath('pedido.costo_envio', '100.00')
            ->assertJsonPath('pedido.total_base', '430.00');

        $this->actingAs($administrador)->postJson("{$base}/envio/aceptacion")->assertOk();

        $this->actingAs($administrador)
            ->postJson("{$base}/items", ['variante_producto_id' => $remo->id, 'cantidad' => 1])
            ->assertOk()
            ->assertJsonPath('pedido.estado_envio', 'cotizado')
            ->assertJsonPath('pedido.total_base', '485.00');

        $this->actingAs($administrador)->postJson("{$base}/envio/aceptacion")->assertOk();

        $lineaRemo = ItemPedido::query()->where('pedido_id', $pedido->id)->where('variante_producto_id', $remo->id)->sole();

        $this->actingAs($administrador)
            ->deleteJson("{$base}/items/{$lineaRemo->id}")
            ->assertOk()
            ->assertJsonPath('pedido.estado_envio', 'cotizado')
            ->assertJsonPath('pedido.total_base', '430.00');

        $this->assertSame(EstadoEnvio::Cotizado, $pedido->fresh()->estado_envio);
    }

    public function test_no_se_agrega_variante_agotada_inactiva_ni_de_otro_producto(): void
    {
        $this->configurarAjuste('10');
        $administrador = Usuario::factory()->administrador()->create();
        $producto = $this->productoDisponible('Kayak Río Azul');
        $disponible = $this->varianteDelProducto($producto, '100.00', 'Azul', 'KAY-AZ-004');
        $agotada = $this->varianteDelProducto($producto, '100.00', 'Verde', 'KAY-VE-004', ['disponible' => false]);
        $inactiva = $this->varianteDelProducto($producto, '100.00', 'Negro', 'KAY-NE-004', ['activo' => false]);
        $productoInactivo = $this->productoDisponible('Chaleco salvavidas', ['activo' => false]);
        $variantePorProductoInactivo = $this->varianteDelProducto($productoInactivo, '100.00', 'Naranja', 'CHA-NA-004');
        $otroProducto = $this->varianteDisponible('50.00', 'Remo doble', 'REM-DO-004');
        $pedido = Pedido::factory()->create();
        $linea = $this->lineaPedido($pedido, $disponible, '110.00', 1);
        $base = "/administracion/pedidos/{$pedido->id}";

        foreach ([$agotada, $inactiva, $variantePorProductoInactivo] as $variante) {
            $this->actingAs($administrador)
                ->postJson("{$base}/items", ['variante_producto_id' => $variante->id, 'cantidad' => 1])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('variante_producto_id');
        }

        $this->actingAs($administrador)
            ->patchJson("{$base}/items/{$linea->id}", ['variante_producto_id' => $otroProducto->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('variante_producto_id');

        $this->actingAs($administrador)
            ->patchJson("{$base}/items/{$linea->id}", ['variante_producto_id' => $agotada->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('variante_producto_id');

        $this->assertDatabaseCount('items_pedido', 1);
        $this->assertDatabaseHas('items_pedido', ['id' => $linea->id, 'variante_producto_id' => $disponible->id]);
    }

    public function test_no_se_quita_el_ultimo_item(): void
    {
        $this->configurarAjuste('10');
        $administrador = Usuario::factory()->administrador()->create();
        $kayak = $this->varianteDisponible('100.00', 'Kayak Río Azul', 'KAY-AZ-005');
        $remo = $this->varianteDisponible('50.00', 'Remo doble', 'REM-DO-005');
        $pedido = Pedido::factory()->create();
        $unico = $this->lineaPedido($pedido, $kayak, '110.00', 1);
        $base = "/administracion/pedidos/{$pedido->id}";

        $this->actingAs($administrador)
            ->deleteJson("{$base}/items/{$unico->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pedido');

        $this->assertDatabaseHas('items_pedido', ['id' => $unico->id]);

        $otra = $this->lineaPedido($pedido, $remo, '55.00', 1);

        $this->actingAs($administrador)
            ->deleteJson("{$base}/items/{$unico->id}")
            ->assertOk()
            ->assertJsonCount(1, 'pedido.items');

        $this->actingAs($administrador)
            ->deleteJson("{$base}/items/{$otra->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pedido');

        $this->assertDatabaseCount('items_pedido', 1);
        $this->assertDatabaseHas('items_pedido', ['id' => $otra->id]);
    }

    public function test_no_se_edita_con_pago_habilitado_pendiente_pagado_o_pedido_cancelado(): void
    {
        $this->configurarAjuste('10');
        $administrador = Usuario::factory()->administrador()->create();
        $kayak = $this->varianteDisponible('100.00', 'Kayak Río Azul', 'KAY-AZ-006');
        $remo = $this->varianteDisponible('50.00', 'Remo doble', 'REM-DO-006');

        $casos = [
            [Pedido::factory()->create(['estado_pago' => EstadoPago::Habilitado]), self::MENSAJE_CON_ORDEN_DE_PAGO],
            [Pedido::factory()->pagoPendiente()->create(), self::MENSAJE_CON_ORDEN_DE_PAGO],
            [Pedido::factory()->pagado()->create(), self::MENSAJE_PAGADO],
            [Pedido::factory()->cancelado()->create(), self::MENSAJE_CANCELADO],
        ];

        foreach ($casos as [$pedido, $mensaje]) {
            $linea = $this->lineaPedido($pedido, $kayak, '110.00', 1);
            $this->lineaPedido($pedido, $remo, '55.00', 1);
            $base = "/administracion/pedidos/{$pedido->id}";

            $this->actingAs($administrador)
                ->postJson("{$base}/items", ['variante_producto_id' => $remo->id, 'cantidad' => 1])
                ->assertUnprocessable()
                ->assertJsonPath('errors.pedido.0', $mensaje);

            $this->actingAs($administrador)
                ->patchJson("{$base}/items/{$linea->id}", ['cantidad' => 4])
                ->assertUnprocessable()
                ->assertJsonPath('errors.pedido.0', $mensaje);

            $this->actingAs($administrador)
                ->patchJson("{$base}/items/{$linea->id}", ['variante_producto_id' => $remo->id])
                ->assertUnprocessable()
                ->assertJsonPath('errors.pedido.0', $mensaje);

            $this->actingAs($administrador)
                ->deleteJson("{$base}/items/{$linea->id}")
                ->assertUnprocessable()
                ->assertJsonPath('errors.pedido.0', $mensaje);

            $this->assertDatabaseHas('items_pedido', ['id' => $linea->id, 'variante_producto_id' => $kayak->id, 'cantidad' => 1]);
            $this->assertSame(2, $pedido->items()->count());
        }
    }

    public function test_reemplazo_cancela_el_original_y_su_link_y_crea_pedido_nuevo(): void
    {
        $this->configurarAjuste('10');
        $administrador = Usuario::factory()->administrador()->create();
        $cliente = Usuario::factory()->create();
        $kayak = $this->varianteDisponible('100.00', 'Kayak Río Azul', 'KAY-AZ-007');
        $remo = $this->varianteDisponible('50.00', 'Remo doble', 'REM-DO-007');
        $original = Pedido::factory()->conTokenPago()->create([
            'usuario_id' => $cliente->id,
            'numero' => 'ER-2026-000701',
            'provincia_envio' => Provincia::Cordoba,
            'localidad_envio' => 'Río Cuarto',
            'codigo_postal_envio' => '5800',
            'direccion_envio' => 'Av. Italia',
            'numero_envio' => '123',
            'direccion_adicional' => 'Piso 2',
            'observaciones_envio' => 'Timbre roto',
            'expreso' => 'Andreani',
            'costo_envio' => '100.00',
            'estado_pago' => EstadoPago::Habilitado,
            'estado_envio' => EstadoEnvio::Aceptado,
            'estado_contacto' => EstadoContacto::Respondio,
            'contactado_en' => now()->subDay(),
            'pago_habilitado_en' => now()->subHour(),
        ]);
        $this->lineaPedido($original, $kayak, '110.00', 2);
        $this->lineaPedido($original, $remo, '55.00', 1);
        $token = $original->token_pago;
        $pagoCreado = Pago::factory()->for($original, 'pedido')->create(['estado' => EstadoIntentoPago::Creado]);
        $pagoRechazado = Pago::factory()->for($original, 'pedido')->create(['estado' => EstadoIntentoPago::Rechazado]);
        $base = "/administracion/pedidos/{$original->id}";

        $nuevo = $this->actingAs($administrador)
            ->postJson("{$base}/reemplazar", ['nota' => '  El cliente quiere otro color  '])
            ->assertCreated()
            ->assertJsonPath('pedido.estado_pedido', 'pendiente_coordinacion')
            ->assertJsonPath('pedido.estado_pago', 'deshabilitado')
            ->assertJsonPath('pedido.estado_envio', 'cotizado')
            ->assertJsonPath('pedido.estado_contacto', 'respondio')
            ->assertJsonPath('pedido.reemplaza_a', 'ER-2026-000701')
            ->assertJsonPath('pedido.reemplazado_por', null)
            ->assertJsonPath('pedido.expreso', 'Andreani')
            ->assertJsonPath('pedido.costo_envio', '100.00')
            ->assertJsonPath('pedido.subtotal_productos', '275.00')
            ->assertJsonPath('pedido.total_base', '375.00')
            ->assertJsonPath('pedido.pago_habilitado_en', null)
            ->assertJsonPath('pedido.envio.localidad', 'Río Cuarto')
            ->assertJsonPath('pedido.envio.observaciones', 'Timbre roto')
            ->assertJsonCount(2, 'pedido.items')
            ->assertJsonMissingPath('pedido.token_pago')
            ->assertJsonMissingPath('pedido.huella_checkout')
            ->json('pedido');

        $this->assertMatchesRegularExpression('/^ER-\d{4}-\d{6}$/', $nuevo['numero']);
        $this->assertNotSame('ER-2026-000701', $nuevo['numero']);

        $pedidoNuevo = Pedido::query()->where('numero', $nuevo['numero'])->sole();
        $this->assertSame($original->id, $pedidoNuevo->pedido_origen_id);
        $this->assertSame($cliente->id, $pedidoNuevo->usuario_id);
        $this->assertSame(sprintf('ER-%s-%06d', $pedidoNuevo->created_at->year, $pedidoNuevo->id), $pedidoNuevo->numero);
        $this->assertNull($pedidoNuevo->token_pago);
        $this->assertNull($pedidoNuevo->huella_checkout);
        $this->assertNotNull($pedidoNuevo->contactado_en);

        $snapshot = fn (Pedido $pedidoAComparar): array => $pedidoAComparar->items()
            ->orderBy('variante_producto_id')
            ->get()
            ->map(fn (ItemPedido $item): array => $item->only([
                'variante_producto_id', 'nombre_producto', 'color', 'etiqueta', 'sku', 'precio_unitario', 'cantidad', 'subtotal',
            ]))
            ->all();
        $this->assertSame($snapshot($original), $snapshot($pedidoNuevo));

        $original->refresh();
        $this->assertSame(EstadoPedido::Cancelado, $original->estado_pedido);
        $this->assertSame(EstadoPago::Cancelado, $original->estado_pago);
        $this->assertSame(MotivoCancelacion::Reemplazado, $original->motivo_cancelacion);
        $this->assertSame('El cliente quiere otro color', $original->detalle_cancelacion);
        $this->assertSame($administrador->id, $original->cancelado_por);
        $this->assertNotNull($original->cancelado_en);
        $this->assertNull($original->token_pago);

        $this->assertSame(EstadoIntentoPago::Cancelado, $pagoCreado->fresh()->estado);
        $this->assertSame(EstadoIntentoPago::Rechazado, $pagoRechazado->fresh()->estado);

        $this->getJson("/pago/{$token}")->assertNotFound();

        $this->actingAs($administrador)
            ->getJson($base)
            ->assertOk()
            ->assertJsonPath('pedido.estado_pedido', 'cancelado')
            ->assertJsonPath('pedido.reemplaza_a', null)
            ->assertJsonPath('pedido.reemplazado_por', $nuevo['numero'])
            ->assertJsonMissingPath('pedido.token_pago');
    }

    public function test_reemplazo_rechazado_sin_orden_de_pago_o_con_pago_pendiente_en_revision_o_pagado(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $sinOrden = Pedido::factory()->create(['estado_pago' => EstadoPago::Deshabilitado]);
        $conRevision = Pedido::factory()->create(['estado_pago' => EstadoPago::Habilitado]);
        Pago::factory()->for($conRevision, 'pedido')->create(['estado' => EstadoIntentoPago::RequiereRevision]);
        $conPagoEnCurso = Pedido::factory()->create(['estado_pago' => EstadoPago::Habilitado]);
        Pago::factory()->for($conPagoEnCurso, 'pedido')->create(['estado' => EstadoIntentoPago::Pendiente]);
        $pagoPendiente = Pedido::factory()->pagoPendiente()->create();
        $pagado = Pedido::factory()->pagado()->create();
        $cancelado = Pedido::factory()->cancelado()->create();
        $pedidosAntes = Pedido::query()->count();

        $this->actingAs($administrador)
            ->postJson("/administracion/pedidos/{$sinOrden->id}/reemplazar")
            ->assertUnprocessable()
            ->assertJsonPath('errors.pedido.0', 'El pedido todavía no tiene orden de pago. Editalo directamente.');

        foreach ([$conRevision, $conPagoEnCurso, $pagoPendiente, $pagado, $cancelado] as $pedido) {
            $this->actingAs($administrador)
                ->postJson("/administracion/pedidos/{$pedido->id}/reemplazar")
                ->assertUnprocessable()
                ->assertJsonValidationErrors('pedido');
        }

        $this->assertSame($pedidosAntes, Pedido::query()->count());
        $this->assertSame(EstadoPago::Habilitado, $conRevision->fresh()->estado_pago);
        $this->assertSame(EstadoPedido::PendienteCoordinacion, $conRevision->fresh()->estado_pedido);
        $this->assertNull($conRevision->fresh()->pedido_origen_id);
    }

    public function test_el_historial_del_cliente_muestra_el_reemplazo(): void
    {
        $this->configurarAjuste('10');
        $administrador = Usuario::factory()->administrador()->create();
        $cliente = Usuario::factory()->create();
        $kayak = $this->varianteDisponible('100.00', 'Kayak Río Azul', 'KAY-AZ-008');
        $original = Pedido::factory()->conTokenPago()->create([
            'usuario_id' => $cliente->id,
            'numero' => 'ER-2026-000801',
            'estado_pago' => EstadoPago::Habilitado,
            'estado_envio' => EstadoEnvio::Aceptado,
            'expreso' => 'Andreani',
            'costo_envio' => '100.00',
        ]);
        $this->lineaPedido($original, $kayak, '110.00', 1);
        $tokenOriginal = $original->token_pago;

        $numeroNuevo = $this->actingAs($administrador)
            ->postJson("/administracion/pedidos/{$original->id}/reemplazar")
            ->assertCreated()
            ->json('pedido.numero');

        $this->actingAs($cliente)
            ->getJson('/cuenta/pedidos')
            ->assertOk()
            ->assertJsonCount(2, 'pedidos.data')
            ->assertJsonPath('pedidos.data.0.numero', $numeroNuevo)
            ->assertJsonPath('pedidos.data.0.reemplaza_a', 'ER-2026-000801')
            ->assertJsonPath('pedidos.data.0.reemplazado_por', null)
            ->assertJsonPath('pedidos.data.1.numero', 'ER-2026-000801')
            ->assertJsonPath('pedidos.data.1.estado_pedido', 'cancelado')
            ->assertJsonPath('pedidos.data.1.reemplazado_por', $numeroNuevo)
            ->assertJsonPath('pedidos.data.1.reemplaza_a', null)
            ->assertJsonMissingPath('pedidos.data.0.id')
            ->assertDontSee($tokenOriginal);

        $this->actingAs($cliente)
            ->getJson("/cuenta/pedidos/{$numeroNuevo}")
            ->assertOk()
            ->assertJsonPath('pedido.reemplaza_a', 'ER-2026-000801');

        $this->actingAs($cliente)
            ->getJson('/cuenta/pedidos/ER-2026-000801')
            ->assertOk()
            ->assertJsonPath('pedido.reemplazado_por', $numeroNuevo)
            ->assertDontSee($tokenOriginal);
    }

    public function test_ediciones_y_reemplazo_quedan_auditados(): void
    {
        $this->configurarAjuste('10');
        $administrador = Usuario::factory()->administrador()->create();
        $cliente = Usuario::factory()->create();
        ReglaPrecioPago::factory()->activa()->create();
        $producto = $this->productoDisponible('Kayak Río Azul');
        $azul = $this->varianteDelProducto($producto, '100.00', 'Azul', 'KAY-AZ-009');
        $rojo = $this->varianteDelProducto($producto, '200.00', 'Rojo', 'KAY-RO-009');
        $remo = $this->varianteDisponible('50.00', 'Remo doble', 'REM-DO-009');
        $pedido = Pedido::factory()->create(['usuario_id' => $cliente->id, 'numero' => 'ER-2026-000901']);
        $lineaAzul = $this->lineaPedido($pedido, $azul, '110.00', 2);
        $base = "/administracion/pedidos/{$pedido->id}";

        $lineaRemo = $this->actingAs($administrador)
            ->postJson("{$base}/items", ['variante_producto_id' => $remo->id, 'cantidad' => 1])
            ->assertOk()
            ->json('pedido.items');
        $idRemo = collect($lineaRemo)->firstWhere('variante_producto_id', $remo->id)['id'];

        $this->actingAs($administrador)
            ->patchJson("{$base}/items/{$lineaAzul->id}", ['cantidad' => 3])
            ->assertOk();

        $this->actingAs($administrador)
            ->patchJson("{$base}/items/{$lineaAzul->id}", ['variante_producto_id' => $rojo->id])
            ->assertOk();

        $this->actingAs($administrador)
            ->deleteJson("{$base}/items/{$idRemo}")
            ->assertOk();

        $this->actingAs($administrador)
            ->patchJson("{$base}/envio/cotizacion", ['expreso' => 'Andreani', 'costo_envio' => '100'])
            ->assertOk();
        $this->actingAs($administrador)->postJson("{$base}/envio/aceptacion")->assertOk();
        $this->actingAs($administrador)->postJson("{$base}/pago/habilitar")->assertOk();

        $numeroNuevo = $this->actingAs($administrador)
            ->postJson("{$base}/reemplazar", ['nota' => 'Cambio de color'])
            ->assertCreated()
            ->json('pedido.numero');

        $pedidoNuevo = Pedido::query()->where('numero', $numeroNuevo)->sole();

        $agregado = RegistroAuditoria::query()->where('accion', 'pedido.item_agregado')->sole();
        $this->assertSame($administrador->id, $agregado->usuario_id);
        $this->assertSame('pedidos', $agregado->entidad);
        $this->assertSame($pedido->id, $agregado->entidad_id);
        $this->assertNull($agregado->datos_anteriores['linea']);
        $this->assertSame($remo->id, $agregado->datos_nuevos['linea']['variante_producto_id']);
        $this->assertSame('275.00', $agregado->datos_nuevos['subtotal_productos']);
        $this->assertSame('220.00', $agregado->datos_anteriores['subtotal_productos']);

        $cantidad = RegistroAuditoria::query()->where('accion', 'pedido.item_cantidad_cambiada')->sole();
        $this->assertSame(2, $cantidad->datos_anteriores['linea']['cantidad']);
        $this->assertSame(3, $cantidad->datos_nuevos['linea']['cantidad']);

        $variante = RegistroAuditoria::query()->where('accion', 'pedido.item_variante_cambiada')->sole();
        $this->assertSame($azul->id, $variante->datos_anteriores['linea']['variante_producto_id']);
        $this->assertSame($rojo->id, $variante->datos_nuevos['linea']['variante_producto_id']);
        $this->assertSame('220.00', $variante->datos_nuevos['linea']['precio_unitario']);

        $quitado = RegistroAuditoria::query()->where('accion', 'pedido.item_quitado')->sole();
        $this->assertSame($remo->id, $quitado->datos_anteriores['linea']['variante_producto_id']);
        $this->assertNull($quitado->datos_nuevos['linea']);

        $reemplazado = RegistroAuditoria::query()->where('accion', 'pedido.reemplazado')->sole();
        $this->assertSame($administrador->id, $reemplazado->usuario_id);
        $this->assertSame('pedidos', $reemplazado->entidad);
        $this->assertSame($pedido->id, $reemplazado->entidad_id);
        $this->assertSame($numeroNuevo, $reemplazado->datos_nuevos['reemplazado_por']);
        $this->assertSame('reemplazado', $reemplazado->datos_nuevos['motivo_cancelacion']);

        $creado = RegistroAuditoria::query()->where('accion', 'pedido.creado_por_reemplazo')->sole();
        $this->assertSame($pedidoNuevo->id, $creado->entidad_id);
        $this->assertSame('ER-2026-000901', $creado->datos_nuevos['reemplaza_a']);
    }

    public function test_cliente_no_puede_editar_ni_reemplazar_pedidos(): void
    {
        $this->configurarAjuste('10');
        $cliente = Usuario::factory()->create();
        $kayak = $this->varianteDisponible('100.00', 'Kayak Río Azul', 'KAY-AZ-010');
        $pedido = Pedido::factory()->create([
            'usuario_id' => $cliente->id,
            'estado_pago' => EstadoPago::Habilitado,
        ]);
        $linea = $this->lineaPedido($pedido, $kayak, '110.00', 1);
        $base = "/administracion/pedidos/{$pedido->id}";
        $cantidadDePedidos = Pedido::query()->count();

        $this->actingAs($cliente)
            ->postJson("{$base}/items", ['variante_producto_id' => $kayak->id, 'cantidad' => 1])
            ->assertForbidden();

        $this->actingAs($cliente)
            ->patchJson("{$base}/items/{$linea->id}", ['cantidad' => 5])
            ->assertForbidden();

        $this->actingAs($cliente)
            ->deleteJson("{$base}/items/{$linea->id}")
            ->assertForbidden();

        $this->actingAs($cliente)
            ->postJson("{$base}/reemplazar")
            ->assertForbidden();

        $this->assertDatabaseHas('items_pedido', ['id' => $linea->id, 'cantidad' => 1]);
        $this->assertSame($cantidadDePedidos, Pedido::query()->count());
        $this->assertSame(EstadoPago::Habilitado, $pedido->fresh()->estado_pago);
    }

    public function test_item_de_otro_pedido_responde_404(): void
    {
        $this->configurarAjuste('10');
        $administrador = Usuario::factory()->administrador()->create();
        $kayak = $this->varianteDisponible('100.00', 'Kayak Río Azul', 'KAY-AZ-011');
        $pedido = Pedido::factory()->create();
        $ajeno = Pedido::factory()->create();
        $lineaAjena = $this->lineaPedido($ajeno, $kayak, '110.00', 1);

        $this->actingAs($administrador)
            ->patchJson("/administracion/pedidos/{$pedido->id}/items/{$lineaAjena->id}", ['cantidad' => 2])
            ->assertNotFound();

        $this->actingAs($administrador)
            ->deleteJson("/administracion/pedidos/{$pedido->id}/items/{$lineaAjena->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('items_pedido', ['id' => $lineaAjena->id, 'pedido_id' => $ajeno->id, 'cantidad' => 1]);
    }

    public function test_actualizar_item_exige_cantidad_o_variante_pero_no_ambas(): void
    {
        $this->configurarAjuste('10');
        $administrador = Usuario::factory()->administrador()->create();
        $kayak = $this->varianteDisponible('100.00', 'Kayak Río Azul', 'KAY-AZ-012');
        $pedido = Pedido::factory()->create();
        $linea = $this->lineaPedido($pedido, $kayak, '110.00', 1);
        $url = "/administracion/pedidos/{$pedido->id}/items/{$linea->id}";

        $this->actingAs($administrador)
            ->patchJson($url, [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cantidad', 'variante_producto_id']);

        $this->actingAs($administrador)
            ->patchJson($url, ['cantidad' => 2, 'variante_producto_id' => $kayak->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cantidad', 'variante_producto_id']);

        $this->actingAs($administrador)
            ->patchJson($url, ['cantidad' => 100])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cantidad');

        $this->assertDatabaseHas('items_pedido', ['id' => $linea->id, 'cantidad' => 1]);
    }

    private function configurarAjuste(string $porcentaje): void
    {
        Configuracion::query()->updateOrCreate(
            ['clave' => 'ajuste_ecommerce_porcentaje'],
            ['valor' => $porcentaje],
        );
    }

    /** @param  array<string, mixed>  $cambios */
    private function productoDisponible(string $nombre, array $cambios = []): Producto
    {
        return Producto::factory()->create(array_merge(
            ['nombre' => $nombre, 'activo' => true, 'disponible' => true],
            $cambios,
        ));
    }

    /** @param  array<string, mixed>  $cambios */
    private function varianteDelProducto(Producto $producto, string $precioBase, string $color, string $sku, array $cambios = []): VarianteProducto
    {
        return VarianteProducto::factory()->create(array_merge([
            'producto_id' => $producto->id,
            'color' => $color,
            'etiqueta' => null,
            'sku' => $sku,
            'precio_base' => $precioBase,
            'activo' => true,
            'disponible' => true,
        ], $cambios));
    }

    private function varianteDisponible(string $precioBase, string $nombre, string $sku): VarianteProducto
    {
        return $this->varianteDelProducto($this->productoDisponible($nombre), $precioBase, 'Azul', $sku);
    }

    /**
     * Crea la línea con el snapshot que dejaría el checkout y recalcula subtotal y total del pedido.
     */
    private function lineaPedido(Pedido $pedido, VarianteProducto $variante, string $precioUnitario, int $cantidad): ItemPedido
    {
        $linea = ItemPedido::factory()->for($pedido, 'pedido')->create([
            'producto_id' => $variante->producto_id,
            'variante_producto_id' => $variante->id,
            'nombre_producto' => $variante->producto->nombre,
            'color' => $variante->color,
            'etiqueta' => $variante->etiqueta,
            'sku' => $variante->sku,
            'precio_unitario' => $precioUnitario,
            'cantidad' => $cantidad,
            'subtotal' => bcmul($precioUnitario, (string) $cantidad, 2),
        ]);

        $subtotal = $pedido->items()->get()->reduce(
            fn (string $acumulado, ItemPedido $item): string => bcadd($acumulado, (string) $item->subtotal, 2),
            '0.00',
        );

        $pedido->subtotal_productos = $subtotal;
        $pedido->total_base = $pedido->costo_envio === null ? null : bcadd($subtotal, (string) $pedido->costo_envio, 2);
        $pedido->save();

        return $linea;
    }

    /** @param  array<string, mixed>  $pedido */
    private function lineaDe(array $pedido, int $varianteId): array
    {
        return collect($pedido['items'])->firstWhere('variante_producto_id', $varianteId);
    }
}
