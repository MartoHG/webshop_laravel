<?php

namespace App\Http\Requests\Administracion;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;

class CambiarEstadoUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof Usuario && $this->user()->rol === 'administrador';
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'activo' => ['required', 'boolean'],
        ];
    }
}
