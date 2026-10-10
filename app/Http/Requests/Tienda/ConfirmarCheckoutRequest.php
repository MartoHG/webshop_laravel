<?php

namespace App\Http\Requests\Tienda;

use App\Enums\Provincia;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfirmarCheckoutRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $codigoPostal = $this->input('codigo_postal');

        if (is_string($codigoPostal)) {
            $this->merge(['codigo_postal' => strtoupper((string) preg_replace('/\s+/', '', $codigoPostal))]);
        }
    }

    public function authorize(): bool
    {
        return $this->user() instanceof Usuario;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'provincia' => ['required', Rule::enum(Provincia::class)],
            'localidad' => ['required', 'string', 'max:100'],
            'codigo_postal' => ['required', 'string', 'max:10', 'regex:/^(\d{4}|[A-Z]\d{4}[A-Z]{3})$/'],
            'direccion' => ['required', 'string', 'max:160'],
            'numero' => ['required', 'string', 'max:10'],
            'direccion_adicional' => ['nullable', 'string', 'max:60'],
            'observaciones' => ['nullable', 'string', 'max:500'],
            'huella' => ['required', 'string', 'size:64'],
        ];
    }
}
