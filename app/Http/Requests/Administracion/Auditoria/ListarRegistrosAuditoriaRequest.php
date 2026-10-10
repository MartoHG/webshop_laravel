<?php

namespace App\Http\Requests\Administracion\Auditoria;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;

class ListarRegistrosAuditoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof Usuario && $this->user()->rol === 'administrador' && $this->user()->activo;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'entidad' => ['nullable', 'string', 'max:100'],
            'entidad_id' => ['nullable', 'integer', 'min:1'],
            'usuario_id' => ['nullable', 'integer', 'min:1'],
            'accion' => ['nullable', 'string', 'max:100'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ];
    }
}
