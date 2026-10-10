<?php

namespace App\Http\Requests\Administracion\Pedidos;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;

class AgregarItemPedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof Usuario && $this->user()->rol === 'administrador' && $this->user()->activo;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'variante_producto_id' => ['required', 'integer', 'exists:variantes_producto,id'],
            'cantidad' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }
}
