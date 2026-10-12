<?php

namespace Tests\Feature;

use App\Enums\EstadoEnvio;
use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Enums\MotivoCancelacion;
use App\Enums\Provincia;
use App\Http\Requests\Concerns\ReglasDatosEnvio;
use App\Models\Configuracion;
use App\Models\ItemPedido;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Usuario;
use App\Models\VarianteProducto;
use App\View\EstadoVisiblePedido;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class VistasCheckoutPedidosTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_checkout_muestra_los_datos_del_cliente_y_precarga_el_envio_del_ultimo_pedido(): void
    {
        $this->configurarAjuste();
        $usuario = $this->usuario(['nombre' => 'Ana', 'apellido' => 'Pérez', 'dni' => '30111222', 'email' => 'ana@example.com', 'telefono' => '2215551234']);
        Pedido::factory()->for($usuario, 'usuario')->create([
            'provincia_envio' => Provincia::Cordoba,
            'localidad_envio' => 'Río Cuarto',
            'codigo_postal_envio' => '5800',
            'direccion_envio' => 'Av. Italia',
            'numero_envio' => '123',
            'direccion_adicional' => 'Piso 2 Depto B',
            'observaciones_envio' => 'Timbre roto',
            'created_at' => now()->subDay(),
        ]);
        $this->agregarAlCarrito($this->crearVariante('100.00', 'Sillón Gran California', 'Blanco'), 2, $usuario);

        $this->actingAs($usuario)
            ->get(route('checkout.resumen'))
            ->assertOk()
            ->assertViewIs('tienda.checkout')
            ->assertSee('<title>Confirmá tu pedido · El Refugio</title>', false)
            ->assertSee('Ana Pérez')
            ->assertSee('30111222')
            ->assertSee('ana@example.com')
            ->assertSee('2215551234')
            ->assertSee('href="'.route('cuenta.mostrar').'"', false)
            ->assertSee('<option value="cordoba" selected>Córdoba</option>', false)
            ->assertSee('value="Río Cuarto"', false)
            ->assertSee('value="5800"', false)
            ->assertSee('value="Av. Italia"', false)
            ->assertSee('value="123"', false)
            ->assertSee('value="Piso 2 Depto B"', false)
            ->assertSee('Timbre roto')
            ->assertSee('Sillón Gran California')
            ->assertSee('$200')
            ->assertSee('A coordinar por WhatsApp')
            ->assertSee('¿Qué pasa después?')
            ->assertSee('Todavía no pagás nada.')
            ->assertSee('autocomplete="postal-code"', false)
            ->assertSee('autocomplete="address-level1"', false);
    }

    public function test_sin_pedidos_previos_el_formulario_de_envio_aparece_vacio(): void
    {
        $this->configurarAjuste();
        $usuario = $this->usuario();
        $this->agregarAlCarrito($this->crearVariante('100.00'), 1, $usuario);

        $respuesta = $this->actingAs($usuario)
            ->get(route('checkout.resumen'))
            ->assertOk()
            ->assertSee('<option value="" selected>Elegí una provincia</option>', false)
            ->assertDontSee('value="Río Cuarto"', false);

        $this->assertSame('', $this->nodo($respuesta, '//input[@name="localidad"]')->getAttribute('value'));
        $this->assertSame('', $this->nodo($respuesta, '//input[@name="codigo_postal"]')->getAttribute('value'));
    }

    public function test_los_datos_escritos_antes_ganan_sobre_la_precarga_y_el_error_queda_marcado(): void
    {
        $this->configurarAjuste();
        $usuario = $this->usuario();
        Pedido::factory()->for($usuario, 'usuario')->create(['localidad_envio' => 'Río Cuarto', 'codigo_postal_envio' => '5800']);
        $variante = $this->crearVariante('100.00');
        $this->agregarAlCarrito($variante, 1, $usuario);
        $huella = $this->actingAs($usuario)->getJson(route('checkout.resumen'))->json('checkout.huella');

        $this->actingAs($usuario)
            ->from(route('checkout.resumen'))
            ->post(route('checkout.confirmar'), array_merge($this->datosEnvioValidos($huella), [
                'localidad' => 'Villa Mercedes',
                'codigo_postal' => 'ABC',
            ]))
            ->assertRedirect(route('checkout.resumen'))
            ->assertSessionHasErrors('codigo_postal');

        $this->continuarSesion();

        $respuesta = $this->actingAs($usuario)
            ->get(route('checkout.resumen'))
            ->assertOk()
            ->assertSee('Revisá los datos marcados abajo.')
            ->assertSee('value="Villa Mercedes"', false)
            ->assertDontSee('value="Río Cuarto"', false);

        $this->assertSame('true', $this->nodo($respuesta, '//input[@name="codigo_postal"]')->getAttribute('aria-invalid'));
        $this->assertDatabaseCount('pedidos', 1);
    }

    public function test_con_el_email_sin_verificar_se_muestra_el_aviso_y_el_boton_de_confirmar_queda_deshabilitado(): void
    {
        $this->configurarAjuste();
        $usuario = $this->usuario(['email_verified_at' => null]);
        $this->agregarAlCarrito($this->crearVariante('100.00'), 1, $usuario);

        $respuesta = $this->actingAs($usuario)
            ->get(route('checkout.resumen'))
            ->assertOk()
            ->assertSee('Falta confirmar tu email')
            ->assertSee('action="'.route('verification.send').'"', false)
            ->assertSee('Mandarme otro email para confirmar');

        $this->assertTrue($this->nodo($respuesta, '//button[normalize-space()="Confirmar pedido"]')->hasAttribute('disabled'));
    }

    public function test_con_el_email_verificado_el_boton_de_confirmar_esta_habilitado_y_sin_aviso(): void
    {
        $this->configurarAjuste();
        $usuario = $this->usuario();
        $this->agregarAlCarrito($this->crearVariante('100.00'), 1, $usuario);

        $respuesta = $this->actingAs($usuario)
            ->get(route('checkout.resumen'))
            ->assertOk()
            ->assertDontSee('Falta confirmar tu email');

        $this->assertFalse($this->nodo($respuesta, '//button[normalize-space()="Confirmar pedido"]')->hasAttribute('disabled'));
    }

    public function test_quien_no_tiene_sesion_va_al_ingreso_y_vuelve_al_checkout_despues(): void
    {
        $this->configurarAjuste();
        $usuario = $this->usuario(['email' => 'ana@example.com']);

        $this->get(route('checkout.resumen'))->assertRedirect(route('login'));

        $this->post(route('login'), ['email' => 'ana@example.com', 'password' => 'clave-segura'])
            ->assertRedirect(route('checkout.resumen'));

        $this->assertAuthenticatedAs($usuario);
    }

    public function test_con_el_carrito_vacio_el_checkout_vuelve_al_carrito_con_un_aviso(): void
    {
        $this->configurarAjuste();
        $usuario = $this->usuario();

        $this->actingAs($usuario)
            ->get(route('checkout.resumen'))
            ->assertRedirect(route('carrito.mostrar'))
            ->assertSessionHas('error', 'Tu carrito está vacío. Agregá un mueble para continuar.');
    }

    public function test_con_un_producto_no_disponible_el_checkout_vuelve_al_carrito_con_un_aviso(): void
    {
        $this->configurarAjuste();
        $usuario = $this->usuario();
        $variante = $this->crearVariante('100.00');
        $this->agregarAlCarrito($variante, 1, $usuario);
        $variante->forceFill(['disponible' => false])->save();

        $this->actingAs($usuario)
            ->get(route('checkout.resumen'))
            ->assertRedirect(route('carrito.mostrar'))
            ->assertSessionHas('error', 'Hay productos que ya no están disponibles. Quitalos para continuar.');
    }

    public function test_confirmar_con_el_formulario_crea_el_pedido_y_redirige_a_pedido_recibido(): void
    {
        $this->configurarAjuste();
        $usuario = $this->usuario(['nombre' => 'María', 'email' => 'maria@example.com', 'telefono' => '3415551234']);
        $this->agregarAlCarrito($this->crearVariante('100.00'), 2, $usuario);
        $huella = $this->actingAs($usuario)->getJson(route('checkout.resumen'))->json('checkout.huella');

        $this->actingAs($usuario)
            ->from(route('checkout.resumen'))
            ->post(route('checkout.confirmar'), $this->datosEnvioValidos($huella))
            ->assertRedirect();

        $pedido = Pedido::query()->sole();
        $this->assertSame(EstadoPago::Deshabilitado, $pedido->estado_pago);
        $this->assertSame(EstadoPedido::PendienteCoordinacion, $pedido->estado_pedido);

        $this->actingAs($usuario)
            ->get(route('checkout.recibido', $pedido->numero))
            ->assertOk()
            ->assertViewIs('tienda.pedido-recibido')
            ->assertSee('<title>Pedido recibido · El Refugio</title>', false)
            ->assertSee('¡Pedido recibido!')
            ->assertSee($pedido->numero)
            ->assertSee('maria@example.com')
            ->assertSee('3415551234')
            ->assertSee('Ahora sigue esto')
            ->assertSee('Ver mi pedido')
            ->assertSee('Volver a la tienda')
            ->assertSee('href="'.route('cuenta.pedidos.mostrar', $pedido->numero).'"', false);

        $this->continuarSesion();

        $this->actingAs($usuario)
            ->get(route('carrito.mostrar'))
            ->assertSee('Tu carrito está vacío.');
    }

    public function test_pedido_recibido_solo_lo_ve_su_dueno(): void
    {
        $this->configurarAjuste();
        $dueno = $this->usuario();
        $otro = $this->usuario();
        $pedido = Pedido::factory()->for($dueno, 'usuario')->create();

        $this->get(route('checkout.recibido', $pedido->numero))->assertRedirect(route('login'));
        $this->actingAs($otro)->get(route('checkout.recibido', $pedido->numero))->assertNotFound();
        $this->actingAs($dueno)->get(route('checkout.recibido', $pedido->numero))->assertOk();
    }

    public function test_si_el_precio_cambio_el_formulario_vuelve_al_checkout_con_el_aviso_y_los_datos(): void
    {
        $this->configurarAjuste();
        $usuario = $this->usuario();
        $variante = $this->crearVariante('100.00');
        $this->agregarAlCarrito($variante, 1, $usuario);
        $huella = $this->actingAs($usuario)->getJson(route('checkout.resumen'))->json('checkout.huella');
        $variante->update(['precio_base' => '120.00']);
        $mensaje = 'Los precios o la disponibilidad cambiaron. Revisá el resumen antes de confirmar.';

        $this->actingAs($usuario)
            ->from(route('checkout.resumen'))
            ->post(route('checkout.confirmar'), $this->datosEnvioValidos($huella))
            ->assertRedirect(route('checkout.resumen'))
            ->assertSessionHasErrors(['carrito' => $mensaje])
            ->assertSessionHasInput('localidad', 'Río Cuarto');

        $this->continuarSesion();

        $this->actingAs($usuario)
            ->get(route('checkout.resumen'))
            ->assertOk()
            ->assertSee('role="alert"', false)
            ->assertSee($mensaje)
            ->assertDontSee('Revisá los datos marcados abajo.')
            ->assertSee('value="Río Cuarto"', false);

        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_mis_pedidos_muestra_el_estado_amigable_y_pagar_solo_cuando_el_pago_esta_habilitado(): void
    {
        $usuario = $this->usuario();
        $habilitado = Pedido::factory()->conTokenPago()->create([
            'usuario_id' => $usuario->id,
            'numero' => 'ER-2026-000301',
            'estado_pago' => EstadoPago::Habilitado,
            'estado_envio' => EstadoEnvio::Aceptado,
            'subtotal_productos' => '200.00',
            'costo_envio' => '50.00',
            'total_base' => '250.00',
            'created_at' => '2026-10-11 01:30:00',
        ]);
        $pagado = Pedido::factory()->pagado()->conTokenPago()->create([
            'usuario_id' => $usuario->id,
            'numero' => 'ER-2026-000302',
            'subtotal_productos' => '100.00',
        ]);
        $cancelado = Pedido::factory()->cancelado()->create([
            'usuario_id' => $usuario->id,
            'numero' => 'ER-2026-000303',
        ]);
        $despachado = Pedido::factory()->create([
            'usuario_id' => $usuario->id,
            'numero' => 'ER-2026-000304',
            'estado_pago' => EstadoPago::Pagado,
            'estado_envio' => EstadoEnvio::Despachado,
            'expreso' => 'Andreani',
        ]);
        $recibido = Pedido::factory()->create([
            'usuario_id' => $usuario->id,
            'numero' => 'ER-2026-000305',
            'subtotal_productos' => '80.00',
        ]);
        $ajeno = Pedido::factory()->conTokenPago()->create([
            'numero' => 'ER-2026-000306',
            'estado_pago' => EstadoPago::Habilitado,
        ]);

        $respuesta = $this->actingAs($usuario)
            ->get(route('cuenta.pedidos.index'))
            ->assertOk()
            ->assertViewIs('tienda.cuenta-pedidos')
            ->assertSee('<title>Mis pedidos · El Refugio</title>', false)
            ->assertSee($habilitado->numero)
            ->assertSee($pagado->numero)
            ->assertSee($cancelado->numero)
            ->assertSee($despachado->numero)
            ->assertSee($recibido->numero)
            ->assertSee('Listo para pagar')
            ->assertSee('Pagado')
            ->assertSee('Cancelado')
            ->assertSee('Despachado')
            ->assertSee('Recibido')
            ->assertSee('Pedido del 10/10/2026')
            ->assertSee('$250')
            ->assertSee('$100')
            ->assertSee('+ envío a coordinar')
            ->assertDontSee($ajeno->numero)
            ->assertDontSee($ajeno->token_pago);

        $this->assertSame(1, $this->cuenta($respuesta, '//a[@href="'.route('pago.mostrar', $habilitado->token_pago).'"]'));
        $this->assertSame(0, $this->cuenta($respuesta, '//a[@href="'.route('pago.mostrar', $pagado->token_pago).'"]'));
        $this->assertSame(1, $this->cuenta($respuesta, '//a[normalize-space()="Pagar" or starts-with(normalize-space(), "Pagar")]'));
    }

    public function test_mis_pedidos_sin_pedidos_invita_a_ver_muebles(): void
    {
        $this->actingAs($this->usuario())
            ->get(route('cuenta.pedidos.index'))
            ->assertOk()
            ->assertSee('Todavía no hiciste ningún pedido.')
            ->assertSee('href="'.route('catalogo.index').'"', false);
    }

    public function test_el_estado_visible_traduce_cada_etapa_del_pedido(): void
    {
        $casos = [
            'cancelado' => [['estado_pedido' => EstadoPedido::Cancelado, 'estado_pago' => EstadoPago::Cancelado], 'Cancelado', 1, 'grafito'],
            'entregado' => [['estado_envio' => EstadoEnvio::Entregado, 'estado_pago' => EstadoPago::Pagado], 'Entregado', 5, 'amarillo'],
            'despachado' => [['estado_envio' => EstadoEnvio::Despachado, 'estado_pago' => EstadoPago::Pagado], 'Despachado', 4, 'amarillo'],
            'preparado' => [['estado_envio' => EstadoEnvio::Preparado, 'estado_pago' => EstadoPago::Pagado], 'Preparando tu pedido', 3, 'celeste'],
            'pagado' => [['estado_pago' => EstadoPago::Pagado, 'estado_envio' => EstadoEnvio::Aceptado], 'Pagado', 3, 'celeste'],
            'pago pendiente' => [['estado_pago' => EstadoPago::Pendiente, 'estado_envio' => EstadoEnvio::Aceptado], 'Confirmando tu pago', 2, 'celeste'],
            'pago habilitado' => [['estado_pago' => EstadoPago::Habilitado, 'estado_envio' => EstadoEnvio::Aceptado], 'Listo para pagar', 2, 'naranja'],
            'envío aceptado' => [['estado_envio' => EstadoEnvio::Aceptado], 'Envío acordado', 2, 'celeste'],
            'envío cotizado' => [['estado_envio' => EstadoEnvio::Cotizado], 'Envío cotizado', 1, 'celeste'],
            'sin avance' => [[], 'Recibido', 1, 'celeste'],
        ];

        foreach ($casos as $nombre => [$atributos, $etiqueta, $paso, $color]) {
            $pedido = Pedido::factory()->make($atributos);

            $estado = EstadoVisiblePedido::paraPedido($pedido);

            $this->assertSame($etiqueta, $estado['etiqueta'], $nombre);
            $this->assertSame($paso, $estado['paso'], $nombre);
            $this->assertSame($color, $estado['color'], $nombre);
            $this->assertNotSame('', $estado['descripcion'], $nombre);
        }
    }

    public function test_el_detalle_muestra_el_avance_los_totales_y_el_envio(): void
    {
        $usuario = $this->usuario();
        $pedido = Pedido::factory()->create([
            'usuario_id' => $usuario->id,
            'numero' => 'ER-2026-000401',
            'estado_pago' => EstadoPago::Pagado,
            'estado_envio' => EstadoEnvio::Despachado,
            'estado_pedido' => EstadoPedido::Confirmado,
            'expreso' => 'Andreani',
            'subtotal_productos' => '200.00',
            'costo_envio' => '50.00',
            'total_base' => '250.00',
            'direccion_envio' => 'Av. Italia',
            'numero_envio' => '123',
            'localidad_envio' => 'Río Cuarto',
            'provincia_envio' => Provincia::Cordoba,
            'codigo_postal_envio' => '5800',
            'observaciones_envio' => 'Timbre roto',
        ]);
        ItemPedido::factory()->for($pedido, 'pedido')->create([
            'nombre_producto' => 'Sillón Gran California',
            'color' => 'Blanco',
            'etiqueta' => null,
            'cantidad' => 2,
            'precio_unitario' => '100.00',
            'subtotal' => '200.00',
        ]);

        $respuesta = $this->actingAs($usuario)
            ->get(route('cuenta.pedidos.mostrar', $pedido->numero))
            ->assertOk()
            ->assertViewIs('tienda.cuenta-pedido')
            ->assertSee('<title>Pedido ER-2026-000401 · El Refugio</title>', false)
            ->assertSee('Despachado')
            ->assertSee('Lo enviamos por')
            ->assertSee('Andreani')
            ->assertSee('Sillón Gran California')
            ->assertSee('2 × $100')
            ->assertSee('Av. Italia 123')
            ->assertSee('Córdoba')
            ->assertSee('Timbre roto')
            ->assertSee('$200')
            ->assertSee('$50')
            ->assertSee('$250')
            ->assertDontSee('A coordinar por WhatsApp');

        $this->assertSame('step', $this->nodo($respuesta, '//li[@aria-current="step"]')->getAttribute('aria-current'));
        $this->assertStringContainsString('Despachado', $this->nodo($respuesta, '//li[@aria-current="step"]')->textContent);
        $this->assertSame(5, $this->cuenta($respuesta, '//ol/li'));
    }

    public function test_el_detalle_sin_envio_cotizado_muestra_envio_a_coordinar(): void
    {
        $usuario = $this->usuario();
        $pedido = Pedido::factory()->create([
            'usuario_id' => $usuario->id,
            'numero' => 'ER-2026-000402',
            'subtotal_productos' => '200.00',
        ]);

        $this->actingAs($usuario)
            ->get(route('cuenta.pedidos.mostrar', $pedido->numero))
            ->assertOk()
            ->assertSee('Recibido')
            ->assertSee('A coordinar por WhatsApp')
            ->assertSee('+ envío a coordinar')
            ->assertSee('Te vamos a escribir por WhatsApp para coordinar el envío.');
    }

    public function test_un_pedido_cancelado_por_reemplazo_muestra_el_motivo_y_enlaza_al_nuevo(): void
    {
        $usuario = $this->usuario();
        $original = Pedido::factory()->cancelado()->create([
            'usuario_id' => $usuario->id,
            'numero' => 'ER-2026-000501',
            'motivo_cancelacion' => MotivoCancelacion::Reemplazado,
        ]);
        $nuevo = Pedido::factory()->create([
            'usuario_id' => $usuario->id,
            'numero' => 'ER-2026-000502',
            'pedido_origen_id' => $original->id,
        ]);

        $respuesta = $this->actingAs($usuario)
            ->get(route('cuenta.pedidos.mostrar', $original->numero))
            ->assertOk()
            ->assertSee('Este pedido está cancelado')
            ->assertSee('Lo reemplazamos por un pedido nuevo')
            ->assertSee('Este pedido fue reemplazado por')
            ->assertSee($nuevo->numero)
            ->assertDontSee('Avance de tu pedido');

        $this->assertSame(1, $this->cuenta($respuesta, '//a[@href="'.route('cuenta.pedidos.mostrar', $nuevo->numero).'"]'));
    }

    public function test_el_detalle_ofrece_pagar_solo_cuando_el_pago_esta_habilitado(): void
    {
        $usuario = $this->usuario();
        $pedido = Pedido::factory()->conTokenPago()->create([
            'usuario_id' => $usuario->id,
            'estado_pago' => EstadoPago::Habilitado,
            'estado_envio' => EstadoEnvio::Aceptado,
            'subtotal_productos' => '200.00',
            'costo_envio' => '50.00',
            'total_base' => '250.00',
        ]);

        $this->actingAs($usuario)
            ->get(route('cuenta.pedidos.mostrar', $pedido->numero))
            ->assertOk()
            ->assertSee('Ya podés pagar tu pedido')
            ->assertSee('Pagar ahora')
            ->assertSee('href="'.route('pago.mostrar', $pedido->token_pago).'"', false);

        $pedido->update(['estado_pago' => EstadoPago::Deshabilitado]);

        $this->actingAs($usuario)
            ->get(route('cuenta.pedidos.mostrar', $pedido->numero))
            ->assertOk()
            ->assertDontSee('Pagar ahora')
            ->assertDontSee($pedido->token_pago);
    }

    public function test_el_detalle_ofrece_el_whatsapp_del_negocio_cuando_esta_configurado(): void
    {
        $usuario = $this->usuario();
        $pedido = Pedido::factory()->for($usuario, 'usuario')->create();

        $this->actingAs($usuario)
            ->get(route('cuenta.pedidos.mostrar', $pedido->numero))
            ->assertOk()
            ->assertDontSee('¿Tenés alguna duda con tu pedido?');

        Configuracion::query()->create(['clave' => 'contacto.whatsapp', 'valor' => '2284 123456']);

        $this->actingAs($usuario)
            ->get(route('cuenta.pedidos.mostrar', $pedido->numero))
            ->assertOk()
            ->assertSee('¿Tenés alguna duda con tu pedido?')
            ->assertSee('https://wa.me/5492284123456', false);
    }

    public function test_un_pedido_ajeno_responde_404_en_html(): void
    {
        $pedido = Pedido::factory()->create();

        $this->actingAs($this->usuario())
            ->get(route('cuenta.pedidos.mostrar', $pedido->numero))
            ->assertNotFound();
    }

    public function test_los_endpoints_json_siguen_igual_y_nunca_muestran_el_token_de_pago(): void
    {
        $this->configurarAjuste();
        $usuario = $this->usuario();
        $pedido = Pedido::factory()->conTokenPago()->create([
            'usuario_id' => $usuario->id,
            'numero' => 'ER-2026-000601',
            'estado_pago' => EstadoPago::Habilitado,
        ]);
        $token = $pedido->token_pago;

        $this->actingAs($usuario)
            ->getJson(route('checkout.resumen'))
            ->assertOk()
            ->assertJsonStructure(['checkout' => ['carrito', 'cliente', 'email_verificado', 'huella']]);

        $this->actingAs($usuario)
            ->getJson(route('cuenta.pedidos.index'))
            ->assertOk()
            ->assertJsonStructure(['pedidos' => ['data', 'current_page', 'last_page']])
            ->assertJsonPath('pedidos.data.0.numero', 'ER-2026-000601')
            ->assertJsonMissingPath('pedidos.data.0.token_pago')
            ->assertDontSee($token);

        $this->actingAs($usuario)
            ->getJson(route('cuenta.pedidos.mostrar', 'ER-2026-000601'))
            ->assertOk()
            ->assertJsonPath('pedido.numero', 'ER-2026-000601')
            ->assertJsonMissingPath('pedido.token_pago')
            ->assertDontSee($token);

        $this->actingAs($usuario)
            ->getJson(route('checkout.recibido', 'ER-2026-000601'))
            ->assertOk()
            ->assertJsonPath('pedido.numero', 'ER-2026-000601')
            ->assertJsonMissingPath('pedido.token_pago')
            ->assertDontSee($token);
    }

    public function test_confirmar_por_json_sigue_respondiendo_201_con_el_pedido(): void
    {
        $this->configurarAjuste();
        $usuario = $this->usuario();
        $this->agregarAlCarrito($this->crearVariante('100.00'), 1, $usuario);
        $huella = $this->actingAs($usuario)->getJson(route('checkout.resumen'))->json('checkout.huella');

        $this->actingAs($usuario)
            ->postJson(route('checkout.confirmar'), $this->datosEnvioValidos($huella))
            ->assertCreated()
            ->assertJsonPath('mensaje', 'Pedido creado. Te contactaremos para coordinar el envío.')
            ->assertJsonMissingPath('pedido.token_pago');
    }

    private function configurarAjuste(string $porcentaje = '0'): void
    {
        Configuracion::query()->updateOrCreate(
            ['clave' => 'ajuste_ecommerce_porcentaje'],
            ['valor' => $porcentaje],
        );
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    private function usuario(array $atributos = []): Usuario
    {
        return Usuario::factory()->create(array_merge(['password' => 'clave-segura'], $atributos));
    }

    private function crearVariante(string $precioBase = '100.00', string $nombre = 'Mesa de jardín', string $color = 'Verde'): VarianteProducto
    {
        $producto = Producto::factory()->create([
            'nombre' => $nombre,
            'activo' => true,
            'disponible' => true,
        ]);

        return VarianteProducto::factory()->create([
            'producto_id' => $producto->id,
            'color' => $color,
            'precio_base' => $precioBase,
            'activo' => true,
            'disponible' => true,
        ]);
    }

    private function agregarAlCarrito(VarianteProducto $variante, int $cantidad, Usuario $usuario): void
    {
        $this->actingAs($usuario)
            ->post(route('carrito.items.agregar'), ['variante_producto_id' => $variante->id, 'cantidad' => $cantidad])
            ->assertRedirect(route('carrito.mostrar'));
    }

    /** @return array<string, string> */
    private function datosEnvioValidos(string $huella): array
    {
        return [
            'provincia' => 'cordoba',
            'localidad' => 'Río Cuarto',
            'codigo_postal' => '5800',
            'direccion' => 'Av. Italia',
            'numero' => '123',
            'direccion_adicional' => 'Piso 2 Depto B',
            'observaciones' => 'Timbre roto',
            'huella' => $huella,
        ];
    }

    /**
     * Mantiene la sesión de la última respuesta para leer los avisos flash en el GET siguiente.
     */
    private function continuarSesion(): void
    {
        $this->withCookie(config('session.cookie'), session()->getId());
    }

    private function nodo(TestResponse $respuesta, string $consulta): DOMElement
    {
        $nodo = $this->documento($respuesta)->query($consulta)->item(0);

        $this->assertInstanceOf(DOMElement::class, $nodo, 'No se encontró el elemento: '.$consulta);

        return $nodo;
    }

    private function cuenta(TestResponse $respuesta, string $consulta): int
    {
        return $this->documento($respuesta)->query($consulta)->length;
    }

    private function documento(TestResponse $respuesta): DOMXPath
    {
        $documento = new DOMDocument;
        libxml_use_internal_errors(true);
        $documento->loadHTML('<?xml encoding="UTF-8">'.$respuesta->getContent());
        libxml_clear_errors();

        return new DOMXPath($documento);
    }

    public function test_el_codigo_postal_acepta_numeros_solos_con_letra_o_cpa_completo(): void
    {
        $reglas = (new class
        {
            use ReglasDatosEnvio;

            /** @return array<string, list<mixed>> */
            public function reglas(): array
            {
                return $this->reglasDatosEnvio();
            }
        })->reglas();

        foreach (['7400', 'B7400', 'B7400ABC', 'S2000'] as $valido) {
            $this->assertTrue(Validator::make(['codigo_postal' => $valido], ['codigo_postal' => $reglas['codigo_postal']])->passes(), $valido);
        }

        foreach (['740', 'B740', '7400AB', 'ABCD', 'B7400ABCD'] as $invalido) {
            $this->assertFalse(Validator::make(['codigo_postal' => $invalido], ['codigo_postal' => $reglas['codigo_postal']])->passes(), $invalido);
        }
    }
}
