<?php

namespace App\Servicios\Cuenta;

use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

class CuentaService
{
    /** @param array{dni: string, telefono: string, email: string} $datos */
    public function actualizarPerfil(Usuario $usuario, array $datos): Usuario
    {
        [$usuario, $cambioEmail] = DB::transaction(function () use ($usuario, $datos): array {
            $cambioEmail = $usuario->email !== $datos['email'];
            $usuario->dni = $datos['dni'];
            $usuario->telefono = $datos['telefono'];

            if ($cambioEmail) {
                $usuario->email = $datos['email'];
                $usuario->email_verified_at = null;
            }

            $usuario->save();

            return [$usuario->refresh(), $cambioEmail];
        });

        if ($cambioEmail) {
            $usuario->sendEmailVerificationNotification();
        }

        return $usuario;
    }
}
