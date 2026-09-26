<?php

namespace App\Http\Requests\Administracion;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CrearAdministradorRequest extends FormRequest
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
        return $this->user() instanceof Usuario && $this->user()->rol === 'administrador';
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'dni' => ['required', 'digits_between:7,12', Rule::unique('usuarios', 'dni')],
            'email' => ['required', 'email', 'max:255', Rule::unique('usuarios', 'email')],
            'telefono' => ['required', 'string', 'max:30'],
        ];
    }
}
