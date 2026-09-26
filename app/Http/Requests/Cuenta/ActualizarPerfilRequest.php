<?php

namespace App\Http\Requests\Cuenta;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarPerfilRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'dni' => preg_replace('/\D+/', '', (string) $this->input('dni')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user() instanceof Usuario;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Usuario $usuario */
        $usuario = $this->user();

        return [
            'dni' => ['required', 'digits_between:7,12', Rule::unique('usuarios', 'dni')->ignore($usuario->getKey())],
            'telefono' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255', Rule::unique('usuarios', 'email')->ignore($usuario->getKey())],
        ];
    }
}
