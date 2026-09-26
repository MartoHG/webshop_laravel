<?php

namespace App\Http\Requests\Autenticacion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistroRequest extends FormRequest
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
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'dni' => ['required', 'digits_between:7,12', Rule::unique('usuarios', 'dni')],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('usuarios', 'email')],
            'telefono' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
        ];
    }
}
