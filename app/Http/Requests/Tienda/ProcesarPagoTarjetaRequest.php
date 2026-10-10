<?php

namespace App\Http\Requests\Tienda;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Recibe el token de la tarjeta generado por el Brick. Nunca llegan ni se guardan número de tarjeta ni CVV.
 */
class ProcesarPagoTarjetaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'metodo_seleccionado' => ['required', 'string', Rule::in(['credit_card', 'debit_card'])],
            'token' => ['required', 'string', 'alpha_dash', 'min:20', 'max:100'],
            'payment_method_id' => ['required', 'string', 'alpha_dash', 'max:40'],
            'issuer_id' => ['nullable', 'regex:/^\d{1,20}$/'],
            'installments' => ['required', 'integer', 'between:1,24'],
            'transaction_amount' => ['required', 'string', 'regex:/^\d{1,13}(\.\d{1,2})?$/'],
            'payer' => ['required', 'array'],
            'payer.email' => ['required', 'string', 'email', 'max:254'],
            'payer.identification' => ['required', 'array'],
            'payer.identification.type' => ['required', 'string', 'alpha', 'max:10'],
            'payer.identification.number' => ['required', 'regex:/^\d{7,11}$/'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'metodo_seleccionado.in' => 'El medio de pago no es válido.',
            'installments.between' => 'La cantidad de cuotas debe estar entre 1 y 24.',
            'transaction_amount.regex' => 'El importe no es válido.',
            'payer.email.email' => 'Ingresá un email válido.',
            'payer.identification.number.regex' => 'El número de documento no es válido.',
        ];
    }
}
