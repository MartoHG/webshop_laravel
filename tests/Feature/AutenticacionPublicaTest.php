<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Notifications\RestablecerClave;
use App\Notifications\VerificarEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AutenticacionPublicaTest extends TestCase
{
    use RefreshDatabase;

    public function test_registro_crea_cliente_con_dni_normalizado_y_envia_verificacion(): void
    {
        Notification::fake();
        $this->get('/');
        $identificadorSesionAnterior = session()->getId();

        $this->postJson('/registro', [
            'nombre' => 'Ana',
            'apellido' => 'Pérez',
            'dni' => '12.345.678',
            'email' => 'ANA@example.com',
            'telefono' => '2215551234',
            'password' => 'clave-segura',
            'password_confirmation' => 'clave-segura',
            'rol' => 'administrador',
            'activo' => false,
        ])->assertCreated()
            ->assertJsonPath('usuario.rol', 'cliente');

        $usuario = Usuario::query()->sole();

        $this->assertSame('12345678', $usuario->dni);
        $this->assertSame('ana@example.com', $usuario->email);
        $this->assertTrue($usuario->activo);
        $this->assertAuthenticatedAs($usuario);
        $this->assertNotSame($identificadorSesionAnterior, session()->getId());
        Notification::assertSentTo($usuario, VerificarEmail::class);
    }

    public function test_email_y_dni_deben_ser_unicos_y_dni_se_normaliza_antes_de_validar(): void
    {
        Usuario::factory()->create(['dni' => '12345678', 'email' => 'ana@example.com']);

        $this->postJson('/registro', [
            'nombre' => 'Ana',
            'apellido' => 'Pérez',
            'dni' => '12.345.678',
            'email' => 'ANA@example.com',
            'telefono' => '2215551234',
            'password' => 'clave-segura',
            'password_confirmation' => 'clave-segura',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['dni', 'email']);
    }

    public function test_usuario_no_verificado_puede_iniciar_sesion_pero_inactivo_no(): void
    {
        $sinVerificar = Usuario::factory()->noVerificado()->create(['password' => 'clave-segura']);

        $this->postJson('/iniciar-sesion', [
            'email' => $sinVerificar->email,
            'password' => 'clave-segura',
        ])->assertOk();

        $this->assertAuthenticatedAs($sinVerificar);
        auth()->logout();

        $inactivo = Usuario::factory()->create(['activo' => false, 'password' => 'clave-segura']);

        $this->postJson('/iniciar-sesion', [
            'email' => $inactivo->email,
            'password' => 'clave-segura',
        ])->assertUnprocessable();

        $this->assertGuest();
    }

    public function test_enlace_firmado_verifica_al_usuario_sin_requerir_sesion(): void
    {
        $usuario = Usuario::factory()->noVerificado()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), [
            'usuario' => $usuario->getKey(),
            'hash' => sha1($usuario->getEmailForVerification()),
        ]);

        $this->getJson($url)->assertOk()
            ->assertJsonPath('mensaje', 'Correo verificado correctamente.');
        $this->assertNotNull($usuario->fresh()->email_verified_at);
    }

    public function test_enlace_de_verificacion_tamperado_vencido_o_con_hash_antiguo_se_rechaza(): void
    {
        $usuario = Usuario::factory()->noVerificado()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), [
            'usuario' => $usuario->getKey(),
            'hash' => sha1($usuario->getEmailForVerification()),
        ]);

        $this->get($url.'&tamper=1')->assertForbidden();

        $urlVencida = URL::temporarySignedRoute('verification.verify', now()->subMinute(), [
            'usuario' => $usuario->getKey(),
            'hash' => sha1($usuario->getEmailForVerification()),
        ]);
        $this->get($urlVencida)->assertForbidden();

        $urlAntigua = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), [
            'usuario' => $usuario->getKey(),
            'hash' => sha1('otro@example.test'),
        ]);
        $this->get($urlAntigua)->assertForbidden();
        $this->assertNull($usuario->fresh()->email_verified_at);
    }

    public function test_solicitud_de_clave_no_revela_si_el_correo_existe(): void
    {
        Notification::fake();
        $usuario = Usuario::factory()->create();

        $this->postJson('/clave/solicitar-restablecimiento', ['email' => $usuario->email])
            ->assertOk()
            ->assertJsonPath('mensaje', 'Si la cuenta existe, enviaremos instrucciones para restablecer la clave.');

        Notification::assertSentTo($usuario, RestablecerClave::class);

        $this->postJson('/clave/solicitar-restablecimiento', ['email' => 'ausente@example.com'])
            ->assertOk()
            ->assertJsonPath('mensaje', 'Si la cuenta existe, enviaremos instrucciones para restablecer la clave.');
    }

    public function test_enlace_y_post_completan_el_restablecimiento_de_clave(): void
    {
        Notification::fake();
        $usuario = Usuario::factory()->create(['password' => 'clave-anterior']);

        $this->postJson('/clave/solicitar-restablecimiento', ['email' => $usuario->email])->assertOk();
        $notificacion = Notification::sent($usuario, RestablecerClave::class)->sole();

        $this->getJson(route('password.reset', ['token' => $notificacion->token, 'email' => $usuario->email]))
            ->assertOk()
            ->assertJsonPath('token', $notificacion->token)
            ->assertJsonPath('email', $usuario->email);

        $this->postJson('/clave/restablecer', [
            'token' => $notificacion->token,
            'email' => $usuario->email,
            'password' => 'clave-nueva-segura',
            'password_confirmation' => 'clave-nueva-segura',
        ])->assertOk();

        $this->assertTrue(password_verify('clave-nueva-segura', $usuario->fresh()->password));
        $this->postJson('/clave/restablecer', [
            'token' => $notificacion->token,
            'email' => $usuario->email,
            'password' => 'otra-clave-segura',
            'password_confirmation' => 'otra-clave-segura',
        ])->assertUnprocessable();
    }
}
