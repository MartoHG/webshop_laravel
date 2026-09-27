<?php

namespace App\Http\Requests\Administracion\Imagenes;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubirImagenProductoRequest extends FormRequest
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
            'imagen' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:'.config('imagenes.maximo_kilobytes')],
            'orden' => ['sometimes', 'integer', 'min:0'],
            'es_principal' => ['sometimes', 'boolean'],
        ];
    }
}
