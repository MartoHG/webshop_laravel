<?php

namespace App\Servicios\Autenticacion;

use App\Models\Usuario;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AutenticacionService
{
    /** @param array{nombre: string, apellido: string, dni: string, email: string, telefono: string, password: string} $datos */
    public function registrar(array $datos): Usuario
    {
        $usuario = Usuario::create([
            'nombre' => $datos['nombre'],
            'apellido' => $datos['apellido'],
            'dni' => preg_replace('/\D+/', '', $datos['dni']),
            'email' => Str::lower($datos['email']),
            'telefono' => $datos['telefono'],
            'password' => $datos['password'],
            'rol' => 'cliente',
            'activo' => true,
        ]);

        $usuario->sendEmailVerificationNotification();
        Auth::login($usuario);

        return $usuario;
    }

    public function iniciarSesion(string $email, string $password, bool $recordarme = false): bool
    {
        return Auth::attempt([
            'email' => Str::lower($email),
            'password' => $password,
            'activo' => true,
        ], $recordarme);
    }

    public function cerrarSesion(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    public function solicitarRestablecimiento(string $email): string
    {
        return Password::sendResetLink(['email' => Str::lower($email)]);
    }

    /** @param array{email: string, password: string, password_confirmation: string, token: string} $datos */
    public function restablecerClave(array $datos): string
    {
        return Password::reset($datos, function (Usuario $usuario, string $password): void {
            $usuario->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($usuario));
        });
    }

    public function verificarEmail(Usuario $usuario, string $hash): bool
    {
        if (! hash_equals(sha1($usuario->getEmailForVerification()), $hash)) {
            return false;
        }

        if ($usuario->hasVerifiedEmail()) {
            return false;
        }

        return $usuario->markEmailAsVerified();
    }

    public function reenviarVerificacion(Usuario $usuario): void
    {
        if (! $usuario->hasVerifiedEmail()) {
            $usuario->sendEmailVerificationNotification();
        }
    }
}
