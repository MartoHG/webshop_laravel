<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\ImagenProducto;
use App\Models\Producto;
use App\Models\Usuario;
use App\Models\VarianteProducto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductosVariantesTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrador_crea_producto_inactivo_con_variante_y_slug_que_no_cambia_al_renombrar(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $this->actingAs($administrador);

        $respuesta = $this->postJson('/administracion/productos', [
            'nombre' => 'Kayak Río Azul',
            'variantes' => [[
                'color' => 'Azul',
                'sku' => 'KAYAK-AZUL-01',
                'precio_base' => '1200.50',
            ]],
        ]);

        $respuesta->assertCreated()
            ->assertJsonPath('producto.slug', 'kayak-rio-azul')
            ->assertJsonPath('producto.activo', false)
            ->assertJsonPath('producto.disponible', false)
            ->assertJsonPath('producto.variantes.0.activo', false);

        $producto = Producto::query()->firstOrFail();
        $slugOriginal = $producto->slug;

        $this->patchJson("/administracion/productos/{$producto->id}", ['nombre' => 'Kayak Río Verde'])
            ->assertOk()
            ->assertJsonPath('producto.nombre', 'Kayak Río Verde')
            ->assertJsonPath('producto.slug', $slugOriginal);
    }

    public function test_no_permite_crear_variantes_con_color_repetido_sin_etiquetas(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $this->actingAs($administrador);

        $this->postJson('/administracion/productos', [
            'nombre' => 'Silla plegable',
            'variantes' => [
                ['color' => ' Negro ', 'sku' => 'SILLA-1', 'precio_base' => '500.00'],
                ['color' => 'negro', 'sku' => 'SILLA-2', 'precio_base' => '510.00'],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['variantes.0.etiqueta', 'variantes.1.etiqueta']);

        $this->assertDatabaseCount('productos', 0);
        $this->assertDatabaseCount('variantes_producto', 0);
    }

    public function test_agregar_variante_valida_el_conjunto_completo_de_variantes(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $this->actingAs($administrador);
        $producto = Producto::factory()->create();
        $primera = VarianteProducto::factory()->create([
            'producto_id' => $producto->id,
            'color' => 'Rojo',
            'etiqueta' => 'Rojo original',
            'sku' => 'ROJO-1',
        ]);

        $this->postJson("/administracion/productos/{$producto->id}/variantes", [
            'color' => ' rojo ',
            'sku' => 'ROJO-2',
            'precio_base' => '200.00',
        ])->assertUnprocessable()->assertJsonValidationErrors(['variantes.1.etiqueta']);

        $this->patchJson("/administracion/productos/{$producto->id}/variantes/{$primera->id}", [
            'etiqueta' => 'Rojo oscuro',
        ])->assertOk();

        $this->postJson("/administracion/productos/{$producto->id}/variantes", [
            'color' => ' ROJO ',
            'etiqueta' => 'Rojo claro',
            'sku' => 'ROJO-2',
            'precio_base' => '200.00',
        ])->assertCreated()->assertJsonPath('variante.activo', false);
    }

    public function test_rechaza_arrays_en_color_y_sku_sin_coercion(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());

        $this->postJson('/administracion/productos', [
            'nombre' => 'Producto inválido',
            'variantes' => [[
                'color' => ['Rojo'],
                'sku' => ['SKU-ARRAY'],
                'precio_base' => '100.00',
            ]],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['variantes.0.color', 'variantes.0.sku']);

        $producto = Producto::factory()->create();
        $this->postJson("/administracion/productos/{$producto->id}/variantes", [
            'color' => ['Azul'],
            'sku' => ['SKU-ARRAY'],
            'precio_base' => '100.00',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['color', 'sku']);

        $variante = VarianteProducto::factory()->create(['producto_id' => $producto->id]);
        $this->patchJson("/administracion/productos/{$producto->id}/variantes/{$variante->id}", [
            'color' => ['Verde'],
            'sku' => ['SKU-ARRAY'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['color', 'sku']);
    }

    public function test_color_null_repetido_exige_etiquetas_y_las_etiquetas_no_pueden_repetirse(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $this->actingAs($administrador);
        $datos = [
            'nombre' => 'Kayak',
            'variantes' => [
                ['sku' => 'KAYAK-1', 'precio_base' => '100.00', 'etiqueta' => 'Simple'],
                ['sku' => 'KAYAK-2', 'precio_base' => '110.00', 'etiqueta' => ' simple '],
            ],
        ];

        $this->postJson('/administracion/productos', $datos)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['variantes.1.etiqueta']);
    }

    public function test_sku_es_unico_global_y_no_se_acepta_activar_desde_endpoint_generico(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $this->actingAs($administrador);
        $producto = Producto::factory()->create();
        VarianteProducto::factory()->create(['producto_id' => $producto->id, 'sku' => 'SKU-RESERVADO', 'activo' => false]);

        $this->postJson('/administracion/productos', [
            'nombre' => 'Nuevo producto',
            'variantes' => [['sku' => 'SKU-RESERVADO', 'precio_base' => '100.00']],
        ])->assertUnprocessable()->assertJsonValidationErrors(['variantes.0.sku']);

        $this->patchJson("/administracion/productos/{$producto->id}", ['activo' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['activo']);

        $variante = $producto->variantes()->firstOrFail();
        $this->patchJson("/administracion/productos/{$producto->id}/variantes/{$variante->id}", ['activo' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['activo']);
    }

    public function test_producto_solo_se_activa_con_categoria_imagen_general_y_variante_activa(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());
        $producto = Producto::factory()->create();
        $variante = VarianteProducto::factory()->create(['producto_id' => $producto->id]);
        $categoria = Categoria::factory()->create(['activo' => false]);
        $producto->categorias()->attach($categoria);
        $this->asignarImagenLista($producto, $variante, true);

        $this->patchJson("/administracion/productos/{$producto->id}/variantes/{$variante->id}/estado", ['activo' => true])
            ->assertOk();

        $this->patchJson("/administracion/productos/{$producto->id}/estado", ['activo' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['activo']);

        $categoria->update(['activo' => true]);
        $this->patchJson("/administracion/productos/{$producto->id}/estado", ['activo' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['activo']);

        $this->asignarImagenLista($producto, null, true);
        $this->patchJson("/administracion/productos/{$producto->id}/estado", ['activo' => true])
            ->assertOk()
            ->assertJsonPath('producto.activo', true);
    }

    public function test_al_activar_segunda_variante_ambas_necesitan_imagenes_propias_listas(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());
        $producto = Producto::factory()->create();
        $primera = VarianteProducto::factory()->create(['producto_id' => $producto->id]);
        $segunda = VarianteProducto::factory()->create(['producto_id' => $producto->id]);
        $this->asignarImagenLista($producto, null, true);
        $this->asignarImagenLista($producto, $primera, true);

        $this->patchJson("/administracion/productos/{$producto->id}/variantes/{$primera->id}/estado", ['activo' => true])
            ->assertOk();

        $this->patchJson("/administracion/productos/{$producto->id}/variantes/{$segunda->id}/estado", ['activo' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['activo']);

        $this->asignarImagenLista($producto, $segunda, true);
        $this->patchJson("/administracion/productos/{$producto->id}/variantes/{$segunda->id}/estado", ['activo' => true])
            ->assertOk()
            ->assertJsonPath('variante.activo', true);
    }

    public function test_producto_con_varias_variantes_activas_exige_imagen_lista_en_cada_una(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());
        $producto = Producto::factory()->create();
        $primera = VarianteProducto::factory()->create(['producto_id' => $producto->id]);
        $segunda = VarianteProducto::factory()->create(['producto_id' => $producto->id]);
        $categoria = Categoria::factory()->create(['activo' => true]);
        $producto->categorias()->attach($categoria);
        $this->asignarImagenLista($producto, null, true);
        $imagenPrimera = $this->asignarImagenLista($producto, $primera, true);
        $this->asignarImagenLista($producto, $segunda, true);

        $this->patchJson("/administracion/productos/{$producto->id}/variantes/{$primera->id}/estado", ['activo' => true])
            ->assertOk();
        $this->patchJson("/administracion/productos/{$producto->id}/variantes/{$segunda->id}/estado", ['activo' => true])
            ->assertOk();

        $imagenPrimera->update(['estado' => 'pendiente']);
        $this->patchJson("/administracion/productos/{$producto->id}/estado", ['activo' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['activo']);

        $imagenPrimera->update(['estado' => 'listo']);
        $this->patchJson("/administracion/productos/{$producto->id}/estado", ['activo' => true])
            ->assertOk();
    }

    public function test_para_varias_imagenes_la_principal_debe_estar_procesada(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());
        $producto = Producto::factory()->create();
        $primera = VarianteProducto::factory()->create(['producto_id' => $producto->id]);
        $segunda = VarianteProducto::factory()->create(['producto_id' => $producto->id]);
        $this->asignarImagenLista($producto, null, true);
        $this->asignarImagenLista($producto, $primera, false);
        $imagenPrincipalPendiente = $this->asignarImagenLista($producto, $primera, true, 'pendiente');
        $this->asignarImagenLista($producto, $segunda, true);

        $this->patchJson("/administracion/productos/{$producto->id}/variantes/{$primera->id}/estado", ['activo' => true])
            ->assertOk();
        $this->patchJson("/administracion/productos/{$producto->id}/variantes/{$segunda->id}/estado", ['activo' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['activo']);

        $imagenPrincipalPendiente->update(['estado' => 'listo']);
        $this->patchJson("/administracion/productos/{$producto->id}/variantes/{$segunda->id}/estado", ['activo' => true])
            ->assertOk();
    }

    public function test_no_se_puede_desactivar_ultima_variante_de_producto_activo(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());
        $producto = Producto::factory()->create();
        $variante = VarianteProducto::factory()->create(['producto_id' => $producto->id]);
        $categoria = Categoria::factory()->create(['activo' => true]);
        $producto->categorias()->attach($categoria);
        $this->asignarImagenLista($producto, null, true);
        $this->asignarImagenLista($producto, $variante, true);

        $this->patchJson("/administracion/productos/{$producto->id}/variantes/{$variante->id}/estado", ['activo' => true])
            ->assertOk();
        $this->patchJson("/administracion/productos/{$producto->id}/estado", ['activo' => true])
            ->assertOk();

        $this->patchJson("/administracion/productos/{$producto->id}/variantes/{$variante->id}/estado", ['activo' => false])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['activo']);
    }

    public function test_disponibilidad_se_puede_cambiar_sin_activar_producto_o_variante(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());
        $producto = Producto::factory()->create();
        $variante = VarianteProducto::factory()->create(['producto_id' => $producto->id]);

        $this->patchJson("/administracion/productos/{$producto->id}/estado", ['disponible' => true])
            ->assertOk()
            ->assertJsonPath('producto.activo', false)
            ->assertJsonPath('producto.disponible', true);

        $this->patchJson("/administracion/productos/{$producto->id}/variantes/{$variante->id}/estado", ['disponible' => true])
            ->assertOk()
            ->assertJsonPath('variante.activo', false)
            ->assertJsonPath('variante.disponible', true);
    }

    public function test_cliente_y_visitante_no_pueden_gestionar_productos(): void
    {
        $producto = Producto::factory()->create();

        $this->getJson('/administracion/productos')->assertUnauthorized();

        $cliente = Usuario::factory()->create();
        $variante = VarianteProducto::factory()->create(['producto_id' => $producto->id]);

        $this->actingAs($cliente)
            ->getJson("/administracion/productos/{$producto->id}")
            ->assertForbidden();
        $this->patchJson("/administracion/productos/{$producto->id}/variantes/{$variante->id}/estado", ['disponible' => true])
            ->assertForbidden();
    }

    private function asignarImagenLista(Producto $producto, ?VarianteProducto $variante, bool $principal, string $estado = 'listo'): ImagenProducto
    {
        $ruta = 'imagenes/'.fake()->uuid().'.webp';
        $archivoId = DB::table('archivos_imagenes_producto')->insertGetId([
            'disco_temporal' => 'local',
            'ruta_temporal' => 'temporal/'.fake()->uuid(),
            'disco' => 'local',
            'ruta' => $ruta,
            'ruta_miniatura' => 'miniaturas/'.$ruta,
            'estado' => $estado,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ImagenProducto::query()->create([
            'producto_id' => $producto->id,
            'variante_producto_id' => $variante?->id,
            'archivo_imagen_producto_id' => $archivoId,
            'ruta' => $ruta,
            'ruta_miniatura' => 'miniaturas/'.$ruta,
            'estado' => $estado,
            'orden' => 0,
            'es_principal' => $principal,
        ]);
    }
}
