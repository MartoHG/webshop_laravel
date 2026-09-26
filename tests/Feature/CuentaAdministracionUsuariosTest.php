<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Notifications\RestablecerClave;
use App\Notifications\VerificarEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class CuentaAdministracionUsuariosTest extends TestCase
{
    use RefreshDatabase;

    public function test_cliente_actualiza_documentos_y_verifica_un_email_nuevo(): void
    {
        Notification::fake();
        $cliente = Usuario::factory()->create([
            'dni' => '12345678',
            'email' => 'cliente@example.test',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($cliente)->putJson('/cuenta/perfil', [
            'dni' => '87.654.321',
            'telefono' => '2284555123',
            'email' => '  NUEVO@example.test  ',
        ])->assertOk();

        $this->assertDatabaseHas('usuarios', [
            'id' => $cliente->id,
            'dni' => '87654321',
            'telefono' => '2284555123',
            'email' => 'nuevo@example.test',
            'email_verified_at' => null,
        ]);
        Notification::assertSentTo($cliente, VerificarEmail::class);
    }

    public function test_dni_duplicado_no_se_puede_asignar_en_el_perfil(): void
    {
        Usuario::factory()->create(['dni' => '12345678']);
        $cliente = Usuario::factory()->create(['dni' => '87654321']);

        $this->actingAs($cliente)->putJson('/cuenta/perfil', [
            'dni' => '12345678',
            'telefono' => '2284555123',
            'email' => $cliente->email,
        ])->assertUnprocessable()->assertJsonValidationErrors('dni');
    }

    public function test_administrador_crea_otro_administrador_con_invitacion_de_clave(): void
    {
        Notification::fake();
        $administrador = Usuario::factory()->administrador()->create();

        $this->actingAs($administrador)->postJson('/administracion/usuarios/administradores', [
            'nombre' => 'Ana',
            'apellido' => 'García',
            'dni' => '30.123.456',
            'email' => ' ANA@example.test ',
            'telefono' => '2284555123',
        ])->assertCreated();

        $nuevoAdministrador = Usuario::query()->where('email', 'ana@example.test')->firstOrFail();
        $this->assertSame('administrador', $nuevoAdministrador->rol);
        $this->assertTrue($nuevoAdministrador->activo);
        $this->assertNull($nuevoAdministrador->email_verified_at);
        Notification::assertSentTo($nuevoAdministrador, RestablecerClave::class);
    }

    public function test_administrador_no_puede_desactivarse_ni_desactivar_al_ultimo_administrador(): void
    {
        $administrador = Usuario::factory()->administrador()->create();

        $this->actingAs($administrador)->patchJson('/administracion/usuarios/'.$administrador->id.'/estado', [
            'activo' => false,
        ])->assertUnprocessable()->assertJsonValidationErrors('activo');

        $this->assertDatabaseHas('usuarios', ['id' => $administrador->id, 'activo' => true]);
    }

    public function test_usuario_desactivado_pierde_acceso_a_su_sesion_activa(): void
    {
        $cliente = Usuario::factory()->create(['activo' => false]);

        $this->actingAs($cliente)->putJson('/cuenta/perfil', [
            'dni' => $cliente->dni,
            'telefono' => $cliente->telefono,
            'email' => $cliente->email,
        ])->assertForbidden();

        $this->assertGuest();
    }

    public function test_usuario_desactivado_no_puede_reenviar_verificacion(): void
    {
        Notification::fake();
        $cliente = Usuario::factory()->noVerificado()->create(['activo' => false]);

        $this->actingAs($cliente)->postJson('/email/verificacion/reenviar')
            ->assertForbidden();

        Notification::assertNothingSent();
        $this->assertGuest();
    }

    public function test_cliente_no_puede_usar_rutas_administrativas(): void
    {
        $cliente = Usuario::factory()->create();

        $this->actingAs($cliente)->postJson('/administracion/usuarios/administradores', [
            'nombre' => 'Ana',
            'apellido' => 'García',
            'dni' => '30123456',
            'email' => 'ana@example.test',
            'telefono' => '2284555123',
        ])->assertForbidden();

        $this->assertDatabaseMissing('usuarios', ['email' => 'ana@example.test']);
    }

    public function test_no_persiste_administrador_si_no_puede_emitir_invitacion(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => 'ana@example.test'])
            ->andReturn(Password::RESET_THROTTLED);

        $this->actingAs($administrador)->postJson('/administracion/usuarios/administradores', [
            'nombre' => 'Ana',
            'apellido' => 'García',
            'dni' => '30123456',
            'email' => 'ana@example.test',
            'telefono' => '2284555123',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->assertDatabaseMissing('usuarios', ['email' => 'ana@example.test']);
    }

    public function test_revierte_la_creacion_si_falla_el_emisor_de_invitaciones(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => 'ana@example.test'])
            ->andThrow(new \RuntimeException('Fallo simulado del envío.'));

        $this->actingAs($administrador)->postJson('/administracion/usuarios/administradores', [
            'nombre' => 'Ana',
            'apellido' => 'García',
            'dni' => '30123456',
            'email' => 'ana@example.test',
            'telefono' => '2284555123',
        ])->assertServerError();

        $this->assertDatabaseMissing('usuarios', ['email' => 'ana@example.test']);
    }
}
