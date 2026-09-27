<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Usuario;
use App\Models\VarianteProducto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PreciosCatalogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrador_configura_el_ajuste_y_un_cliente_no_puede_modificarlo(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());

        $this->getJson('/administracion/precios/ajuste-ecommerce')
            ->assertOk()
            ->assertJsonPath('ajuste_configurado', false)
            ->assertJsonPath('porcentaje', null);

        $this->putJson('/administracion/precios/ajuste-ecommerce', ['porcentaje' => 9.25])
            ->assertOk()
            ->assertJsonPath('ajuste_configurado', true)
            ->assertJsonPath('porcentaje', '9.25');

        $this->assertDatabaseHas('configuraciones', [
            'clave' => 'ajuste_ecommerce_porcentaje',
            'valor' => '9.25',
        ]);

        $this->actingAs(Usuario::factory()->create())
            ->putJson('/administracion/precios/ajuste-ecommerce', ['porcentaje' => '10'])
            ->assertForbidden();
    }

    public function test_ajuste_rechaza_porcentajes_negativos_o_fuera_de_precision_de_almacenamiento(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());

        $this->putJson('/administracion/precios/ajuste-ecommerce', ['porcentaje' => '-1'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('porcentaje');

        $this->putJson('/administracion/precios/ajuste-ecommerce', ['porcentaje' => '1000000'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('porcentaje');

        $this->putJson('/administracion/precios/ajuste-ecommerce', ['porcentaje' => '1.1234567'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('porcentaje');
    }

    public function test_ajuste_admite_cero_y_decimales_json_hasta_seis_lugares(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());

        $this->putJson('/administracion/precios/ajuste-ecommerce', ['porcentaje' => 0])
            ->assertOk()
            ->assertJsonPath('porcentaje', '0');

        $this->putJson('/administracion/precios/ajuste-ecommerce', ['porcentaje' => 9.123456])
            ->assertOk()
            ->assertJsonPath('porcentaje', '9.123456');
    }

    public function test_catalogo_informa_que_falta_configurar_el_ajuste(): void
    {
        $this->getJson('/catalogo')->assertServiceUnavailable()
            ->assertJsonPath('codigo', 'ajuste_ecommerce_no_configurado');

        $this->getJson('/catalogo/producto-prueba')->assertServiceUnavailable()
            ->assertJsonPath('codigo', 'ajuste_ecommerce_no_configurado');
    }

    public function test_catalogo_calcula_precio_con_redondeo_half_up_y_no_expone_precio_base(): void
    {
        $this->configurarAjuste('10');
        $producto = $this->crearProducto('farol', true, true);
        $this->hacerPublicable($producto);
        VarianteProducto::factory()->create([
            'producto_id' => $producto->id,
            'sku' => 'FAROL-001',
            'precio_base' => '0.05',
            'activo' => true,
            'disponible' => true,
        ]);

        Storage::fake('public');

        $this->getJson('/catalogo/farol')
            ->assertOk()
            ->assertJsonPath('producto.variantes.0.precio_publicado', '0.06')
            ->assertJsonPath('producto.imagenes.0.url', Storage::disk('public')->url('productos/foto.jpg'))
            ->assertJsonPath('producto.imagenes.0.url_miniatura', Storage::disk('public')->url('productos/foto-miniatura.jpg'))
            ->assertJsonMissingPath('producto.variantes.0.precio_base');
    }

    public function test_inicio_oculta_agotados_mientras_busqueda_los_muestra_y_no_filtra_categoria_inactiva(): void
    {
        $this->configurarAjuste('0');
        $agotado = $this->crearProducto('kayak-agotado', false, true, 'Kayak agotado');
        VarianteProducto::factory()->create([
            'producto_id' => $agotado->id,
            'sku' => 'KAYAK-AGOTADO',
            'precio_base' => '100.00',
            'activo' => true,
            'disponible' => false,
        ]);
        $categoriaActiva = Categoria::factory()->create(['slug' => 'kayaks-activos', 'activo' => true]);
        $categoriaInactiva = Categoria::factory()->create(['slug' => 'kayaks-ocultos', 'activo' => false]);
        $agotado->categorias()->attach([$categoriaActiva->id, $categoriaInactiva->id]);
        $this->agregarImagenPrincipalLista($agotado);

        $this->getJson('/catalogo?contexto=inicio')
            ->assertOk()
            ->assertJsonCount(0, 'productos');

        $this->getJson('/catalogo?buscar=kayak')
            ->assertOk()
            ->assertJsonCount(1, 'productos')
            ->assertJsonPath('productos.0.disponible', false)
            ->assertJsonCount(1, 'productos.0.categorias');

        $this->getJson('/catalogo?categoria=kayaks-ocultos')
            ->assertOk()
            ->assertJsonCount(0, 'productos');

        $this->getJson('/catalogo/categorias')
            ->assertOk()
            ->assertJsonCount(1, 'categorias')
            ->assertJsonPath('categorias.0.slug', 'kayaks-activos');
    }

    private function configurarAjuste(string $porcentaje): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create())
            ->putJson('/administracion/precios/ajuste-ecommerce', ['porcentaje' => $porcentaje])
            ->assertOk();

        auth()->logout();
    }

    private function hacerPublicable(Producto $producto): void
    {
        $producto->categorias()->attach(Categoria::factory()->create(['activo' => true]));
        $this->agregarImagenPrincipalLista($producto);
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

    private function crearProducto(string $slug, bool $disponible, bool $activo, ?string $nombre = null): Producto
    {
        $producto = Producto::factory()->create([
            'slug' => $slug,
            'nombre' => $nombre ?? 'Producto de prueba',
            'activo' => $activo,
            'disponible' => $disponible,
        ]);

        return $producto;
    }
}
