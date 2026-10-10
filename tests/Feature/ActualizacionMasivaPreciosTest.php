<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Configuracion;
use App\Models\HistorialActualizacionPrecios;
use App\Models\ItemPedido;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\RegistroAuditoria;
use App\Models\Usuario;
use App\Models\VarianteProducto;
use App\Servicios\Auditoria\AuditoriaService;
use App\Servicios\Precios\ActualizacionMasivaPreciosService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use LogicException;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class ActualizacionMasivaPreciosTest extends TestCase
{
    use RefreshDatabase;

    private const string RUTA_VISTA_PREVIA = '/administracion/precios/actualizacion-masiva/vista-previa';

    private const string RUTA_APLICAR = '/administracion/precios/actualizacion-masiva';

    private const string RUTA_HISTORIAL = '/administracion/precios/actualizaciones-masivas';

    public function test_vista_previa_muestra_anteriores_nuevos_y_cantidad_sin_modificar_precios(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());
        $producto = Producto::factory()->create(['nombre' => 'Silla', 'activo' => true]);
        $variante = $this->crearVariante($producto, 'SILLA-1', '1000.00', true, ['color' => 'Negro', 'etiqueta' => 'Grande']);

        $this->postJson(self::RUTA_VISTA_PREVIA, [
            'operacion' => 'aumentar_porcentaje',
            'valor' => '10',
            'alcance' => 'variante',
            'variante_producto_id' => $variante->id,
        ])->assertOk()
            ->assertJsonPath('cantidad', 1)
            ->assertJsonPath('filas.0.variante_producto_id', $variante->id)
            ->assertJsonPath('filas.0.producto_id', $producto->id)
            ->assertJsonPath('filas.0.nombre_producto', 'Silla')
            ->assertJsonPath('filas.0.color', 'Negro')
            ->assertJsonPath('filas.0.etiqueta', 'Grande')
            ->assertJsonPath('filas.0.sku', 'SILLA-1')
            ->assertJsonPath('filas.0.activo', true)
            ->assertJsonPath('filas.0.precio_anterior', '1000.00')
            ->assertJsonPath('filas.0.precio_nuevo', '1100.00')
            ->assertJsonPath('huella', fn (string $huella): bool => strlen($huella) === 64);

        $this->assertSame('1000.00', (string) $variante->refresh()->precio_base);
        $this->assertDatabaseCount('historial_actualizaciones_precios', 0);
    }

    public function test_aumentar_y_disminuir_porcentaje_redondean_al_centavo(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());
        $aumento = $this->crearVariante(Producto::factory()->create(['nombre' => 'Mesa']), 'MESA-1', '1000.00');
        $decimalesDelTercio = $this->crearVariante(Producto::factory()->create(['nombre' => 'Banco']), 'BANCO-1', '333.33');
        $medioCentavo = $this->crearVariante(Producto::factory()->create(['nombre' => 'Farol']), 'FAROL-1', '0.05');
        $bajada = $this->crearVariante(Producto::factory()->create(['nombre' => 'Lámpara']), 'LAMPARA-1', '999.99');

        $this->assertSame('1075.00', $this->precioNuevoDe('aumentar_porcentaje', '7.5', $aumento));
        $this->assertSame('366.66', $this->precioNuevoDe('aumentar_porcentaje', '10', $decimalesDelTercio));
        $this->assertSame('0.06', $this->precioNuevoDe('aumentar_porcentaje', '10', $medioCentavo));
        $this->assertSame('666.66', $this->precioNuevoDe('disminuir_porcentaje', '33.3333', $bajada));
    }

    public function test_fijar_importe_en_un_producto_categoria_seleccionados_y_todos(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());
        $categoria = Categoria::factory()->create(['activo' => true]);
        $productoActivo = Producto::factory()->create(['nombre' => 'Alfa', 'activo' => true]);
        $productoInactivo = Producto::factory()->create(['nombre' => 'Beta', 'activo' => false]);
        $otroProductoActivo = Producto::factory()->create(['nombre' => 'Gamma', 'activo' => true]);
        $productoSinCategoria = Producto::factory()->create(['nombre' => 'Delta', 'activo' => false]);
        $productoActivo->categorias()->attach($categoria);
        $productoInactivo->categorias()->attach($categoria);
        $otroProductoActivo->categorias()->attach(Categoria::factory()->create(['activo' => true]));

        $varianteActivaDelProducto = $this->crearVariante($productoActivo, 'ALFA-1', '100.00', true);
        $varianteInactivaDelProducto = $this->crearVariante($productoActivo, 'ALFA-2', '110.00', false);
        $varianteDeProductoInactivo = $this->crearVariante($productoInactivo, 'BETA-1', '120.00', false);
        $varianteDeOtroProducto = $this->crearVariante($otroProductoActivo, 'GAMMA-1', '130.00', true);
        $varianteSinCategoria = $this->crearVariante($productoSinCategoria, 'DELTA-1', '140.00', false);

        $this->previsualizarFijar(['alcance' => 'variante', 'variante_producto_id' => $varianteInactivaDelProducto->id])
            ->assertJsonPath('cantidad', 1)
            ->assertJsonPath('filas.0.sku', 'ALFA-2');

        $this->previsualizarFijar(['alcance' => 'producto', 'producto_id' => $productoActivo->id])
            ->assertJsonPath('cantidad', 2)
            ->assertJsonPath('filas.0.sku', 'ALFA-1')
            ->assertJsonPath('filas.1.sku', 'ALFA-2');

        $this->previsualizarFijar(['alcance' => 'productos', 'producto_ids' => [$productoActivo->id, $otroProductoActivo->id]])
            ->assertJsonPath('cantidad', 3);

        $respuestaCategoria = $this->aplicarDesdeVistaPrevia(
            ['operacion' => 'fijar_importe', 'valor' => '500', 'alcance' => 'categoria', 'categoria_id' => $categoria->id],
        );
        $respuestaCategoria->assertCreated()->assertJsonPath('historial.cantidad_afectados', 3);

        $this->assertSame('500.00', (string) $varianteActivaDelProducto->refresh()->precio_base);
        $this->assertSame('500.00', (string) $varianteInactivaDelProducto->refresh()->precio_base);
        $this->assertSame('500.00', (string) $varianteDeProductoInactivo->refresh()->precio_base);
        $this->assertSame('130.00', (string) $varianteDeOtroProducto->refresh()->precio_base);
        $this->assertSame('140.00', (string) $varianteSinCategoria->refresh()->precio_base);

        $this->previsualizarFijar(['alcance' => 'todos'])->assertJsonPath('cantidad', 2);
    }

    public function test_si_algun_precio_queda_en_cero_o_menos_se_rechaza_todo(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());
        $bajo = $this->crearVariante(Producto::factory()->create(['nombre' => 'Alfa', 'activo' => true]), 'BAJO-1', '0.01');
        $alto = $this->crearVariante(Producto::factory()->create(['nombre' => 'Beta', 'activo' => true]), 'ALTO-1', '1000.00');

        $respuesta = $this->postJson(self::RUTA_VISTA_PREVIA, [
            'operacion' => 'disminuir_porcentaje',
            'valor' => '99.9',
            'alcance' => 'todos',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('valor');

        $this->assertStringContainsString('BAJO-1', $respuesta->json('errors.valor.0'));
        $this->assertSame('0.01', (string) $bajo->refresh()->precio_base);
        $this->assertSame('1000.00', (string) $alto->refresh()->precio_base);
        $this->assertDatabaseCount('historial_actualizaciones_precios', 0);
    }

    public function test_aplicar_con_huella_vieja_se_rechaza_y_no_cambia_nada(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());
        $primera = $this->crearVariante(Producto::factory()->create(['nombre' => 'Alfa', 'activo' => true]), 'VIEJA-1', '1000.00');
        $segunda = $this->crearVariante(Producto::factory()->create(['nombre' => 'Beta', 'activo' => true]), 'VIEJA-2', '2000.00');

        $vistaPrevia = $this->postJson(self::RUTA_VISTA_PREVIA, [
            'operacion' => 'aumentar_porcentaje',
            'valor' => '10',
            'alcance' => 'todos',
        ])->assertOk()->json();

        $primera->update(['precio_base' => '1500.00']);

        $this->postJson(self::RUTA_APLICAR, [
            'operacion' => 'aumentar_porcentaje',
            'valor' => '10',
            'alcance' => 'todos',
            'huella' => $vistaPrevia['huella'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('huella');

        $this->assertSame('1500.00', (string) $primera->refresh()->precio_base);
        $this->assertSame('2000.00', (string) $segunda->refresh()->precio_base);
        $this->assertDatabaseCount('historial_actualizaciones_precios', 0);
        $this->assertDatabaseMissing('registros_auditoria', ['accion' => 'precios.actualizacion_masiva']);
    }

    public function test_aplicar_actualiza_en_una_transaccion_y_deja_historial_y_auditoria(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $this->actingAs($administrador);
        $primera = $this->crearVariante(Producto::factory()->create(['nombre' => 'Alfa', 'activo' => true]), 'APLICA-1', '1000.00');
        $segunda = $this->crearVariante(Producto::factory()->create(['nombre' => 'Beta', 'activo' => false]), 'APLICA-2', '200.00', false);

        $respuesta = $this->aplicarDesdeVistaPrevia([
            'operacion' => 'aumentar_porcentaje',
            'valor' => '10',
            'alcance' => 'todos',
        ])->assertCreated()
            ->assertJsonPath('historial.cantidad_afectados', 2)
            ->assertJsonPath('historial.operacion', 'aumentar_porcentaje')
            ->assertJsonPath('historial.usuario.id', $administrador->id)
            ->assertJsonPath('historial.detalle.0.sku', 'APLICA-1')
            ->assertJsonPath('historial.detalle.0.precio_anterior', '1000.00')
            ->assertJsonPath('historial.detalle.0.precio_nuevo', '1100.00');

        $this->assertSame('1100.00', (string) $primera->refresh()->precio_base);
        $this->assertSame('220.00', (string) $segunda->refresh()->precio_base);

        $historial = HistorialActualizacionPrecios::query()->firstOrFail();
        $this->assertSame($respuesta->json('historial.id'), $historial->id);
        $this->assertSame($administrador->id, $historial->usuario_id);
        $this->assertSame('10.000000', $historial->valor);
        $this->assertSame('todos', $historial->alcance->value);
        $this->assertNull($historial->filtro);
        $this->assertCount(2, $historial->detalle);

        $auditoria = RegistroAuditoria::query()->where('accion', 'precios.actualizacion_masiva')->get();
        $this->assertCount(1, $auditoria);
        $this->assertSame('historial_actualizaciones_precios', $auditoria->first()->entidad);
        $this->assertSame($historial->id, $auditoria->first()->entidad_id);
        $this->assertSame($administrador->id, $auditoria->first()->usuario_id);
        $this->assertEquals(
            ['historial_id' => $historial->id, 'operacion' => 'aumentar_porcentaje', 'valor' => '10', 'alcance' => 'todos', 'cantidad_afectados' => 2],
            $auditoria->first()->datos_nuevos,
        );

        $this->actingAs($administrador)
            ->getJson(self::RUTA_HISTORIAL)
            ->assertOk()
            ->assertJsonPath('historiales.data.0.id', $historial->id)
            ->assertJsonPath('historiales.data.0.cantidad_afectados', 2)
            ->assertJsonMissingPath('historiales.data.0.detalle')
            ->assertJsonPath('historiales.data.0.usuario.nombre', $administrador->nombre);

        $this->getJson(self::RUTA_HISTORIAL.'/'.$historial->id)
            ->assertOk()
            ->assertJsonCount(2, 'historial.detalle')
            ->assertJsonPath('historial.usuario.nombre', $administrador->nombre);
    }

    public function test_si_falla_la_auditoria_no_queda_ningun_cambio_a_medio_aplicar(): void
    {
        $variante = $this->crearVariante(Producto::factory()->create(['nombre' => 'Alfa', 'activo' => true]), 'ATOMICO-1', '1000.00');
        $parametros = ['operacion' => 'aumentar_porcentaje', 'valor' => '10', 'alcance' => 'variante', 'variante_producto_id' => $variante->id];
        $administrador = Usuario::factory()->administrador()->create();
        $servicio = app(ActualizacionMasivaPreciosService::class);
        $huella = $servicio->previsualizar($parametros)['huella'];

        $this->mock(AuditoriaService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('registrar')->once()->andThrow(new RuntimeException('Auditoría no disponible.'));
        });

        $this->assertThrows(
            fn () => app(ActualizacionMasivaPreciosService::class)->aplicar($administrador, $parametros, $huella),
            RuntimeException::class,
        );

        $this->assertSame('1000.00', (string) $variante->refresh()->precio_base);
        $this->assertDatabaseCount('historial_actualizaciones_precios', 0);
    }

    public function test_el_historial_no_se_puede_editar_ni_borrar(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());
        $variante = $this->crearVariante(Producto::factory()->create(['nombre' => 'Alfa', 'activo' => true]), 'INMUTABLE-1', '1000.00');
        $this->aplicarDesdeVistaPrevia(['operacion' => 'fijar_importe', 'valor' => '750', 'alcance' => 'variante', 'variante_producto_id' => $variante->id])
            ->assertCreated();

        $historial = HistorialActualizacionPrecios::query()->firstOrFail();

        $this->assertThrows(fn () => $historial->update(['cantidad_afectados' => 99]), LogicException::class);
        $this->assertThrows(fn () => $historial->delete(), LogicException::class);

        $this->deleteJson(self::RUTA_HISTORIAL.'/'.$historial->id)->assertStatus(405);
        $this->putJson(self::RUTA_HISTORIAL.'/'.$historial->id, ['cantidad_afectados' => 99])->assertStatus(405);

        $this->assertDatabaseCount('historial_actualizaciones_precios', 1);
        $this->assertSame(1, $historial->refresh()->cantidad_afectados);
    }

    public function test_los_pedidos_existentes_conservan_su_precio(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());
        $variante = $this->crearVariante(Producto::factory()->create(['nombre' => 'Alfa', 'activo' => true]), 'PEDIDO-1', '1000.00');
        $pedido = Pedido::factory()->create();
        $item = ItemPedido::factory()->create([
            'pedido_id' => $pedido->id,
            'producto_id' => $variante->producto_id,
            'variante_producto_id' => $variante->id,
            'sku' => 'PEDIDO-1',
            'precio_unitario' => '1000.00',
            'cantidad' => 2,
            'subtotal' => '2000.00',
        ]);

        $this->aplicarDesdeVistaPrevia(['operacion' => 'aumentar_porcentaje', 'valor' => '50', 'alcance' => 'variante', 'variante_producto_id' => $variante->id])
            ->assertCreated();

        $this->assertSame('1500.00', (string) $variante->refresh()->precio_base);
        $item->refresh();
        $this->assertSame('1000.00', (string) $item->precio_unitario);
        $this->assertSame('2000.00', (string) $item->subtotal);
    }

    public function test_el_catalogo_refleja_el_precio_nuevo(): void
    {
        Storage::fake('public');
        Configuracion::query()->create(['clave' => 'ajuste_ecommerce_porcentaje', 'valor' => '10']);
        $producto = Producto::factory()->create(['nombre' => 'Farol', 'slug' => 'farol', 'activo' => true, 'disponible' => true]);
        $producto->categorias()->attach(Categoria::factory()->create(['activo' => true]));
        $this->agregarImagenPrincipalLista($producto);
        $variante = $this->crearVariante($producto, 'FAROL-1', '1000.00', true, ['disponible' => true]);

        $this->getJson('/catalogo/farol')
            ->assertOk()
            ->assertJsonPath('producto.variantes.0.precio_publicado', '1100.00');

        $this->actingAs(Usuario::factory()->administrador()->create());
        $this->aplicarDesdeVistaPrevia(['operacion' => 'aumentar_porcentaje', 'valor' => '50', 'alcance' => 'producto', 'producto_id' => $producto->id])
            ->assertCreated();

        auth()->logout();
        $this->assertSame('1500.00', (string) $variante->refresh()->precio_base);
        $this->getJson('/catalogo/farol')
            ->assertOk()
            ->assertJsonPath('producto.variantes.0.precio_publicado', '1650.00');
    }

    public function test_cliente_no_puede_usar_actualizaciones_masivas(): void
    {
        $variante = $this->crearVariante(Producto::factory()->create(['nombre' => 'Alfa', 'activo' => true]), 'CLIENTE-1', '1000.00');
        $historialPrevio = HistorialActualizacionPrecios::query()->count();

        $this->actingAs(Usuario::factory()->create());

        $this->postJson(self::RUTA_VISTA_PREVIA, [
            'operacion' => 'aumentar_porcentaje',
            'valor' => '10',
            'alcance' => 'variante',
            'variante_producto_id' => $variante->id,
        ])->assertForbidden();

        $this->postJson(self::RUTA_APLICAR, [
            'operacion' => 'aumentar_porcentaje',
            'valor' => '10',
            'alcance' => 'variante',
            'variante_producto_id' => $variante->id,
            'huella' => str_repeat('a', 64),
        ])->assertForbidden();

        $this->getJson(self::RUTA_HISTORIAL)->assertForbidden();

        $this->assertSame('1000.00', (string) $variante->refresh()->precio_base);
        $this->assertSame($historialPrevio, HistorialActualizacionPrecios::query()->count());
    }

    public function test_valida_operacion_valor_alcance_y_huella(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());
        $variante = $this->crearVariante(Producto::factory()->create(['nombre' => 'Alfa', 'activo' => true]), 'VALIDA-1', '1000.00');

        $this->postJson(self::RUTA_VISTA_PREVIA, ['operacion' => 'disminuir_porcentaje', 'valor' => '100', 'alcance' => 'todos'])
            ->assertUnprocessable()->assertJsonValidationErrors('valor');
        $this->postJson(self::RUTA_VISTA_PREVIA, ['operacion' => 'aumentar_porcentaje', 'valor' => '1000.01', 'alcance' => 'todos'])
            ->assertUnprocessable()->assertJsonValidationErrors('valor');
        $this->postJson(self::RUTA_VISTA_PREVIA, ['operacion' => 'aumentar_porcentaje', 'valor' => '1.12345', 'alcance' => 'todos'])
            ->assertUnprocessable()->assertJsonValidationErrors('valor');
        $this->postJson(self::RUTA_VISTA_PREVIA, ['operacion' => 'fijar_importe', 'valor' => '0', 'alcance' => 'todos'])
            ->assertUnprocessable()->assertJsonValidationErrors('valor');
        $this->postJson(self::RUTA_VISTA_PREVIA, ['operacion' => 'fijar_importe', 'valor' => '10.123', 'alcance' => 'todos'])
            ->assertUnprocessable()->assertJsonValidationErrors('valor');
        $this->postJson(self::RUTA_VISTA_PREVIA, ['operacion' => 'rebajar', 'valor' => '10', 'alcance' => 'todos'])
            ->assertUnprocessable()->assertJsonValidationErrors('operacion');
        $this->postJson(self::RUTA_VISTA_PREVIA, ['operacion' => 'fijar_importe', 'valor' => '10', 'alcance' => 'producto'])
            ->assertUnprocessable()->assertJsonValidationErrors('producto_id');
        $this->postJson(self::RUTA_VISTA_PREVIA, ['operacion' => 'fijar_importe', 'valor' => '10', 'alcance' => 'productos', 'producto_ids' => [1, 1]])
            ->assertUnprocessable()->assertJsonValidationErrors('producto_ids.0');
        $this->postJson(self::RUTA_VISTA_PREVIA, ['operacion' => 'fijar_importe', 'valor' => '10', 'alcance' => 'variante', 'variante_producto_id' => 999999])
            ->assertUnprocessable()->assertJsonValidationErrors('variante_producto_id');

        $this->postJson(self::RUTA_APLICAR, [
            'operacion' => 'fijar_importe',
            'valor' => '900',
            'alcance' => 'variante',
            'variante_producto_id' => $variante->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('huella');

        $this->assertSame('1000.00', (string) $variante->refresh()->precio_base);
    }

    private function crearVariante(Producto $producto, string $sku, string $precio, bool $activo = true, array $atributos = []): VarianteProducto
    {
        return VarianteProducto::factory()->create(array_merge([
            'producto_id' => $producto->id,
            'sku' => $sku,
            'precio_base' => $precio,
            'activo' => $activo,
            'disponible' => true,
        ], $atributos));
    }

    private function precioNuevoDe(string $operacion, string $valor, VarianteProducto $variante): string
    {
        return $this->postJson(self::RUTA_VISTA_PREVIA, [
            'operacion' => $operacion,
            'valor' => $valor,
            'alcance' => 'variante',
            'variante_producto_id' => $variante->id,
        ])->assertOk()->json('filas.0.precio_nuevo');
    }

    /** @param  array<string, mixed>  $alcance */
    private function previsualizarFijar(array $alcance): TestResponse
    {
        return $this->postJson(self::RUTA_VISTA_PREVIA, array_merge(
            ['operacion' => 'fijar_importe', 'valor' => '500'],
            $alcance,
        ))->assertOk();
    }

    /** @param  array<string, mixed>  $datos */
    private function aplicarDesdeVistaPrevia(array $datos): TestResponse
    {
        $vistaPrevia = $this->postJson(self::RUTA_VISTA_PREVIA, $datos)->assertOk()->json();

        return $this->postJson(self::RUTA_APLICAR, array_merge($datos, ['huella' => $vistaPrevia['huella']]));
    }

    private function agregarImagenPrincipalLista(Producto $producto): void
    {
        $archivoId = DB::table('archivos_imagenes_producto')->insertGetId([
            'disco_temporal' => 'local',
            'ruta_temporal' => 'temporal/foto.jpg',
            'disco' => 'public',
            'ruta' => 'productos/foto.jpg',
            'ruta_miniatura' => 'productos/foto-miniatura.jpg',
            'estado' => 'listo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $producto->imagenes()->create([
            'archivo_imagen_producto_id' => $archivoId,
            'ruta' => 'productos/foto.jpg',
            'ruta_miniatura' => 'productos/foto-miniatura.jpg',
            'estado' => 'listo',
            'orden' => 0,
            'es_principal' => true,
        ]);
    }
}
