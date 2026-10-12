<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Notifications\RestablecerClave;
use App\Notifications\VerificarEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class VistasCuentaTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_paginas_de_acceso_y_la_cuenta_se_muestran(): void
    {
        $this->get(route('login.formulario'))
            ->assertOk()
            ->assertViewIs('tienda.iniciar-sesion')
            ->assertSee('<title>Iniciar sesión · El Refugio</title>', false)
            ->assertSee('Creala acá');

        $this->get(route('registro.formulario'))
            ->assertOk()
            ->assertSee('<title>Crear cuenta · El Refugio</title>', false)
            ->assertSee('Solo números');

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('<title>Recuperar contraseña · El Refugio</title>', false);

        $this->get(route('password.reset', ['token' => 'token-de-prueba', 'email' => 'ana@example.com']))
            ->assertOk()
            ->assertSee('<title>Contraseña nueva · El Refugio</title>', false);
    }

    public function test_las_paginas_de_ingreso_y_registro_no_se_muestran_a_quien_ya_tiene_sesion(): void
    {
        $this->actingAs($this->usuario());

        $this->get(route('login.formulario'))->assertRedirect('/');
        $this->get(route('registro.formulario'))->assertRedirect('/');
    }

    public function test_quien_no_tiene_sesion_va_al_ingreso_si_intenta_entrar_a_su_cuenta(): void
    {
        $this->get(route('cuenta.mostrar'))->assertRedirect(route('login'));

        $this->get(route('login.formulario'))
            ->assertOk()
            ->assertSee('<title>Iniciar sesión · El Refugio</title>', false);
    }

    public function test_el_ingreso_con_formulario_lleva_a_la_pagina_que_se_habia_pedido(): void
    {
        $usuario = $this->usuario(['email' => 'ana@example.com']);

        $this->get(route('cuenta.mostrar'))->assertRedirect(route('login'));

        $this->from(route('login.formulario'))
            ->post(route('login'), ['email' => 'ana@example.com', 'password' => 'clave-segura'])
            ->assertRedirect(route('cuenta.mostrar'))
            ->assertSessionHas('estado', 'Sesión iniciada.');

        $this->assertAuthenticatedAs($usuario);
    }

    public function test_un_ingreso_con_clave_incorrecta_vuelve_al_formulario_con_el_error(): void
    {
        $this->usuario(['email' => 'ana@example.com']);

        $this->from(route('login.formulario'))
            ->post(route('login'), ['email' => 'ana@example.com', 'password' => 'clave-equivocada'])
            ->assertRedirect(route('login.formulario'))
            ->assertSessionHasErrors(['email' => 'El email o la contraseña no son correctos. Revisalos y probá de nuevo.'])
            ->assertSessionHasInput('email', 'ana@example.com');

        $this->continuarSesion();

        $this->get(route('login.formulario'))
            ->assertOk()
            ->assertSee('El email o la contraseña no son correctos. Revisalos y probá de nuevo.')
            ->assertSee('value="ana@example.com"', false)
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('aria-describedby="campo-email-error"', false)
            ->assertSee('role="alert"', false);
    }

    public function test_el_registro_con_formulario_crea_la_cuenta_y_avisa_que_hay_que_confirmar_el_email(): void
    {
        Notification::fake();

        $this->from(route('registro.formulario'))
            ->post(route('registro'), [
                'nombre' => 'Ana',
                'apellido' => 'Pérez',
                'dni' => '12.345.678',
                'email' => 'ANA@example.com',
                'telefono' => '2215551234',
                'password' => 'clave-segura',
                'password_confirmation' => 'clave-segura',
            ])
            ->assertRedirect(route('tienda.inicio'))
            ->assertSessionHas('estado', 'Creamos tu cuenta. Te mandamos un email a ana@example.com para confirmarla.');

        $usuario = Usuario::query()->sole();

        $this->assertSame('12345678', $usuario->dni);
        $this->assertSame('cliente', $usuario->rol);
        $this->assertAuthenticatedAs($usuario);
        Notification::assertSentTo($usuario, VerificarEmail::class);
    }

    public function test_un_registro_con_datos_invalidos_vuelve_al_formulario_con_los_errores(): void
    {
        $this->from(route('registro.formulario'))
            ->post(route('registro'), [
                'nombre' => 'Ana',
                'email' => 'no-es-un-email',
                'dni' => '12',
                'password' => 'corta',
                'password_confirmation' => 'otra',
            ])
            ->assertRedirect(route('registro.formulario'))
            ->assertSessionHasErrors(['apellido', 'email', 'dni', 'telefono', 'password']);

        $this->continuarSesion();

        $this->get(route('registro.formulario'))
            ->assertOk()
            ->assertSee('Revisá los datos marcados abajo.')
            ->assertSee('aria-describedby="campo-email-error"', false);
    }

    public function test_pedir_una_clave_nueva_responde_igual_exista_o_no_el_email(): void
    {
        Notification::fake();
        $usuario = $this->usuario(['email' => 'ana@example.com']);
        $mensaje = 'Si el email está registrado, te mandamos un link para crear una contraseña nueva.';

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'ana@example.com'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('estado', $mensaje);

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'ausente@example.com'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('estado', $mensaje);

        Notification::assertSentTo($usuario, RestablecerClave::class);
    }

    public function test_la_clave_nueva_precarga_el_email_y_guarda_la_clave_con_el_formulario(): void
    {
        $usuario = $this->usuario(['email' => 'ana@example.com']);
        $token = Password::broker()->createToken($usuario);

        $this->get(route('password.reset', ['token' => $token, 'email' => 'ana@example.com']))
            ->assertOk()
            ->assertSee('value="ana@example.com"', false)
            ->assertSee('value="'.$token.'"', false);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'ana@example.com',
            'password' => 'clave-nueva-segura',
            'password_confirmation' => 'clave-nueva-segura',
        ])
            ->assertRedirect(route('login.formulario'))
            ->assertSessionHas('estado', 'Tu contraseña se actualizó. Ya podés ingresar.');

        $this->assertTrue(password_verify('clave-nueva-segura', $usuario->fresh()->password));
    }

    public function test_una_clave_nueva_con_enlace_vencido_vuelve_al_formulario_con_el_error(): void
    {
        $this->usuario(['email' => 'ana@example.com']);

        $this->from(route('password.reset', ['token' => 'token-vencido', 'email' => 'ana@example.com']))
            ->post(route('password.update'), [
                'token' => 'token-vencido',
                'email' => 'ana@example.com',
                'password' => 'clave-nueva-segura',
                'password_confirmation' => 'clave-nueva-segura',
            ])
            ->assertRedirect(route('password.reset', ['token' => 'token-vencido', 'email' => 'ana@example.com']))
            ->assertSessionHasErrors(['email' => 'El enlace no es válido o ya venció.']);
    }

    public function test_el_enlace_de_verificacion_sin_sesion_confirma_el_email_y_lleva_al_ingreso(): void
    {
        $usuario = $this->usuario(['email' => 'ana@example.com', 'email_verified_at' => null]);

        $this->get($this->enlaceDeVerificacion($usuario))
            ->assertRedirect(route('login.formulario'))
            ->assertSessionHas('estado', 'Confirmaste tu email.');

        $this->assertNotNull($usuario->fresh()->email_verified_at);
    }

    public function test_el_enlace_de_verificacion_con_sesion_lleva_a_la_cuenta(): void
    {
        $usuario = $this->usuario(['email' => 'ana@example.com', 'email_verified_at' => null]);

        $this->actingAs($usuario)
            ->get($this->enlaceDeVerificacion($usuario))
            ->assertRedirect(route('cuenta.mostrar'))
            ->assertSessionHas('estado', 'Confirmaste tu email.');
    }

    public function test_mi_cuenta_avisa_que_falta_confirmar_el_email_y_ofrece_reenviarlo(): void
    {
        $usuario = $this->usuario(['email_verified_at' => null]);

        $this->actingAs($usuario)
            ->get(route('cuenta.mostrar'))
            ->assertOk()
            ->assertViewIs('tienda.cuenta')
            ->assertSee('<title>Mi cuenta · El Refugio</title>', false)
            ->assertSee('Falta confirmar tu email')
            ->assertSee('action="'.route('verification.send').'"', false)
            ->assertSee('Mis pedidos');
    }

    public function test_mi_cuenta_no_muestra_el_aviso_si_el_email_ya_esta_confirmado(): void
    {
        $this->actingAs($this->usuario())
            ->get(route('cuenta.mostrar'))
            ->assertOk()
            ->assertDontSee('Falta confirmar tu email');
    }

    public function test_reenviar_la_verificacion_con_formulario_vuelve_a_la_cuenta_con_aviso(): void
    {
        Notification::fake();
        $usuario = $this->usuario(['email_verified_at' => null]);

        $this->actingAs($usuario)
            ->from(route('cuenta.mostrar'))
            ->post(route('verification.send'))
            ->assertRedirect(route('cuenta.mostrar'))
            ->assertSessionHas('estado', 'Te mandamos otro email para confirmar tu cuenta.');

        Notification::assertSentTo($usuario, VerificarEmail::class);
    }

    public function test_actualizar_el_perfil_con_formulario_guarda_los_datos_y_pide_confirmar_el_email_nuevo(): void
    {
        Notification::fake();
        $usuario = $this->usuario(['email' => 'ana@example.com']);

        $this->actingAs($usuario)
            ->from(route('cuenta.mostrar'))
            ->put(route('cuenta.perfil.actualizar'), [
                'dni' => '30.111.222',
                'email' => 'Ana.Nueva@example.com',
                'telefono' => '2281 555-0000',
            ])
            ->assertRedirect(route('cuenta.mostrar'))
            ->assertSessionHas('estado', 'Perfil actualizado.');

        $usuario->refresh();

        $this->assertSame('30111222', $usuario->dni);
        $this->assertSame('ana.nueva@example.com', $usuario->email);
        $this->assertNull($usuario->email_verified_at);
        Notification::assertSentTo($usuario, VerificarEmail::class);
    }

    public function test_un_dni_repetido_vuelve_a_la_cuenta_con_el_error_asociado_al_campo(): void
    {
        $this->usuario(['dni' => '30111222']);
        $usuario = $this->usuario(['dni' => '40222333']);

        $this->actingAs($usuario)
            ->from(route('cuenta.mostrar'))
            ->put(route('cuenta.perfil.actualizar'), [
                'dni' => '30111222',
                'email' => $usuario->email,
                'telefono' => '2281555000',
            ])
            ->assertRedirect(route('cuenta.mostrar'))
            ->assertSessionHasErrors('dni');

        $this->continuarSesion();

        $this->actingAs($usuario)
            ->get(route('cuenta.mostrar'))
            ->assertOk()
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('aria-describedby="campo-dni-pista campo-dni-error"', false);
    }

    public function test_cerrar_sesion_con_formulario_vuelve_al_inicio(): void
    {
        $this->actingAs($this->usuario())
            ->from(route('cuenta.mostrar'))
            ->post(route('logout'))
            ->assertRedirect('/')
            ->assertSessionHas('estado', 'Sesión cerrada.');

        $this->assertGuest();
    }

    public function test_los_endpoints_json_de_la_cuenta_siguen_respondiendo_igual(): void
    {
        Notification::fake();
        $usuario = $this->usuario(['email' => 'ana@example.com', 'email_verified_at' => null]);

        $this->getJson(route('password.reset', ['token' => 'token-json', 'email' => 'ana@example.com']))
            ->assertJsonPath('token', 'token-json');

        $this->postJson(route('password.email'), ['email' => 'ana@example.com'])
            ->assertJsonPath('mensaje', 'Si la cuenta existe, enviaremos instrucciones para restablecer la clave.');

        $this->getJson($this->enlaceDeVerificacion($usuario))
            ->assertJsonPath('mensaje', 'Correo verificado correctamente.');

        $this->postJson(route('login'), ['email' => 'ana@example.com', 'password' => 'clave-segura'])
            ->assertJsonPath('mensaje', 'Sesión iniciada.');

        $this->postJson(route('verification.send'))
            ->assertJsonPath('mensaje', 'Si el correo todavía necesita verificación, enviaremos un nuevo enlace.');

        $this->putJson(route('cuenta.perfil.actualizar'), [
            'dni' => '30111222',
            'email' => 'ana@example.com',
            'telefono' => '2281555000',
        ])->assertJsonPath('mensaje', 'Perfil actualizado.');

        $this->getJson(route('cuenta.pedidos.index'))
            ->assertJsonStructure(['pedidos']);

        $this->postJson(route('logout'))
            ->assertJsonPath('mensaje', 'Sesión cerrada.');
    }

    /**
     * Mantiene la sesión de la última respuesta para leer los avisos flash en el GET siguiente.
     */
    private function continuarSesion(): void
    {
        $this->withCookie(config('session.cookie'), session()->getId());
    }

    private function usuario(array $atributos = []): Usuario
    {
        return Usuario::factory()->create(array_merge(['password' => 'clave-segura'], $atributos));
    }

    private function enlaceDeVerificacion(Usuario $usuario): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), [
            'usuario' => $usuario->getKey(),
            'hash' => sha1($usuario->getEmailForVerification()),
        ]);
    }
}
