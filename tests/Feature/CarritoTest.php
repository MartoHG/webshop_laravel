<?php

namespace Tests\Feature;

use App\Models\ArchivoImagenProducto;
use App\Models\Configuracion;
use App\Models\ImagenProducto;
use App\Models\Producto;
use App\Models\Usuario;
use App\Models\VarianteProducto;
use App\Servicios\Carrito\CarritoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CarritoTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_agrega_variante_disponible_y_ve_el_precio_publicado_actual(): void
    {
        Storage::fake('public');
        $this->configurarAjuste('10');
        $variante = $this->crearVarianteDisponible('100.00');
        $this->agregarImagenPrincipal($variante->producto);

        $this->postJson('/carrito/items', ['variante_producto_id' => $variante->id, 'cantidad' => 2])
            ->assertOk()
            ->assertJsonPath('carrito.items.0.variante_producto_id', $variante->id)
            ->assertJsonPath('carrito.items.0.nombre_producto', 'Kayak Río Azul')
            ->assertJsonPath('carrito.items.0.precio_unitario', '110.00')
            ->assertJsonPath('carrito.items.0.cantidad', 2)
            ->assertJsonPath('carrito.items.0.subtotal', '220.00')
            ->assertJsonPath('carrito.items.0.disponible', true)
            ->assertJsonPath('carrito.items.0.motivo', null)
            ->assertJsonPath('carrito.items.0.imagen_miniatura', Storage::disk('public')->url('productos/foto-miniatura.jpg'))
            ->assertJsonPath('carrito.subtotal', '220.00')
            ->assertJsonPath('carrito.cantidad_unidades', 2)
            ->assertJsonPath('carrito.puede_continuar', true)
            ->assertJsonMissingPath('carrito.items.0.precio_base');

        $this->getJson('/carrito')
            ->assertOk()
            ->assertJsonPath('carrito.items.0.precio_unitario', '110.00');
    }

    public function test_la_miniatura_prioriza_la_imagen_propia_de_la_variante(): void
    {
        Storage::fake('public');
        $this->configurarAjuste('0');
        $variante = $this->crearVarianteDisponible('10.00');
        $this->agregarImagenPrincipal($variante->producto, null, 'productos/general-miniatura.jpg');
        $this->agregarImagenPrincipal($variante->producto, $variante, 'productos/variante-miniatura.jpg');

        $this->postJson('/carrito/items', ['variante_producto_id' => $variante->id, 'cantidad' => 1])
            ->assertOk()
            ->assertJsonPath('carrito.items.0.imagen_miniatura', Storage::disk('public')->url('productos/variante-miniatura.jpg'));
    }

    public function test_agregar_la_misma_variante_suma_en_la_misma_linea_hasta_99(): void
    {
        $this->configurarAjuste('0');
        $variante = $this->crearVarianteDisponible('50.00');

        $this->postJson('/carrito/items', ['variante_producto_id' => $variante->id, 'cantidad' => 98])->assertOk();

        $this->postJson('/carrito/items', ['variante_producto_id' => $variante->id, 'cantidad' => 1])
            ->assertOk()
            ->assertJsonCount(1, 'carrito.items')
            ->assertJsonPath('carrito.items.0.cantidad', 99)
            ->assertJsonPath('carrito.subtotal', '4950.00');

        $this->postJson('/carrito/items', ['variante_producto_id' => $variante->id, 'cantidad' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cantidad');

        $this->assertSame([$variante->id => 99], session('carrito'));
    }

    public function test_no_se_agrega_variante_agotada_inactiva_o_de_producto_inactivo(): void
    {
        $this->configurarAjuste('10');

        $agotada = $this->crearVarianteDisponible();
        $agotada->forceFill(['disponible' => false])->save();

        $inactiva = $this->crearVarianteDisponible();
        $inactiva->forceFill(['activo' => false])->save();

        $productoInactivo = $this->crearVarianteDisponible();
        $productoInactivo->producto->forceFill(['activo' => false])->save();

        $productoAgotado = $this->crearVarianteDisponible();
        $productoAgotado->producto->forceFill(['disponible' => false])->save();

        foreach ([$agotada, $inactiva, $productoInactivo, $productoAgotado] as $variante) {
            $this->postJson('/carrito/items', ['variante_producto_id' => $variante->id, 'cantidad' => 1])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('variante_producto_id');
        }

        $this->postJson('/carrito/items', ['variante_producto_id' => 999999, 'cantidad' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('variante_producto_id');

        $this->getJson('/carrito')
            ->assertOk()
            ->assertJsonCount(0, 'carrito.items')
            ->assertJsonPath('carrito.puede_continuar', false);
    }

    public function test_cantidad_debe_estar_entre_1_y_99(): void
    {
        $this->configurarAjuste('10');
        $variante = $this->crearVarianteDisponible();

        foreach ([0, 100, 'abc'] as $cantidad) {
            $this->postJson('/carrito/items', ['variante_producto_id' => $variante->id, 'cantidad' => $cantidad])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('cantidad');
        }

        $this->postJson('/carrito/items', ['variante_producto_id' => $variante->id, 'cantidad' => 5])->assertOk();

        foreach ([0, 100] as $cantidad) {
            $this->patchJson("/carrito/items/{$variante->id}", ['cantidad' => $cantidad])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('cantidad');
        }

        $this->patchJson("/carrito/items/{$variante->id}", ['cantidad' => 99])
            ->assertOk()
            ->assertJsonPath('carrito.items.0.cantidad', 99);
    }

    public function test_el_carrito_no_confia_en_el_precio_guardado_en_sesion(): void
    {
        $this->configurarAjuste('10');
        $variante = $this->crearVarianteDisponible('100.00');

        $this->postJson('/carrito/items', ['variante_producto_id' => $variante->id, 'cantidad' => 1])
            ->assertJsonPath('carrito.items.0.precio_unitario', '110.00');

        $this->assertSame([$variante->id => 1], session('carrito'));

        $variante->update(['precio_base' => '200.00']);
        $this->configurarAjuste('20');

        $this->getJson('/carrito')
            ->assertOk()
            ->assertJsonPath('carrito.items.0.precio_unitario', '240.00')
            ->assertJsonPath('carrito.subtotal', '240.00');
    }

    public function test_variante_que_se_agota_queda_marcada_y_bloquea_continuar_sin_borrarse(): void
    {
        $this->configurarAjuste('0');
        $primera = $this->crearVarianteDisponible('100.00');
        $segunda = $this->crearVarianteDisponible('50.00');

        $this->postJson('/carrito/items', ['variante_producto_id' => $primera->id, 'cantidad' => 2])->assertOk();
        $this->postJson('/carrito/items', ['variante_producto_id' => $segunda->id, 'cantidad' => 1])->assertOk();

        $primera->forceFill(['disponible' => false])->save();

        $this->getJson('/carrito')
            ->assertOk()
            ->assertJsonCount(2, 'carrito.items')
            ->assertJsonPath('carrito.items.0.disponible', false)
            ->assertJsonPath('carrito.items.0.motivo', 'agotado')
            ->assertJsonPath('carrito.items.0.precio_unitario', '100.00')
            ->assertJsonPath('carrito.items.1.disponible', true)
            ->assertJsonPath('carrito.items.1.motivo', null)
            ->assertJsonPath('carrito.subtotal', '50.00')
            ->assertJsonPath('carrito.cantidad_unidades', 3)
            ->assertJsonPath('carrito.puede_continuar', false);

        $this->patchJson("/carrito/items/{$primera->id}", ['cantidad' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('variante_producto_id');

        $this->assertSame([$primera->id => 2, $segunda->id => 1], session('carrito'));
    }

    public function test_variante_eliminada_o_de_producto_inactivo_se_muestra_como_no_disponible(): void
    {
        $this->configurarAjuste('0');
        $conProductoInactivo = $this->crearVarianteDisponible('100.00');
        $eliminada = $this->crearVarianteDisponible('50.00');

        $this->postJson('/carrito/items', ['variante_producto_id' => $conProductoInactivo->id, 'cantidad' => 1])->assertOk();
        $this->postJson('/carrito/items', ['variante_producto_id' => $eliminada->id, 'cantidad' => 2])->assertOk();

        $conProductoInactivo->producto->forceFill(['activo' => false])->save();
        $eliminada->delete();

        $this->getJson('/carrito')
            ->assertOk()
            ->assertJsonCount(2, 'carrito.items')
            ->assertJsonPath('carrito.items.0.motivo', 'no_disponible')
            ->assertJsonPath('carrito.items.1.variante_producto_id', $eliminada->id)
            ->assertJsonPath('carrito.items.1.nombre_producto', null)
            ->assertJsonPath('carrito.items.1.precio_unitario', null)
            ->assertJsonPath('carrito.items.1.motivo', 'no_disponible')
            ->assertJsonPath('carrito.subtotal', '0.00')
            ->assertJsonPath('carrito.cantidad_unidades', 3)
            ->assertJsonPath('carrito.puede_continuar', false);
    }

    public function test_quitar_y_vaciar_el_carrito(): void
    {
        $this->configurarAjuste('0');
        $primera = $this->crearVarianteDisponible('10.00');
        $segunda = $this->crearVarianteDisponible('20.00');

        $this->postJson('/carrito/items', ['variante_producto_id' => $primera->id, 'cantidad' => 1])->assertOk();
        $this->postJson('/carrito/items', ['variante_producto_id' => $segunda->id, 'cantidad' => 3])->assertOk();

        $this->deleteJson("/carrito/items/{$primera->id}")
            ->assertOk()
            ->assertJsonCount(1, 'carrito.items')
            ->assertJsonPath('carrito.items.0.variante_producto_id', $segunda->id)
            ->assertJsonPath('carrito.subtotal', '60.00');

        $this->deleteJson("/carrito/items/{$primera->id}")->assertNotFound();

        $this->deleteJson('/carrito')
            ->assertOk()
            ->assertJsonCount(0, 'carrito.items')
            ->assertJsonPath('carrito.subtotal', '0.00')
            ->assertJsonPath('carrito.cantidad_unidades', 0)
            ->assertJsonPath('carrito.puede_continuar', false);

        $this->getJson('/carrito')
            ->assertOk()
            ->assertJsonCount(0, 'carrito.items');
    }

    public function test_el_carrito_se_conserva_al_iniciar_sesion(): void
    {
        $this->configurarAjuste('10');
        $variante = $this->crearVarianteDisponible('100.00');
        $usuario = Usuario::factory()->create(['password' => 'clave-segura']);

        $this->postJson('/carrito/items', ['variante_producto_id' => $variante->id, 'cantidad' => 2])->assertOk();

        $this->postJson('/iniciar-sesion', ['email' => $usuario->email, 'password' => 'clave-segura'])
            ->assertOk();
        $this->assertAuthenticatedAs($usuario);

        $this->getJson('/carrito')
            ->assertOk()
            ->assertJsonCount(1, 'carrito.items')
            ->assertJsonPath('carrito.items.0.variante_producto_id', $variante->id)
            ->assertJsonPath('carrito.items.0.cantidad', 2);
    }

    public function test_validar_para_checkout_rechaza_carrito_vacio_o_con_items_no_disponibles(): void
    {
        $this->configurarAjuste('10');
        $variante = $this->crearVarianteDisponible('100.00');

        $this->assertRechazaCheckout();

        $this->postJson('/carrito/items', ['variante_producto_id' => $variante->id, 'cantidad' => 1])->assertOk();

        $this->assertSame('110.00', app(CarritoService::class)->validarParaCheckout()['subtotal']);

        $variante->forceFill(['disponible' => false])->save();
        $this->assertRechazaCheckout();

        $variante->forceFill(['disponible' => true])->save();
        $variante->producto->forceFill(['activo' => false])->save();
        $this->assertRechazaCheckout();
    }

    public function test_sin_ajuste_ecommerce_configurado_el_carrito_responde_503(): void
    {
        $variante = $this->crearVarianteDisponible('100.00');

        $this->getJson('/carrito')
            ->assertServiceUnavailable()
            ->assertJsonPath('codigo', 'ajuste_ecommerce_no_configurado');

        $this->postJson('/carrito/items', ['variante_producto_id' => $variante->id, 'cantidad' => 1])
            ->assertServiceUnavailable()
            ->assertJsonPath('codigo', 'ajuste_ecommerce_no_configurado');

        $this->deleteJson('/carrito')
            ->assertServiceUnavailable()
            ->assertJsonPath('codigo', 'ajuste_ecommerce_no_configurado');

        $this->assertEmpty(session('carrito', []));
    }

    private function assertRechazaCheckout(): void
    {
        try {
            app(CarritoService::class)->validarParaCheckout();

            $this->fail('El checkout debía rechazar el carrito.');
        } catch (ValidationException $excepcion) {
            $this->assertArrayHasKey('carrito', $excepcion->errors());
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
            'nombre' => 'Kayak Río Azul',
            'activo' => true,
            'disponible' => true,
        ]);

        return VarianteProducto::factory()->create([
            'producto_id' => $producto->id,
            'color' => 'Azul',
            'precio_base' => $precioBase,
            'activo' => true,
            'disponible' => true,
        ]);
    }

    private function agregarImagenPrincipal(Producto $producto, ?VarianteProducto $variante = null, string $miniatura = 'productos/foto-miniatura.jpg'): void
    {
        $archivo = ArchivoImagenProducto::query()->create([
            'disco_temporal' => 'local',
            'ruta_temporal' => 'temporal/'.fake()->uuid(),
            'disco' => 'public',
            'ruta' => 'productos/'.fake()->uuid().'.jpg',
            'ruta_miniatura' => $miniatura,
            'estado' => 'listo',
        ]);

        ImagenProducto::query()->create([
            'producto_id' => $producto->id,
            'variante_producto_id' => $variante?->id,
            'archivo_imagen_producto_id' => $archivo->id,
            'ruta' => $archivo->ruta,
            'ruta_miniatura' => $miniatura,
            'estado' => 'listo',
            'orden' => 0,
            'es_principal' => true,
        ]);
    }
}
