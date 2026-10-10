<?php

namespace Tests\Feature;

use App\Enums\EstadoContacto;
use App\Enums\EstadoEnvio;
use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Enums\Provincia;
use App\Enums\TipoMedioPago;
use App\Models\Configuracion;
use App\Models\ItemPedido;
use App\Models\Pedido;
use App\Models\RegistroAuditoria;
use App\Models\ReglaPrecioPago;
use App\Models\Usuario;
use App\Models\VarianteProducto;
use App\Servicios\Pedidos\WhatsAppEnlaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CoordinacionEnvioTest extends TestCase
{
    use RefreshDatabase;

    public function test_registrar_contacto_actualiza_estado_y_fecha_y_queda_auditado(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $pedido = Pedido::factory()->create();
        $url = "/administracion/pedidos/{$pedido->id}/contacto";

        $this->actingAs($administrador)
            ->patchJson($url, ['estado_contacto' => 'no_contestado'])
            ->assertOk()
            ->assertJsonPath('pedido.estado_contacto', 'no_contestado')
            ->assertJsonPath('pedido.contactado_en', null);

        $primeraFecha = $this->actingAs($administrador)
            ->patchJson($url, ['estado_contacto' => 'contactado'])
            ->assertOk()
            ->assertJsonPath('pedido.estado_contacto', 'contactado')
            ->json('pedido.contactado_en');

        $this->assertNotNull($primeraFecha);

        $this->actingAs($administrador)
            ->patchJson($url, ['estado_contacto' => 'respondio'])
            ->assertOk()
            ->assertJsonPath('pedido.estado_contacto', 'respondio')
            ->assertJsonPath('pedido.contactado_en', $primeraFecha);

        $this->assertDatabaseHas('pedidos', ['id' => $pedido->id, 'estado_contacto' => 'respondio']);

        $registros = RegistroAuditoria::query()
            ->where('accion', 'pedido.contacto_actualizado')
            ->orderBy('id')
            ->get();

        $this->assertCount(3, $registros);
        $this->assertSame($administrador->id, $registros[0]->usuario_id);
        $this->assertSame('pedidos', $registros[0]->entidad);
        $this->assertSame($pedido->id, $registros[0]->entidad_id);
        $this->assertSame(['estado_contacto' => 'no_contestado'], $registros[0]->datos_nuevos);
        $this->assertEqualsCanonicalizing(['estado_contacto' => 'contactado', 'contactado_en' => $primeraFecha], $registros[1]->datos_nuevos);
        $this->assertSame(['estado_contacto' => 'respondio'], $registros[2]->datos_nuevos);
    }

    public function test_cotizar_envio_calcula_total_base_y_recotizar_vuelve_a_cotizado(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $pedido = Pedido::factory()->create(['subtotal_productos' => '275.00']);
        $url = "/administracion/pedidos/{$pedido->id}";

        $this->actingAs($administrador)
            ->patchJson("{$url}/envio/cotizacion", ['expreso' => 'Andreani', 'costo_envio' => '1500.5'])
            ->assertOk()
            ->assertJsonPath('pedido.estado_envio', 'cotizado')
            ->assertJsonPath('pedido.expreso', 'Andreani')
            ->assertJsonPath('pedido.costo_envio', '1500.50')
            ->assertJsonPath('pedido.total_base', '1775.50');

        $this->actingAs($administrador)
            ->postJson("{$url}/envio/aceptacion")
            ->assertOk()
            ->assertJsonPath('pedido.estado_envio', 'aceptado');

        $this->actingAs($administrador)
            ->patchJson("{$url}/envio/cotizacion", ['expreso' => 'Via Cargo', 'costo_envio' => '2000'])
            ->assertOk()
            ->assertJsonPath('pedido.estado_envio', 'cotizado')
            ->assertJsonPath('pedido.expreso', 'Via Cargo')
            ->assertJsonPath('pedido.total_base', '2275.00');

        $this->assertDatabaseHas('pedidos', ['id' => $pedido->id, 'estado_envio' => 'cotizado', 'total_base' => '2275.00']);
        $this->assertSame(2, RegistroAuditoria::query()->where('accion', 'pedido.envio_cotizado')->count());
    }

    public function test_cotizacion_valida_expreso_y_costo(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $pedido = Pedido::factory()->create(['subtotal_productos' => '275.00']);
        $url = "/administracion/pedidos/{$pedido->id}/envio/cotizacion";

        foreach (['-1', '10.555', 'abc'] as $costoInvalido) {
            $this->actingAs($administrador)
                ->patchJson($url, ['expreso' => 'Andreani', 'costo_envio' => $costoInvalido])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('costo_envio');
        }

        $this->actingAs($administrador)
            ->patchJson($url, ['expreso' => str_repeat('x', 101), 'costo_envio' => '100'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('expreso');

        $this->actingAs($administrador)
            ->patchJson($url, ['costo_envio' => '100'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('expreso');

        $this->actingAs($administrador)
            ->patchJson($url, ['expreso' => 'A retirar', 'costo_envio' => '0'])
            ->assertOk()
            ->assertJsonPath('pedido.total_base', '275.00');
    }

    public function test_no_se_cotiza_con_pago_habilitado_ni_en_pedido_cancelado(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $pagoHabilitado = Pedido::factory()->create(['estado_pago' => EstadoPago::Habilitado]);
        $cancelado = Pedido::factory()->cancelado()->create();
        $confirmado = Pedido::factory()->pagado()->create();

        $this->actingAs($administrador)
            ->patchJson("/administracion/pedidos/{$pagoHabilitado->id}/envio/cotizacion", ['expreso' => 'Andreani', 'costo_envio' => '100'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pago');

        foreach ([$cancelado, $confirmado] as $pedido) {
            $this->actingAs($administrador)
                ->patchJson("/administracion/pedidos/{$pedido->id}/envio/cotizacion", ['expreso' => 'Andreani', 'costo_envio' => '100'])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('pedido');
        }

        $this->assertNull($pagoHabilitado->fresh()->costo_envio);
        $this->assertNull($cancelado->fresh()->costo_envio);
        $this->assertNull($confirmado->fresh()->costo_envio);
        $this->assertSame(0, RegistroAuditoria::query()->where('accion', 'pedido.envio_cotizado')->count());
    }

    public function test_aceptacion_requiere_envio_cotizado(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $pedido = Pedido::factory()->create();
        $url = "/administracion/pedidos/{$pedido->id}";

        $this->actingAs($administrador)
            ->postJson("{$url}/envio/aceptacion")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pedido');

        $this->assertSame(EstadoEnvio::PendienteCoordinacion, $pedido->fresh()->estado_envio);

        $this->actingAs($administrador)
            ->patchJson("{$url}/envio/cotizacion", ['expreso' => 'Andreani', 'costo_envio' => '100'])
            ->assertOk();

        $this->actingAs($administrador)
            ->postJson("{$url}/envio/aceptacion")
            ->assertOk()
            ->assertJsonPath('pedido.estado_envio', 'aceptado');

        $this->actingAs($administrador)
            ->postJson("{$url}/envio/aceptacion")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pedido');

        $this->assertSame(1, RegistroAuditoria::query()->where('accion', 'pedido.envio_aceptado')->count());
    }

    public function test_habilitar_pago_requiere_envio_aceptado_y_reglas_de_pago_activas(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $pedido = Pedido::factory()->create(['subtotal_productos' => '275.00']);
        $url = "/administracion/pedidos/{$pedido->id}";

        $this->actingAs($administrador)
            ->postJson("{$url}/pago/habilitar")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pedido');

        $this->actingAs($administrador)
            ->patchJson("{$url}/envio/cotizacion", ['expreso' => 'Andreani', 'costo_envio' => '100'])
            ->assertOk();

        $this->actingAs($administrador)
            ->postJson("{$url}/pago/habilitar")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pedido');

        $this->actingAs($administrador)
            ->postJson("{$url}/envio/aceptacion")
            ->assertOk();

        $this->actingAs($administrador)
            ->postJson("{$url}/pago/habilitar")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pedido');

        ItemPedido::factory()->for($pedido, 'pedido')->create([
            'precio_unitario' => '275.00',
            'cantidad' => 1,
            'subtotal' => '275.00',
        ]);

        ReglaPrecioPago::factory()->create(['activo' => false]);

        $this->actingAs($administrador)
            ->postJson("{$url}/pago/habilitar")
            ->assertUnprocessable()
            ->assertJsonPath('errors.pago.0', 'No hay modalidades de pago activas. Configurá las reglas de pago antes de habilitar el pago.');

        ReglaPrecioPago::factory()->activa()->create(['tipo_medio_pago' => TipoMedioPago::Debito]);

        $this->actingAs($administrador)
            ->postJson("{$url}/pago/habilitar")
            ->assertOk()
            ->assertJsonPath('pedido.estado_pago', 'habilitado');
    }

    public function test_habilitar_pago_genera_token_y_bloquea_cotizar_y_recalcular(): void
    {
        $this->configurarAjuste('10');
        $administrador = Usuario::factory()->administrador()->create();
        $pedido = $this->crearPedidoConItems();
        ReglaPrecioPago::factory()->activa()->create();
        $url = "/administracion/pedidos/{$pedido->id}";

        $this->actingAs($administrador)
            ->patchJson("{$url}/envio/cotizacion", ['expreso' => 'Andreani', 'costo_envio' => '1500.50'])
            ->assertOk();
        $this->actingAs($administrador)->postJson("{$url}/envio/aceptacion")->assertOk();

        $this->actingAs($administrador)
            ->postJson("{$url}/pago/habilitar")
            ->assertOk()
            ->assertJsonPath('pedido.estado_pago', 'habilitado')
            ->assertJsonPath('pedido.total_base', '1720.50')
            ->assertJsonMissingPath('pedido.token_pago');

        $pedido->refresh();
        $this->assertMatchesRegularExpression('/^[0-9a-f]{48}$/', $pedido->token_pago);
        $this->assertNotNull($pedido->pago_habilitado_en);

        $this->actingAs($administrador)
            ->patchJson("{$url}/envio/cotizacion", ['expreso' => 'Via Cargo', 'costo_envio' => '900'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pago');

        $this->actingAs($administrador)
            ->postJson("{$url}/importes/recalcular")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pago');

        $this->assertSame('1500.50', $pedido->fresh()->costo_envio);

        $registro = RegistroAuditoria::query()->where('accion', 'pedido.pago_habilitado')->sole();
        $this->assertSame(['estado_pago' => 'habilitado', 'pago_habilitado_en' => $pedido->pago_habilitado_en->toIso8601String()], $registro->datos_nuevos);
        $this->assertArrayNotHasKey('token_pago', $registro->datos_nuevos);
    }

    public function test_recalcular_importes_usa_los_precios_actuales(): void
    {
        $this->configurarAjuste('10');
        $administrador = Usuario::factory()->administrador()->create();
        $variante = VarianteProducto::factory()->create(['precio_base' => '100.00']);
        $pedido = Pedido::factory()->create([
            'subtotal_productos' => '220.00',
            'costo_envio' => '150.00',
            'total_base' => '370.00',
            'estado_envio' => EstadoEnvio::Cotizado,
        ]);
        ItemPedido::factory()->for($pedido, 'pedido')->create([
            'variante_producto_id' => $variante->id,
            'producto_id' => $variante->producto_id,
            'precio_unitario' => '110.00',
            'cantidad' => 2,
            'subtotal' => '220.00',
        ]);
        $url = "/administracion/pedidos/{$pedido->id}/importes/recalcular";

        $variante->update(['precio_base' => '200.00']);

        $this->actingAs($administrador)
            ->postJson($url)
            ->assertOk()
            ->assertJsonPath('pedido.items.0.precio_unitario', '220.00')
            ->assertJsonPath('pedido.items.0.subtotal', '440.00')
            ->assertJsonPath('pedido.subtotal_productos', '440.00')
            ->assertJsonPath('pedido.total_base', '590.00')
            ->assertJsonPath('pedido.estado_envio', 'cotizado');

        $this->assertDatabaseHas('items_pedido', ['pedido_id' => $pedido->id, 'precio_unitario' => '220.00', 'subtotal' => '440.00']);

        $registro = RegistroAuditoria::query()->where('accion', 'pedido.importes_recalculados')->sole();
        $this->assertEqualsCanonicalizing(
            ['subtotal_productos' => '220.00', 'total_base' => '370.00'],
            $registro->datos_anteriores,
        );
        $this->assertEqualsCanonicalizing(
            ['subtotal_productos' => '440.00', 'total_base' => '590.00'],
            $registro->datos_nuevos,
        );

        Configuracion::query()->where('clave', 'ajuste_ecommerce_porcentaje')->delete();

        $this->actingAs($administrador)
            ->postJson($url)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pedido');
    }

    public function test_preparado_despachado_entregado_solo_despues_del_pago_y_en_orden(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $sinPagar = Pedido::factory()->create([
            'estado_envio' => EstadoEnvio::Aceptado,
            'estado_pago' => EstadoPago::Habilitado,
        ]);
        $pedido = Pedido::factory()->pagado()->create(['estado_envio' => EstadoEnvio::Aceptado]);

        $this->actingAs($administrador)
            ->patchJson("/administracion/pedidos/{$sinPagar->id}/envio/estado", ['estado_envio' => 'preparado'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pago');

        $url = "/administracion/pedidos/{$pedido->id}/envio/estado";

        foreach (['aceptado', 'cotizado', 'pendiente_coordinacion', 'inventado'] as $estadoInvalido) {
            $this->actingAs($administrador)
                ->patchJson($url, ['estado_envio' => $estadoInvalido])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('estado_envio');
        }

        $this->actingAs($administrador)
            ->patchJson($url, ['estado_envio' => 'despachado'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('estado_envio');

        $this->actingAs($administrador)
            ->patchJson($url, ['estado_envio' => 'preparado'])
            ->assertOk()
            ->assertJsonPath('pedido.estado_envio', 'preparado')
            ->assertJsonPath('pedido.estado_pago', 'pagado');

        $this->actingAs($administrador)
            ->patchJson($url, ['estado_envio' => 'preparado'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('estado_envio');

        $this->actingAs($administrador)
            ->patchJson($url, ['estado_envio' => 'entregado'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('estado_envio');

        $this->actingAs($administrador)
            ->patchJson($url, ['estado_envio' => 'despachado'])
            ->assertOk()
            ->assertJsonPath('pedido.estado_envio', 'despachado');

        $this->actingAs($administrador)
            ->patchJson($url, ['estado_envio' => 'entregado'])
            ->assertOk()
            ->assertJsonPath('pedido.estado_envio', 'entregado');

        $this->actingAs($administrador)
            ->patchJson($url, ['estado_envio' => 'despachado'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('estado_envio');

        $pedido->refresh();
        $this->assertNotNull($pedido->preparado_en);
        $this->assertNotNull($pedido->despachado_en);
        $this->assertNotNull($pedido->entregado_en);
        $this->assertSame(3, RegistroAuditoria::query()->where('accion', 'pedido.envio_avanzado')->count());
    }

    public function test_datos_de_envio_se_corrigen_despues_del_pago_y_quedan_auditados(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $pedido = Pedido::factory()->pagado()->create([
            'provincia_envio' => Provincia::Cordoba,
            'localidad_envio' => 'Río Cuarto',
            'codigo_postal_envio' => '5800',
            'direccion_envio' => 'Av. Italia',
            'numero_envio' => '123',
            'direccion_adicional' => 'Piso 2',
            'observaciones_envio' => 'Timbre roto',
        ]);
        $url = "/administracion/pedidos/{$pedido->id}/envio/datos";

        $this->actingAs($administrador)
            ->patchJson($url, [
                'provincia' => 'cordoba',
                'localidad' => 'Villa María',
                'codigo_postal' => 'b5900 abc',
                'direccion' => 'Av. Italia',
                'numero' => '123',
                'direccion_adicional' => null,
                'observaciones' => 'Llamar antes',
            ])
            ->assertOk()
            ->assertJsonPath('pedido.envio.localidad', 'Villa María')
            ->assertJsonPath('pedido.envio.codigo_postal', 'B5900ABC')
            ->assertJsonPath('pedido.envio.direccion_adicional', null)
            ->assertJsonPath('pedido.envio.observaciones', 'Llamar antes')
            ->assertJsonPath('pedido.estado_pago', 'pagado');

        $this->assertDatabaseHas('pedidos', [
            'id' => $pedido->id,
            'localidad_envio' => 'Villa María',
            'codigo_postal_envio' => 'B5900ABC',
            'observaciones_envio' => 'Llamar antes',
        ]);

        $registro = RegistroAuditoria::query()->where('accion', 'pedido.datos_envio_actualizados')->sole();
        $this->assertSame($administrador->id, $registro->usuario_id);
        $this->assertEqualsCanonicalizing([
            'localidad_envio' => 'Río Cuarto',
            'codigo_postal_envio' => '5800',
            'direccion_adicional' => 'Piso 2',
            'observaciones_envio' => 'Timbre roto',
        ], $registro->datos_anteriores);
        $this->assertEqualsCanonicalizing([
            'localidad_envio' => 'Villa María',
            'codigo_postal_envio' => 'B5900ABC',
            'direccion_adicional' => null,
            'observaciones_envio' => 'Llamar antes',
        ], $registro->datos_nuevos);

        $this->actingAs($administrador)
            ->patchJson("/administracion/pedidos/{$pedido->id}/envio/datos", ['provincia' => 'cordoba'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['localidad', 'codigo_postal', 'direccion', 'numero']);

        $cancelado = Pedido::factory()->cancelado()->create();

        $this->actingAs($administrador)
            ->patchJson("/administracion/pedidos/{$cancelado->id}/envio/datos", [
                'provincia' => 'cordoba',
                'localidad' => 'Río Cuarto',
                'codigo_postal' => '5800',
                'direccion' => 'Av. Italia',
                'numero' => '123',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pedido');
    }

    #[DataProvider('telefonosArgentinos')]
    public function test_normaliza_telefonos_argentinos_para_wa_me(string $telefono, ?string $esperado): void
    {
        $this->assertSame($esperado, (new WhatsAppEnlaceService)->normalizarTelefono($telefono));
    }

    public function test_enlaces_de_whatsapp_para_coordinar_y_para_pagar(): void
    {
        $this->configurarAjuste('10');
        $administrador = Usuario::factory()->administrador()->create();
        $token = bin2hex(random_bytes(24));
        $pedido = Pedido::factory()->create([
            'nombre_cliente' => 'Ana',
            'telefono_cliente' => '+54 9 11 3909-0296',
            'localidad_envio' => 'Río Cuarto',
            'provincia_envio' => Provincia::Cordoba,
            'subtotal_productos' => '275000.00',
            'costo_envio' => '1500.50',
            'total_base' => '276500.50',
            'estado_envio' => EstadoEnvio::Aceptado,
        ]);
        $base = "/administracion/pedidos/{$pedido->id}";

        $coordinar = $this->actingAs($administrador)
            ->getJson("{$base}/whatsapp/coordinar-envio")
            ->assertOk()
            ->assertJsonPath('whatsapp.telefono', '+54 9 11 3909-0296')
            ->assertJsonPath('whatsapp.telefono_normalizado', '5491139090296')
            ->json('whatsapp');

        $this->assertStringStartsWith('https://wa.me/5491139090296?text=', $coordinar['url']);
        $this->assertSame('https://wa.me/5491139090296?text='.rawurlencode($coordinar['mensaje']), $coordinar['url']);
        $this->assertStringContainsString("Hola Ana, te escribimos de El Refugio por tu pedido {$pedido->numero}.", $coordinar['mensaje']);
        $this->assertStringContainsString('Queremos coordinar el envío a Río Cuarto, Córdoba.', $coordinar['mensaje']);

        $this->actingAs($administrador)
            ->getJson("{$base}/whatsapp/link-pago")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pago');

        $this->actingAs($administrador)
            ->getJson("{$base}/whatsapp/otro-tipo")
            ->assertNotFound();

        $pedido->forceFill(['estado_pago' => EstadoPago::Habilitado, 'token_pago' => $token, 'pago_habilitado_en' => now()])->save();

        $pago = $this->actingAs($administrador)
            ->getJson("{$base}/whatsapp/link-pago")
            ->assertOk()
            ->json('whatsapp');

        $this->assertStringContainsString("Hola Ana, ya podés pagar tu pedido {$pedido->numero}.", $pago['mensaje']);
        $this->assertStringContainsString('Total con envío: $276.500,50.', $pago['mensaje']);
        $this->assertStringContainsString('Pagá desde este link: '.url('/pago/'.$token), $pago['mensaje']);
        $this->assertSame('https://wa.me/5491139090296?text='.rawurlencode($pago['mensaje']), $pago['url']);

        $this->actingAs($administrador)
            ->getJson($base)
            ->assertOk()
            ->assertJsonPath('pedido.whatsapp.coordinar_envio.telefono_normalizado', '5491139090296')
            ->assertJsonPath('pedido.whatsapp.link_pago.telefono_normalizado', '5491139090296')
            ->assertJsonMissingPath('pedido.token_pago');

        $pedido->update(['telefono_cliente' => '123']);

        $this->actingAs($administrador)
            ->getJson("{$base}/whatsapp/coordinar-envio")
            ->assertOk()
            ->assertJsonPath('whatsapp.url', null)
            ->assertJsonPath('whatsapp.telefono', '123')
            ->assertJsonPath('whatsapp.telefono_normalizado', null);

        $cancelado = Pedido::factory()->cancelado()->create();

        $this->actingAs($administrador)
            ->getJson("/administracion/pedidos/{$cancelado->id}/whatsapp/coordinar-envio")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pedido');

        $this->actingAs($administrador)
            ->getJson("/administracion/pedidos/{$cancelado->id}")
            ->assertOk()
            ->assertJsonPath('pedido.whatsapp.coordinar_envio', null)
            ->assertJsonPath('pedido.whatsapp.link_pago', null);
    }

    public function test_cliente_no_puede_usar_acciones_de_coordinacion(): void
    {
        $cliente = Usuario::factory()->create();
        $pedido = Pedido::factory()->create();
        $base = "/administracion/pedidos/{$pedido->id}";

        $this->actingAs($cliente)->patchJson("{$base}/contacto", ['estado_contacto' => 'contactado'])->assertForbidden();
        $this->actingAs($cliente)->patchJson("{$base}/envio/cotizacion", ['expreso' => 'Andreani', 'costo_envio' => '100'])->assertForbidden();
        $this->actingAs($cliente)->postJson("{$base}/envio/aceptacion")->assertForbidden();
        $this->actingAs($cliente)->postJson("{$base}/pago/habilitar")->assertForbidden();
        $this->actingAs($cliente)->postJson("{$base}/importes/recalcular")->assertForbidden();
        $this->actingAs($cliente)->patchJson("{$base}/envio/estado", ['estado_envio' => 'preparado'])->assertForbidden();
        $this->actingAs($cliente)->patchJson("{$base}/envio/datos", ['provincia' => 'cordoba'])->assertForbidden();
        $this->actingAs($cliente)->getJson("{$base}/whatsapp/coordinar-envio")->assertForbidden();

        $pedido->refresh();
        $this->assertSame(EstadoContacto::Pendiente, $pedido->estado_contacto);
        $this->assertSame(EstadoEnvio::PendienteCoordinacion, $pedido->estado_envio);
        $this->assertSame(EstadoPago::Deshabilitado, $pedido->estado_pago);
        $this->assertSame(0, RegistroAuditoria::query()->where('usuario_id', $cliente->id)->count());
    }

    public function test_pedido_cancelado_rechaza_las_acciones_de_coordinacion(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $cancelado = Pedido::factory()->cancelado()->create();
        $base = "/administracion/pedidos/{$cancelado->id}";

        $this->actingAs($administrador)->patchJson("{$base}/contacto", ['estado_contacto' => 'contactado'])->assertUnprocessable()->assertJsonValidationErrors('pedido');
        $this->actingAs($administrador)->patchJson("{$base}/envio/cotizacion", ['expreso' => 'Andreani', 'costo_envio' => '100'])->assertUnprocessable()->assertJsonValidationErrors('pedido');
        $this->actingAs($administrador)->postJson("{$base}/envio/aceptacion")->assertUnprocessable()->assertJsonValidationErrors('pedido');
        $this->actingAs($administrador)->postJson("{$base}/pago/habilitar")->assertUnprocessable()->assertJsonValidationErrors('pedido');
        $this->actingAs($administrador)->postJson("{$base}/importes/recalcular")->assertUnprocessable()->assertJsonValidationErrors('pedido');
        $this->actingAs($administrador)->patchJson("{$base}/envio/estado", ['estado_envio' => 'preparado'])->assertUnprocessable()->assertJsonValidationErrors('pedido');
        $this->actingAs($administrador)->patchJson("{$base}/envio/datos", [
            'provincia' => 'cordoba',
            'localidad' => 'Río Cuarto',
            'codigo_postal' => '5800',
            'direccion' => 'Av. Italia',
            'numero' => '123',
        ])->assertUnprocessable()->assertJsonValidationErrors('pedido');

        $this->assertSame(EstadoPedido::Cancelado, $cancelado->fresh()->estado_pedido);
        $this->assertSame(EstadoContacto::Pendiente, $cancelado->fresh()->estado_contacto);
    }

    /** @return array<string, array{string, ?string}> */
    public static function telefonosArgentinos(): array
    {
        return [
            'celular con 0 y 15 tras codigo de area de 2 digitos' => ['011 15 1234-5678', '5491112345678'],
            'codigo de area de 4 digitos con 15' => ['2284 15 123456', '5492284123456'],
            'sin 0 ni 15' => ['11 3909 0296', '5491139090296'],
            'internacional con 9' => ['+54 9 11 3909-0296', '5491139090296'],
            'internacional sin 9' => ['+54 11 3909 0296', '5491139090296'],
            'codigo de area de 4 digitos con 0 y 15' => ['0341 15 555-1234', '5493415551234'],
            'codigo de area de 4 digitos sin 15' => ['2284-123456', '5492284123456'],
            'demasiado corto' => ['123', null],
            'sin digitos' => ['abc', null],
        ];
    }

    private function configurarAjuste(string $porcentaje): void
    {
        Configuracion::query()->updateOrCreate(
            ['clave' => 'ajuste_ecommerce_porcentaje'],
            ['valor' => $porcentaje],
        );
    }

    private function crearPedidoConItems(): Pedido
    {
        $variante = VarianteProducto::factory()->create(['precio_base' => '100.00']);
        $pedido = Pedido::factory()->create(['subtotal_productos' => '220.00']);

        ItemPedido::factory()->for($pedido, 'pedido')->create([
            'variante_producto_id' => $variante->id,
            'producto_id' => $variante->producto_id,
            'precio_unitario' => '110.00',
            'cantidad' => 2,
            'subtotal' => '220.00',
        ]);

        return $pedido;
    }
}
