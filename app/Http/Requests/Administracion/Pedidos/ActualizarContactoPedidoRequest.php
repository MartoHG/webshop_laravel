<?php

namespace App\Http\Requests\Administracion\Pedidos;

use App\Enums\EstadoContacto;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarContactoPedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof Usuario && $this->user()->rol === 'administrador' && $this->user()->activo;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'estado_contacto' => ['required', Rule::enum(EstadoContacto::class)],
        ];
    }
}
