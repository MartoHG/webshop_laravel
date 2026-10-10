<?php

namespace App\Http\Requests\Administracion\Pedidos;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;

class ActualizarItemPedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof Usuario && $this->user()->rol === 'administrador' && $this->user()->activo;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'cantidad' => ['required_without:variante_producto_id', 'prohibits:variante_producto_id', 'integer', 'min:1', 'max:99'],
            'variante_producto_id' => ['required_without:cantidad', 'prohibits:cantidad', 'integer', 'exists:variantes_producto,id'],
        ];
    }
}
