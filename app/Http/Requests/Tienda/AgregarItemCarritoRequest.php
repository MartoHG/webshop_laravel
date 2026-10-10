<?php

namespace App\Http\Requests\Tienda;

use Illuminate\Foundation\Http\FormRequest;

class AgregarItemCarritoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'variante_producto_id' => ['required', 'integer'],
            'cantidad' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }
}
