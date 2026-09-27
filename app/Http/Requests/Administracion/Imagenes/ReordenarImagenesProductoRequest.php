<?php

namespace App\Http\Requests\Administracion\Imagenes;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReordenarImagenesProductoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->rol === 'administrador';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'variante_producto_id' => ['nullable', 'integer', 'exists:variantes_producto,id'],
            'imagenes' => ['required', 'array', 'min:1'],
            'imagenes.*.id' => ['required', 'integer', 'distinct', 'exists:imagenes_producto,id'],
            'imagenes.*.orden' => ['required', 'integer', 'min:0'],
        ];
    }
}
