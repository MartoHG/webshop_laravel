<?php

namespace Tests\Feature;

use App\Jobs\ProcesarImagenContenido;
use App\Models\Categoria;
use App\Models\Configuracion;
use App\Models\ImagenContenido;
use App\Models\RegistroAuditoria;
use App\Models\Usuario;
use App\Servicios\Contenido\PortadaService;
use App\Servicios\Imagenes\ProcesadorImagenes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ContenidoPortadaCategoriasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['imagenes.disco' => 'public', 'imagenes.disco_temporal' => 'local']);
        Storage::fake('public');
        Storage::fake('local');
    }

    public function test_procesador_compartido_no_cambia_las_imagenes_de_producto(): void
    {
        $archivo = UploadedFile::fake()->image('kayak.png', 1600, 900);
        $original = file_get_contents($archivo->getRealPath());

        [$miniatura] = app(ProcesadorImagenes::class)->convertirWebp($original, [
            ['maximo_ancho' => 480, 'maximo_alto' => 480, 'calidad' => 78],
        ]);

        $datos = getimagesizefromstring($miniatura);
        $this->assertSame('image/webp', $datos['mime']);
        $this->assertSame([480, 270], [$datos[0], $datos[1]]);
    }

    public function test_subir_portada_encola_y_el_job_publica_webp_y_borra_el_original(): void
    {
        Queue::fake();
        $administrador = Usuario::factory()->administrador()->create();

        $this->actingAs($administrador)->postJson('/administracion/contenido/portada/imagen', [
            'imagen' => UploadedFile::fake()->image('portada.png', 2000, 1000),
        ])->assertAccepted()->assertJsonPath('imagen.estado', 'pendiente');

        $imagen = ImagenContenido::query()->sole();
        $rutaTemporal = $imagen->ruta_temporal;
        Storage::disk('local')->assertExists($rutaTemporal);
        Queue::assertPushed(ProcesarImagenContenido::class);

        (new ProcesarImagenContenido($imagen->id))->handle();

        $imagen->refresh();
        $this->assertSame('listo', $imagen->estado);
        $this->assertNull($imagen->ruta_temporal);
        Storage::disk('local')->assertMissing($rutaTemporal);
        Storage::disk('public')->assertExists($imagen->ruta);
        Storage::disk('public')->assertExists($imagen->ruta_miniatura);

        $detalle = getimagesizefromstring(Storage::disk('public')->get($imagen->ruta));
        $miniatura = getimagesizefromstring(Storage::disk('public')->get($imagen->ruta_miniatura));
        $this->assertSame('image/webp', $detalle['mime']);
        $this->assertSame([2000, 1000], [$detalle[0], $detalle[1]]);
        $this->assertSame('image/webp', $miniatura['mime']);
        $this->assertSame([960, 480], [$miniatura[0], $miniatura[1]]);
    }

    public function test_portada_se_muestra_solo_con_titulo_e_imagen_lista(): void
    {
        $servicio = app(PortadaService::class);
        $imagen = ImagenContenido::factory()->listo()->create(['tipo' => ImagenContenido::TIPO_PORTADA]);
        $this->asignarImagenPortada($imagen);

        $this->assertNull($servicio->obtener());

        $servicio->actualizarTextos(['titulo' => 'Verano en el jardín', 'subtitulo' => 'Muebles para el aire libre']);

        $portada = $servicio->obtener();
        $this->assertSame('Verano en el jardín', $portada['titulo']);
        $this->assertSame('Muebles para el aire libre', $portada['subtitulo']);
        $this->assertNull($portada['boton_texto']);
        $this->assertStringContainsString($imagen->ruta, $portada['imagen_url']);
        $this->assertStringContainsString($imagen->ruta_miniatura, $portada['imagen_miniatura_url']);

        $imagen->forceFill(['estado' => 'procesando'])->save();
        $this->assertNull($servicio->obtener());
    }

    public function test_boton_de_portada_acepta_solo_rutas_internas(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());

        $destinosInvalidos = ['https://ejemplo.com', '//ejemplo.com', '/\\ejemplo.com', 'catalogo', '/con espacios'];

        foreach ($destinosInvalidos as $destino) {
            $this->patchJson('/administracion/contenido/portada', [
                'titulo' => 'Portada',
                'boton_texto' => 'Ver productos',
                'boton_destino' => $destino,
            ])->assertUnprocessable()->assertJsonValidationErrors('boton_destino');
        }

        $this->patchJson('/administracion/contenido/portada', [
            'titulo' => 'Portada',
            'boton_texto' => 'Ver productos',
        ])->assertUnprocessable()->assertJsonValidationErrors(['boton_texto', 'boton_destino']);

        $this->patchJson('/administracion/contenido/portada', [
            'titulo' => 'Portada',
            'boton_texto' => 'Ver productos',
            'boton_destino' => '/catalogo/sillas',
        ])->assertOk()->assertJsonPath('portada.boton_destino', '/catalogo/sillas');

        $this->assertDatabaseHas('configuraciones', ['clave' => 'portada.boton_destino', 'valor' => '/catalogo/sillas']);
    }

    public function test_cambiar_y_quitar_la_portada_borra_la_imagen_anterior(): void
    {
        Queue::fake();
        $administrador = Usuario::factory()->administrador()->create();

        $this->actingAs($administrador)->postJson('/administracion/contenido/portada/imagen', [
            'imagen' => UploadedFile::fake()->image('uno.png', 800, 600),
        ])->assertAccepted();
        $primera = ImagenContenido::query()->sole();
        (new ProcesarImagenContenido($primera->id))->handle();
        $primera->refresh();

        $this->actingAs($administrador)->postJson('/administracion/contenido/portada/imagen', [
            'imagen' => UploadedFile::fake()->image('dos.png', 800, 600),
        ])->assertAccepted();
        $segunda = ImagenContenido::query()->whereKeyNot($primera->id)->sole();
        (new ProcesarImagenContenido($segunda->id))->handle();
        $segunda->refresh();

        $this->assertDatabaseMissing('imagenes_contenido', ['id' => $primera->id]);
        Storage::disk('public')->assertMissing($primera->ruta);
        Storage::disk('public')->assertMissing($primera->ruta_miniatura);
        Storage::disk('public')->assertExists($segunda->ruta);
        $this->assertDatabaseHas('configuraciones', ['clave' => 'portada.imagen_id', 'valor' => (string) $segunda->id]);

        $this->actingAs($administrador)->patchJson('/administracion/contenido/portada', ['titulo' => 'Portada'])->assertOk();
        $this->actingAs($administrador)->deleteJson('/administracion/contenido/portada')->assertOk();

        $this->assertDatabaseMissing('imagenes_contenido', ['id' => $segunda->id]);
        Storage::disk('public')->assertMissing($segunda->ruta);
        Storage::disk('public')->assertMissing($segunda->ruta_miniatura);
        $this->assertDatabaseHas('configuraciones', ['clave' => 'portada.titulo', 'valor' => null]);
        $this->assertDatabaseHas('configuraciones', ['clave' => 'portada.imagen_id', 'valor' => null]);
        $this->assertDatabaseHas('registros_auditoria', ['accion' => 'contenido.portada_actualizada']);
    }

    public function test_categoria_recibe_foto_y_se_marca_destacada_y_queda_auditado(): void
    {
        Queue::fake();
        $administrador = Usuario::factory()->administrador()->create();
        $categoria = Categoria::factory()->create(['nombre' => 'Sillas', 'slug' => 'sillas']);
        Configuracion::query()->create(['clave' => 'ajuste_ecommerce_porcentaje', 'valor' => '10']);

        $this->actingAs($administrador)->postJson("/administracion/categorias/{$categoria->id}/imagen", [
            'imagen' => UploadedFile::fake()->image('silla.png', 900, 1200),
        ])->assertAccepted();

        $imagen = ImagenContenido::query()->sole();
        (new ProcesarImagenContenido($imagen->id))->handle();

        $this->actingAs($administrador)->patchJson("/administracion/categorias/{$categoria->id}/destacada", [
            'destacada_en_home' => true,
        ])->assertOk()->assertJsonPath('categoria.destacada_en_home', true);

        $this->assertDatabaseHas('categorias', [
            'id' => $categoria->id,
            'imagen_contenido_id' => $imagen->id,
            'destacada_en_home' => true,
        ]);

        $listadoAdmin = $this->actingAs($administrador)->getJson('/administracion/categorias')
            ->assertOk()
            ->assertJsonPath('categorias.0.destacada_en_home', true);
        $this->assertNotNull($listadoAdmin->json('categorias.0.imagen_url'));

        $listadoPublico = $this->getJson('/catalogo/categorias')
            ->assertOk()
            ->assertJsonPath('categorias.0.slug', 'sillas')
            ->assertJsonPath('categorias.0.destacada_en_home', true);
        $this->assertNotNull($listadoPublico->json('categorias.0.imagen_miniatura_url'));

        $this->assertDatabaseHas('registros_auditoria', [
            'accion' => 'categoria.imagen_actualizada',
            'entidad' => 'categorias',
            'entidad_id' => $categoria->id,
        ]);
        $this->assertSame(
            ['destacada_en_home' => true],
            RegistroAuditoria::query()->where('accion', 'categoria.destacada_actualizada')->sole()->datos_nuevos,
        );
    }

    public function test_imagen_fallida_se_puede_reintentar(): void
    {
        Queue::fake();
        $administrador = Usuario::factory()->administrador()->create();
        $this->actingAs($administrador)->postJson('/administracion/contenido/portada/imagen', [
            'imagen' => UploadedFile::fake()->image('mal.png'),
        ])->assertAccepted();

        $imagen = ImagenContenido::query()->sole();
        Storage::disk('local')->put($imagen->ruta_temporal, 'contenido inválido');
        $trabajo = new ProcesarImagenContenido($imagen->id);

        try {
            $trabajo->handle();
            $this->fail('Debió fallar el archivo inválido.');
        } catch (RuntimeException $exception) {
            $trabajo->failed($exception);
        }

        $this->actingAs($administrador)->getJson('/administracion/contenido/portada')
            ->assertOk()
            ->assertJsonPath('portada.imagen.estado', 'fallido')
            ->assertJsonPath('portada.imagen.error', 'El archivo no es una imagen JPEG, PNG o WebP válida.');
        Storage::disk('local')->assertExists($imagen->ruta_temporal);

        $this->actingAs($administrador)->postJson("/administracion/contenido/imagenes/{$imagen->id}/reintentar")
            ->assertAccepted();

        $this->assertDatabaseHas('imagenes_contenido', ['id' => $imagen->id, 'estado' => 'pendiente', 'error' => null]);
        Queue::assertPushed(ProcesarImagenContenido::class, fn (ProcesarImagenContenido $job): bool => $job->imagenContenidoId === $imagen->id);
    }

    public function test_cliente_no_puede_editar_el_contenido(): void
    {
        $cliente = Usuario::factory()->create();
        $imagen = ImagenContenido::factory()->listo()->create(['tipo' => ImagenContenido::TIPO_PORTADA]);
        $categoria = Categoria::factory()->create();
        $this->actingAs($cliente);

        $this->getJson('/administracion/contenido/portada')->assertForbidden();
        $this->patchJson('/administracion/contenido/portada', ['titulo' => 'Hackeo'])->assertForbidden();
        $this->postJson('/administracion/contenido/portada/imagen', [
            'imagen' => UploadedFile::fake()->image('x.png'),
        ])->assertForbidden();
        $this->deleteJson('/administracion/contenido/portada')->assertForbidden();
        $this->postJson("/administracion/contenido/imagenes/{$imagen->id}/reintentar")->assertForbidden();
        $this->postJson("/administracion/categorias/{$categoria->id}/imagen", [
            'imagen' => UploadedFile::fake()->image('x.png'),
        ])->assertForbidden();
        $this->deleteJson("/administracion/categorias/{$categoria->id}/imagen")->assertForbidden();
        $this->patchJson("/administracion/categorias/{$categoria->id}/destacada", ['destacada_en_home' => true])->assertForbidden();

        $this->assertDatabaseMissing('configuraciones', ['clave' => 'portada.titulo']);
        $this->assertDatabaseHas('categorias', ['id' => $categoria->id, 'destacada_en_home' => false]);
    }

    private function asignarImagenPortada(ImagenContenido $imagen): void
    {
        Configuracion::query()->updateOrCreate(['clave' => 'portada.imagen_id'], ['valor' => (string) $imagen->id]);
    }
}
