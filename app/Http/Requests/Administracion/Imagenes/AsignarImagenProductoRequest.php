<?php

namespace App\Http\Requests\Administracion\Imagenes;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AsignarImagenProductoRequest extends FormRequest
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
            'archivo_imagen_producto_id' => ['required', 'integer', 'exists:archivos_imagenes_producto,id'],
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'variante_producto_id' => ['nullable', 'integer', 'exists:variantes_producto,id'],
            'orden' => ['sometimes', 'integer', 'min:0'],
            'es_principal' => ['sometimes', 'boolean'],
        ];
    }
}
