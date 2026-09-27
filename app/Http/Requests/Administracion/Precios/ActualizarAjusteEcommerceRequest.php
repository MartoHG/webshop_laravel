<?php

namespace App\Http\Requests\Administracion\Precios;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarAjusteEcommerceRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $porcentaje = $this->input('porcentaje');

        if (is_int($porcentaje) || is_float($porcentaje)) {
            $this->merge(['porcentaje' => (string) $porcentaje]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'porcentaje' => ['required', 'string', 'regex:/^\d{1,6}(?:\.\d{1,6})?$/'],
        ];
    }
}
