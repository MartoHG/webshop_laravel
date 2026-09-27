<?php

namespace Tests\Feature;

use App\Jobs\ProcesarImagenProducto;
use App\Models\ImagenProducto;
use App\Models\Producto;
use App\Models\Usuario;
use App\Models\VarianteProducto;
use App\Servicios\Imagenes\ImagenProductoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ImagenesProductoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Route::has('administracion.imagenes.subir')) {
            require base_path('routes/imagenes.php');
        }

        config(['imagenes.disco' => 'public', 'imagenes.disco_temporal' => 'local']);
        Storage::fake('public');
        Storage::fake('local');
    }

    public function test_upload_se_encola_y_el_job_publica_dos_webp_y_elimina_el_temporal(): void
    {
        Queue::fake();
        $administrador = Usuario::factory()->administrador()->create();
        $producto = Producto::factory()->create();

        $respuesta = $this->actingAs($administrador)->postJson('/administracion/imagenes', [
            'producto_id' => $producto->id,
            'imagen' => UploadedFile::fake()->image('kayak.png', 1600, 900),
            'es_principal' => true,
        ]);

        $respuesta->assertAccepted()
            ->assertJsonPath('imagen.estado', 'pendiente')
            ->assertJsonPath('imagen.es_principal', true);

        $asignacion = ImagenProducto::query()->firstOrFail();
        $archivo = DB::table('archivos_imagenes_producto')->where('id', $asignacion->archivo_imagen_producto_id)->firstOrFail();
        Storage::disk('local')->assertExists($archivo->ruta_temporal);
        Queue::assertPushed(ProcesarImagenProducto::class);

        (new ProcesarImagenProducto((int) $archivo->id))->handle();

        $asignacion->refresh();
        $this->assertSame('listo', $asignacion->estado);
        $this->assertStringEndsWith('.webp', $asignacion->ruta);
        $this->assertStringEndsWith('.webp', $asignacion->ruta_miniatura);
        Storage::disk('local')->assertMissing($archivo->ruta_temporal);
        Storage::disk('public')->assertExists($asignacion->ruta);
        Storage::disk('public')->assertExists($asignacion->ruta_miniatura);
        $this->assertSame('image/webp', getimagesizefromstring(Storage::disk('public')->get($asignacion->ruta))['mime']);
    }

    public function test_un_archivo_listo_se_asigna_a_otro_producto_y_se_borra_solo_al_quitar_la_ultima_referencia(): void
    {
        Queue::fake();
        $administrador = Usuario::factory()->administrador()->create();
        $productoOriginal = Producto::factory()->create();
        $productoDestino = Producto::factory()->create();
        $respuesta = $this->actingAs($administrador)->postJson('/administracion/imagenes', [
            'producto_id' => $productoOriginal->id,
            'imagen' => UploadedFile::fake()->image('producto.png'),
            'es_principal' => true,
        ])->assertAccepted();

        $asignacionOriginal = ImagenProducto::query()->firstOrFail();
        $archivoId = $asignacionOriginal->archivo_imagen_producto_id;
        (new ProcesarImagenProducto($archivoId))->handle();
        $asignacionOriginal->refresh();
        $rutaDetalle = $asignacionOriginal->ruta;
        $rutaMiniatura = $asignacionOriginal->ruta_miniatura;

        $this->postJson('/administracion/imagenes/asignaciones', [
            'archivo_imagen_producto_id' => $archivoId,
            'producto_id' => $productoDestino->id,
            'es_principal' => true,
        ])->assertCreated();

        $segundaAsignacion = ImagenProducto::query()->where('producto_id', $productoDestino->id)->firstOrFail();
        $this->deleteJson("/administracion/imagenes/{$asignacionOriginal->id}")->assertOk();
        Storage::disk('public')->assertExists($rutaDetalle);
        Storage::disk('public')->assertExists($rutaMiniatura);

        $this->deleteJson("/administracion/imagenes/{$segundaAsignacion->id}")->assertOk();
        Storage::disk('public')->assertMissing($rutaDetalle);
        Storage::disk('public')->assertMissing($rutaMiniatura);
        $this->assertDatabaseMissing('archivos_imagenes_producto', ['id' => $archivoId]);
    }

    public function test_expone_el_fallo_y_permite_reencolar_sin_perder_el_temporal(): void
    {
        Queue::fake();
        $administrador = Usuario::factory()->administrador()->create();
        $producto = Producto::factory()->create();
        $this->actingAs($administrador)->postJson('/administracion/imagenes', [
            'producto_id' => $producto->id,
            'imagen' => UploadedFile::fake()->image('producto.png'),
        ])->assertAccepted();

        $imagen = ImagenProducto::query()->firstOrFail();
        $archivo = DB::table('archivos_imagenes_producto')->where('id', $imagen->archivo_imagen_producto_id)->firstOrFail();
        Storage::disk('local')->put($archivo->ruta_temporal, 'contenido inválido');
        $trabajo = new ProcesarImagenProducto((int) $archivo->id);

        try {
            $trabajo->handle();
            $this->fail('Debió fallar el archivo inválido.');
        } catch (\RuntimeException $exception) {
            $trabajo->failed($exception);
        }

        $this->getJson("/administracion/imagenes/{$imagen->id}")
            ->assertOk()
            ->assertJsonPath('imagen.estado', 'fallido')
            ->assertJsonPath('imagen.error', 'El archivo no es una imagen JPEG, PNG o WebP válida.');
        Storage::disk('local')->assertExists($archivo->ruta_temporal);

        $this->postJson("/administracion/imagenes/archivos/{$archivo->id}/reintentar")
            ->assertAccepted();
        Queue::assertPushed(ProcesarImagenProducto::class);
    }

    public function test_rechaza_archivo_duplicado_en_ambito_general_y_variante_de_otro_producto(): void
    {
        Queue::fake();
        $administrador = Usuario::factory()->administrador()->create();
        $producto = Producto::factory()->create();
        $otroProducto = Producto::factory()->create();
        $varianteAjena = VarianteProducto::factory()->create(['producto_id' => $otroProducto->id]);
        $this->actingAs($administrador)->postJson('/administracion/imagenes', [
            'producto_id' => $producto->id,
            'imagen' => UploadedFile::fake()->image('producto.png'),
        ])->assertAccepted();

        $asignacion = ImagenProducto::query()->firstOrFail();
        $archivoId = $asignacion->archivo_imagen_producto_id;
        (new ProcesarImagenProducto($archivoId))->handle();

        $this->postJson('/administracion/imagenes/asignaciones', [
            'archivo_imagen_producto_id' => $archivoId,
            'producto_id' => $producto->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('archivo_imagen_producto_id');

        $this->postJson('/administracion/imagenes/asignaciones', [
            'archivo_imagen_producto_id' => $archivoId,
            'producto_id' => $producto->id,
            'variante_producto_id' => $varianteAjena->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('variante_producto_id');
    }

    public function test_no_quita_principal_de_producto_activo_y_promueve_la_siguiente_imagen(): void
    {
        $producto = Producto::factory()->create(['activo' => true]);
        $principal = $this->crearAsignacionLista($producto, null, true, 'a');
        $alternativa = $this->crearAsignacionLista($producto, null, false, 'b');
        $servicio = app(ImagenProductoService::class);

        try {
            $servicio->eliminar($principal);
            $this->fail('Debió impedir quitar la imagen principal de un producto activo.');
        } catch (ValidationException) {
            $this->assertDatabaseHas('imagenes_producto', ['id' => $principal->id]);
        }

        $producto->forceFill(['activo' => false])->save();
        $servicio->eliminar($principal);
        $this->assertDatabaseHas('imagenes_producto', ['id' => $alternativa->id, 'es_principal' => true]);
    }

    public function test_no_quita_la_unica_imagen_general_que_usa_la_unica_variante_activa(): void
    {
        $producto = Producto::factory()->create();
        $variante = VarianteProducto::factory()->create(['producto_id' => $producto->id, 'activo' => true]);
        $imagenGeneral = $this->crearAsignacionLista($producto, null, true, 'c');

        try {
            app(ImagenProductoService::class)->eliminar($imagenGeneral);
            $this->fail('Debió proteger la imagen general utilizada por la variante activa.');
        } catch (ValidationException) {
            $this->assertDatabaseHas('imagenes_producto', ['id' => $imagenGeneral->id]);
            $this->assertDatabaseHas('variantes_producto', ['id' => $variante->id, 'activo' => true]);
        }
    }

    public function test_reordena_imagenes_y_cambia_la_principal_dentro_de_su_ambito(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $producto = Producto::factory()->create();
        $primera = $this->crearAsignacionLista($producto, null, true, 'd');
        $segunda = $this->crearAsignacionLista($producto, null, false, 'e');

        $this->actingAs($administrador)->putJson('/administracion/imagenes/orden', [
            'producto_id' => $producto->id,
            'imagenes' => [
                ['id' => $segunda->id, 'orden' => 0],
                ['id' => $primera->id, 'orden' => 1],
            ],
        ])->assertOk();

        $this->patchJson("/administracion/imagenes/{$segunda->id}/principal")->assertOk();
        $this->assertDatabaseHas('imagenes_producto', ['id' => $segunda->id, 'orden' => 0, 'es_principal' => true]);
        $this->assertDatabaseHas('imagenes_producto', ['id' => $primera->id, 'orden' => 1, 'es_principal' => false]);
    }

    private function crearAsignacionLista(Producto $producto, ?VarianteProducto $variante, bool $principal, string $identificador): ImagenProducto
    {
        $ruta = 'imagenes-producto/'.$identificador.'/detalle.webp';
        $archivoId = DB::table('archivos_imagenes_producto')->insertGetId([
            'disco_temporal' => 'local',
            'ruta_temporal' => 'imagenes-producto/temporales/'.$identificador.'.png',
            'disco' => 'public',
            'ruta' => $ruta,
            'ruta_miniatura' => 'imagenes-producto/'.$identificador.'/miniatura.webp',
            'estado' => 'listo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ImagenProducto::query()->create([
            'producto_id' => $producto->id,
            'variante_producto_id' => $variante?->id,
            'archivo_imagen_producto_id' => $archivoId,
            'ruta' => $ruta,
            'ruta_miniatura' => 'imagenes-producto/'.$identificador.'/miniatura.webp',
            'estado' => 'listo',
            'orden' => 0,
            'es_principal' => $principal,
        ]);
    }
}
