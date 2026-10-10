<?php

namespace App\Http\Requests\Tienda;

use Illuminate\Foundation\Http\FormRequest;

/**
 * El link de pago es la credencial: la solicitud se valida por forma y la regla se verifica en el servicio.
 */
class IniciarIntentoPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'regla_precio_pago_id' => ['required', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'regla_precio_pago_id.required' => 'Elegí una modalidad de pago.',
            'regla_precio_pago_id.integer' => 'La modalidad de pago no es válida.',
            'regla_precio_pago_id.min' => 'La modalidad de pago no es válida.',
        ];
    }
}
