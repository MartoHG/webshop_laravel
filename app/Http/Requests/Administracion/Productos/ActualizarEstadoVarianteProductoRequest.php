<?php

namespace App\Http\Requests\Administracion\Productos;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;

class ActualizarEstadoVarianteProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof Usuario && $this->user()->rol === 'administrador' && $this->user()->activo;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'activo' => ['required_without:disponible', 'boolean'],
            'disponible' => ['required_without:activo', 'boolean'],
        ];
    }
}
