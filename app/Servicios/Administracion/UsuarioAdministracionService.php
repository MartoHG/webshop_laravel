<?php

namespace App\Servicios\Administracion;

use App\Models\Usuario;
use App\Servicios\Auditoria\AuditoriaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UsuarioAdministracionService
{
    public function __construct(private AuditoriaService $auditoria) {}

    /** @param array{nombre: string, apellido: string, dni: string, email: string, telefono: string} $datos */
    public function crearAdministrador(array $datos): Usuario
    {
        return DB::transaction(function () use ($datos): Usuario {
            $usuario = new Usuario;
            $usuario->fill($datos);
            $usuario->rol = 'administrador';
            $usuario->activo = true;
            $usuario->email_verified_at = null;
            $usuario->password = Hash::make(Str::random(64));
            $usuario->save();

            $this->auditoria->registrar('usuario.administrador_creado', $usuario, [], [
                'nombre' => $usuario->nombre,
                'apellido' => $usuario->apellido,
                'email' => $usuario->email,
                'rol' => $usuario->rol,
            ]);

            $estado = Password::sendResetLink(['email' => $usuario->email]);

            if ($estado !== Password::RESET_LINK_SENT) {
                throw ValidationException::withMessages([
                    'email' => __('No se pudo enviar la invitación para establecer la contraseña.'),
                ]);
            }

            return $usuario;
        });
    }

    public function cambiarEstado(Usuario $administrador, Usuario $usuario, bool $activo): Usuario
    {
        return DB::transaction(function () use ($administrador, $usuario, $activo): Usuario {
            $usuario = Usuario::query()->lockForUpdate()->findOrFail($usuario->getKey());

            if (! $activo && $usuario->is($administrador)) {
                throw ValidationException::withMessages([
                    'activo' => __('No puedes desactivar tu propia cuenta.'),
                ]);
            }

            if (! $activo && $usuario->rol === 'administrador' && $usuario->activo) {
                $administradoresActivos = Usuario::query()
                    ->where('rol', 'administrador')
                    ->where('activo', true)
                    ->lockForUpdate()
                    ->get(['id']);

                if ($administradoresActivos->count() <= 1) {
                    throw ValidationException::withMessages([
                        'activo' => __('No se puede desactivar al último administrador activo.'),
                    ]);
                }
            }

            $activoAnterior = $usuario->activo;

            $usuario->activo = $activo;
            $usuario->save();

            $this->auditoria->registrarCambios(
                'usuario.estado_actualizado',
                $usuario,
                ['activo' => $activoAnterior],
                ['activo' => $activo],
                $administrador,
            );

            return $usuario->refresh();
        });
    }
}
