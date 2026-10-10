<?php

namespace App\Http\Requests\Administracion\Pedidos;

use App\Enums\MotivoCancelacion;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CancelarPedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof Usuario && $this->user()->rol === 'administrador' && $this->user()->activo;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'motivo' => ['required', Rule::enum(MotivoCancelacion::class)],
            'detalle' => ['nullable', 'string', 'max:500'],
        ];
    }
}
