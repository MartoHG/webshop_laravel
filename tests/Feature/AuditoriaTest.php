<?php

namespace Tests\Feature;

use App\Models\Configuracion;
use App\Models\Producto;
use App\Models\RegistroAuditoria;
use App\Models\Usuario;
use App\Models\VarianteProducto;
use App\Servicios\Auditoria\AuditoriaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use LogicException;
use Tests\TestCase;

class AuditoriaTest extends TestCase
{
    use RefreshDatabase;

    public function test_registro_de_auditoria_no_se_puede_modificar_ni_eliminar(): void
    {
        $registro = RegistroAuditoria::factory()->create(['accion' => 'producto.estado_actualizado']);

        $this->assertThrows(fn () => $registro->update(['accion' => 'alterada']), LogicException::class);
        $this->assertThrows(fn () => $registro->delete(), LogicException::class);

        $this->assertDatabaseHas('registros_auditoria', ['id' => $registro->id, 'accion' => 'producto.estado_actualizado']);
    }

    public function test_registro_de_auditoria_no_guarda_campos_sensibles(): void
    {
        $administrador = Usuario::factory()->administrador()->create();

        $registro = app(AuditoriaService::class)->registrar(
            'prueba.sensible',
            $administrador,
            ['password' => 'anterior', 'nombre' => 'Ana'],
            [
                'remember_token' => 'token-largo',
                'api_token' => 'otro-token',
                'clave_secreta' => 'valor',
                'client_secret' => 'valor',
                'activo' => true,
                'configuracion' => ['client_secret' => 'valor', 'porcentaje' => '9'],
            ],
        );

        $this->assertSame(['nombre' => 'Ana'], $registro->datos_anteriores);
        $this->assertEqualsCanonicalizing(['activo' => true, 'configuracion' => ['porcentaje' => '9']], $registro->datos_nuevos);
    }

    public function test_cambio_de_ajuste_ecommerce_queda_auditado_con_usuario_e_ip(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10']);

        $this->actingAs($administrador)->putJson('/administracion/precios/ajuste-ecommerce', ['porcentaje' => '9.25'])->assertOk();
        $this->actingAs($administrador)->putJson('/administracion/precios/ajuste-ecommerce', ['porcentaje' => '10'])->assertOk();
        $this->actingAs($administrador)->putJson('/administracion/precios/ajuste-ecommerce', ['porcentaje' => '10.000'])->assertOk();

        $configuracion = Configuracion::query()->where('clave', 'ajuste_ecommerce_porcentaje')->sole();
        $registros = RegistroAuditoria::query()
            ->where('accion', 'configuracion.ajuste_ecommerce_actualizado')
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $registros);
        $this->assertSame('configuraciones', $registros[0]->entidad);
        $this->assertSame($configuracion->id, $registros[0]->entidad_id);
        $this->assertSame($administrador->id, $registros[0]->usuario_id);
        $this->assertSame('203.0.113.10', $registros[0]->ip);
        $this->assertSame(['porcentaje' => null], $registros[0]->datos_anteriores);
        $this->assertSame(['porcentaje' => '9.25'], $registros[0]->datos_nuevos);
        $this->assertSame(['porcentaje' => '9.25'], $registros[1]->datos_anteriores);
        $this->assertSame(['porcentaje' => '10'], $registros[1]->datos_nuevos);
    }

    public function test_cambio_de_precio_base_queda_auditado_y_guardar_sin_cambios_no_registra(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $producto = Producto::factory()->create(['activo' => false, 'disponible' => false]);
        $variante = VarianteProducto::factory()->create([
            'producto_id' => $producto->id,
            'sku' => 'SKU-AUDITORIA-1',
            'precio_base' => '100.00',
        ]);
        $ruta = "/administracion/productos/{$producto->id}/variantes/{$variante->id}";

        $this->actingAs($administrador)
            ->patchJson($ruta, ['sku' => 'SKU-AUDITORIA-1', 'precio_base' => '100.00'])
            ->assertOk();
        $this->assertDatabaseCount('registros_auditoria', 0);

        $this->actingAs($administrador)
            ->patchJson($ruta, ['sku' => 'SKU-AUDITORIA-1', 'precio_base' => '120.5'])
            ->assertOk();

        $registro = RegistroAuditoria::query()->sole();
        $this->assertSame('variante_producto.precio_base_actualizado', $registro->accion);
        $this->assertSame('variantes_producto', $registro->entidad);
        $this->assertSame($variante->id, $registro->entidad_id);
        $this->assertSame($administrador->id, $registro->usuario_id);
        $this->assertSame(['precio_base' => '100.00'], $registro->datos_anteriores);
        $this->assertSame(['precio_base' => '120.50'], $registro->datos_nuevos);
    }

    public function test_cambio_de_estado_de_producto_queda_auditado(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $producto = Producto::factory()->create(['activo' => false, 'disponible' => false]);
        $variante = VarianteProducto::factory()->create([
            'producto_id' => $producto->id,
            'activo' => true,
            'disponible' => false,
        ]);

        $this->actingAs($administrador)
            ->patchJson("/administracion/productos/{$producto->id}/estado", ['disponible' => true])
            ->assertOk();
        $this->actingAs($administrador)
            ->patchJson("/administracion/productos/{$producto->id}/variantes/{$variante->id}/estado", ['activo' => false, 'disponible' => false])
            ->assertOk();

        $registros = RegistroAuditoria::query()->orderBy('id')->get();
        $this->assertCount(2, $registros);

        $this->assertSame('producto.estado_actualizado', $registros[0]->accion);
        $this->assertSame('productos', $registros[0]->entidad);
        $this->assertSame($producto->id, $registros[0]->entidad_id);
        $this->assertSame(['disponible' => false], $registros[0]->datos_anteriores);
        $this->assertSame(['disponible' => true], $registros[0]->datos_nuevos);

        $this->assertSame('variante_producto.estado_actualizado', $registros[1]->accion);
        $this->assertSame($variante->id, $registros[1]->entidad_id);
        $this->assertSame(['activo' => true], $registros[1]->datos_anteriores);
        $this->assertSame(['activo' => false], $registros[1]->datos_nuevos);
    }

    public function test_crear_administrador_y_desactivar_usuario_quedan_auditados_sin_datos_sensibles(): void
    {
        Notification::fake();
        $administrador = Usuario::factory()->administrador()->create();

        $this->actingAs($administrador)->postJson('/administracion/usuarios/administradores', [
            'nombre' => 'Ana',
            'apellido' => 'García',
            'dni' => '30123456',
            'email' => 'ana@example.test',
            'telefono' => '2284555123',
        ])->assertCreated();

        $nuevoAdministrador = Usuario::query()->where('email', 'ana@example.test')->firstOrFail();

        $this->actingAs($administrador)
            ->patchJson("/administracion/usuarios/{$nuevoAdministrador->id}/estado", ['activo' => false])
            ->assertOk();

        $creacion = RegistroAuditoria::query()->where('accion', 'usuario.administrador_creado')->sole();
        $this->assertSame('usuarios', $creacion->entidad);
        $this->assertSame($nuevoAdministrador->id, $creacion->entidad_id);
        $this->assertSame($administrador->id, $creacion->usuario_id);
        $this->assertNull($creacion->datos_anteriores);
        $this->assertEqualsCanonicalizing([
            'nombre' => 'Ana',
            'apellido' => 'García',
            'email' => 'ana@example.test',
            'rol' => 'administrador',
        ], $creacion->datos_nuevos);

        $desactivacion = RegistroAuditoria::query()->where('accion', 'usuario.estado_actualizado')->sole();
        $this->assertSame($nuevoAdministrador->id, $desactivacion->entidad_id);
        $this->assertSame($administrador->id, $desactivacion->usuario_id);
        $this->assertSame(['activo' => true], $desactivacion->datos_anteriores);
        $this->assertSame(['activo' => false], $desactivacion->datos_nuevos);
    }

    public function test_comando_crear_administrador_queda_auditado_sin_usuario_ni_ip(): void
    {
        $this->artisan('usuario:crear-administrador')
            ->expectsQuestion('Nombre', 'Ana')
            ->expectsQuestion('Apellido', 'García')
            ->expectsQuestion('DNI', '30123456')
            ->expectsQuestion('Email', 'ana@example.test')
            ->expectsQuestion('Teléfono / WhatsApp', '2284555123')
            ->expectsQuestion('Contraseña', 'clave-segura-de-prueba')
            ->expectsQuestion('Confirmar contraseña', 'clave-segura-de-prueba')
            ->assertSuccessful();

        $registro = RegistroAuditoria::query()->sole();
        $this->assertSame('usuario.administrador_creado', $registro->accion);
        $this->assertNull($registro->usuario_id);
        $this->assertNull($registro->ip);
        $this->assertEqualsCanonicalizing([
            'nombre' => 'Ana',
            'apellido' => 'García',
            'email' => 'ana@example.test',
            'rol' => 'administrador',
        ], $registro->datos_nuevos);
    }

    public function test_cambio_rechazado_por_regla_de_negocio_no_deja_registro(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $producto = Producto::factory()->create(['activo' => false, 'disponible' => false]);

        $this->actingAs($administrador)
            ->patchJson("/administracion/productos/{$producto->id}/estado", ['activo' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('activo');

        $this->actingAs($administrador)
            ->patchJson("/administracion/usuarios/{$administrador->id}/estado", ['activo' => false])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('activo');

        Password::shouldReceive('sendResetLink')
            ->once()
            ->andReturn(Password::RESET_THROTTLED);

        $this->actingAs($administrador)->postJson('/administracion/usuarios/administradores', [
            'nombre' => 'Ana',
            'apellido' => 'García',
            'dni' => '30123456',
            'email' => 'ana@example.test',
            'telefono' => '2284555123',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('registros_auditoria', 0);
        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'activo' => false]);
    }

    public function test_solo_un_administrador_puede_listar_y_filtrar_la_auditoria(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $otroAdministrador = Usuario::factory()->administrador()->create();
        $cliente = Usuario::factory()->create();

        RegistroAuditoria::factory()->create([
            'usuario_id' => $otroAdministrador->id,
            'entidad' => 'productos',
            'entidad_id' => 5,
            'accion' => 'producto.estado_actualizado',
            'created_at' => now()->subDays(10),
        ]);
        RegistroAuditoria::factory()->create([
            'usuario_id' => $administrador->id,
            'entidad' => 'configuraciones',
            'entidad_id' => 1,
            'accion' => 'configuracion.ajuste_ecommerce_actualizado',
            'created_at' => now()->subDays(2),
        ]);
        RegistroAuditoria::factory()->create([
            'usuario_id' => $otroAdministrador->id,
            'entidad' => 'productos',
            'entidad_id' => 6,
            'accion' => 'producto.estado_actualizado',
            'created_at' => now(),
        ]);

        $this->actingAs($cliente)->getJson('/administracion/auditoria')->assertForbidden();

        $this->actingAs($administrador)->getJson('/administracion/auditoria')
            ->assertOk()
            ->assertJsonCount(3, 'registros.data')
            ->assertJsonPath('registros.data.0.entidad_id', 6);

        $this->actingAs($administrador)->getJson('/administracion/auditoria?entidad=productos')
            ->assertOk()
            ->assertJsonCount(2, 'registros.data');

        $this->actingAs($administrador)->getJson('/administracion/auditoria?entidad=productos&entidad_id=5')
            ->assertOk()
            ->assertJsonCount(1, 'registros.data')
            ->assertJsonPath('registros.data.0.entidad_id', 5);

        $this->actingAs($administrador)->getJson('/administracion/auditoria?usuario_id='.$administrador->id)
            ->assertOk()
            ->assertJsonCount(1, 'registros.data')
            ->assertJsonPath('registros.data.0.accion', 'configuracion.ajuste_ecommerce_actualizado');

        $this->actingAs($administrador)->getJson('/administracion/auditoria?accion=producto.estado_actualizado&desde='.now()->subDays(5)->toDateString())
            ->assertOk()
            ->assertJsonCount(1, 'registros.data')
            ->assertJsonPath('registros.data.0.entidad_id', 6);

        $this->actingAs($administrador)->getJson('/administracion/auditoria?hasta='.now()->subDays(5)->toDateString())
            ->assertOk()
            ->assertJsonCount(1, 'registros.data')
            ->assertJsonPath('registros.data.0.entidad_id', 5);

        $this->actingAs($administrador)->getJson('/administracion/auditoria?desde=2026-10-10&hasta=2026-10-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('hasta');
    }

    public function test_auditoria_no_expone_rutas_de_escritura(): void
    {
        $administrador = Usuario::factory()->administrador()->create();

        $this->actingAs($administrador)->postJson('/administracion/auditoria', [])->assertMethodNotAllowed();
        $this->actingAs($administrador)->putJson('/administracion/auditoria', [])->assertMethodNotAllowed();
        $this->actingAs($administrador)->patchJson('/administracion/auditoria', [])->assertMethodNotAllowed();
        $this->actingAs($administrador)->deleteJson('/administracion/auditoria')->assertMethodNotAllowed();
    }
}
