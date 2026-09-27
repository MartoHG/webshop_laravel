<?php

namespace App\Http\Requests\Administracion\Productos;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;

class ActualizarProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof Usuario && $this->user()->rol === 'administrador' && $this->user()->activo;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'required', 'string', 'max:160'],
            'descripcion' => ['sometimes', 'nullable', 'string'],
            'slug' => ['prohibited'],
            'activo' => ['prohibited'],
            'disponible' => ['prohibited'],
        ];
    }
}
