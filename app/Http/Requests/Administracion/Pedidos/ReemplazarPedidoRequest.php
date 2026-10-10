<?php

namespace App\Http\Requests\Administracion\Pedidos;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;

class ReemplazarPedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof Usuario && $this->user()->rol === 'administrador' && $this->user()->activo;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'nota' => ['nullable', 'string', 'max:500'],
        ];
    }
}
