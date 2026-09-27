<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CategoriasTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_crear_categoria_con_slug_generado(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());

        $response = $this->postJson('/administracion/categorias', ['nombre' => 'Muebles de jardín']);

        $response->assertCreated()
            ->assertJsonPath('categoria.nombre', 'Muebles de jardín')
            ->assertJsonPath('categoria.slug', 'muebles-de-jardin')
            ->assertJsonPath('categoria.activo', true);
        $this->assertDatabaseHas('categorias', ['slug' => 'muebles-de-jardin', 'clave_sistema' => null]);
    }

    public function test_cliente_no_puede_administrar_categorias(): void
    {
        $this->actingAs(Usuario::factory()->create());

        $this->getJson('/administracion/categorias')->assertForbidden();
    }

    public function test_categoria_no_se_desactiva_si_deja_sin_categoria_activa_a_un_producto_activo(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());
        $categoria = Categoria::factory()->create();
        $productoId = $this->crearProducto(true);
        DB::table('categoria_producto')->insert(['categoria_id' => $categoria->id, 'producto_id' => $productoId]);

        $this->patchJson("/administracion/categorias/{$categoria->id}/estado", ['activo' => false])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('activo');

        $this->assertDatabaseHas('categorias', ['id' => $categoria->id, 'activo' => true]);
    }

    public function test_producto_activo_no_puede_perder_su_ultima_categoria_activa(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());
        $categoria = Categoria::factory()->create();
        $productoId = $this->crearProducto(true);
        DB::table('categoria_producto')->insert(['categoria_id' => $categoria->id, 'producto_id' => $productoId]);

        $this->deleteJson("/administracion/productos/{$productoId}/categorias/{$categoria->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('categoria_id');

        $this->assertDatabaseHas('categoria_producto', ['categoria_id' => $categoria->id, 'producto_id' => $productoId]);
    }

    public function test_ofertas_se_siembra_por_clave_estable_y_puede_desactivarse(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());
        $this->seed();
        $ofertas = Categoria::query()->where('clave_sistema', 'ofertas')->sole();

        $this->patchJson("/administracion/categorias/{$ofertas->id}/estado", ['activo' => false])
            ->assertOk()
            ->assertJsonPath('categoria.clave_sistema', 'ofertas')
            ->assertJsonPath('categoria.activo', false);

        $ofertas->update(['orden' => 7]);
        $this->seed();
        $this->assertDatabaseHas('categorias', [
            'id' => $ofertas->id,
            'nombre' => 'Ofertas',
            'slug' => 'ofertas',
            'clave_sistema' => 'ofertas',
            'activo' => false,
            'orden' => 7,
        ]);

        $this->patchJson("/administracion/categorias/{$ofertas->id}", ['nombre' => 'Liquidación'])
            ->assertUnprocessable();
    }

    private function crearProducto(bool $activo): int
    {
        return DB::table('productos')->insertGetId([
            'nombre' => 'Producto de prueba',
            'slug' => 'producto-prueba-'.uniqid(),
            'activo' => $activo,
            'disponible' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
