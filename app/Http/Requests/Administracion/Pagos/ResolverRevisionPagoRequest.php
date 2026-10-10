<?php

namespace App\Http\Requests\Administracion\Pagos;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolverRevisionPagoRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $nota = $this->input('nota');

        if (is_string($nota)) {
            $this->merge(['nota' => trim($nota)]);
        }
    }

    public function authorize(): bool
    {
        return $this->user() instanceof Usuario && $this->user()->rol === 'administrador' && $this->user()->activo;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['aceptar', 'descartar'])],
            'nota' => ['required', 'string', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'decision.required' => 'Elegí aceptar o descartar el pago.',
            'decision.in' => 'La decisión no es válida.',
            'nota.required' => 'Escribí una nota con el motivo de la decisión.',
            'nota.max' => 'La nota no puede superar los 500 caracteres.',
        ];
    }
}
