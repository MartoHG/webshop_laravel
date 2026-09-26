<?php

namespace App\Console\Commands;

use App\Models\Usuario;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

#[Signature('usuario:crear-administrador')]
#[Description('Crea la cuenta del primer administrador del sistema.')]
class CrearAdministrador extends Command
{
    public function handle(): int
    {
        if (Usuario::query()->where('rol', 'administrador')->exists()) {
            $this->error('Ya existe al menos un administrador.');

            return self::FAILURE;
        }

        $datos = [
            'nombre' => $this->ask('Nombre'),
            'apellido' => $this->ask('Apellido'),
            'dni' => $this->ask('DNI'),
            'email' => $this->ask('Email'),
            'telefono' => $this->ask('Teléfono / WhatsApp'),
            'password' => $this->secret('Contraseña'),
        ];
        $confirmacion = $this->secret('Confirmar contraseña');
        $datos['dni'] = preg_replace('/\D+/', '', (string) $datos['dni']) ?? '';
        $datos['email'] = mb_strtolower(trim((string) $datos['email']));

        $validador = Validator::make($datos, [
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'dni' => ['required', 'regex:/^[0-9]+$/', 'unique:usuarios,dni'],
            'email' => ['required', 'email', 'max:255', 'unique:usuarios,email'],
            'telefono' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:12'],
        ]);

        if ($validador->fails()) {
            foreach ($validador->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if (! is_string($datos['password']) || $datos['password'] !== $confirmacion) {
            $this->error('Las contraseñas no coinciden.');

            return self::FAILURE;
        }

        Usuario::query()->create([
            ...$validador->validated(),
            'password' => Hash::make($datos['password']),
            'rol' => 'administrador',
            'activo' => true,
        ]);

        $this->info('Administrador creado.');

        return self::SUCCESS;
    }
}
