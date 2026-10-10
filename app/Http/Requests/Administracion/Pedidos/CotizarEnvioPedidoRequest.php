<?php

namespace App\Http\Requests\Administracion\Pedidos;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;

class CotizarEnvioPedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof Usuario && $this->user()->rol === 'administrador' && $this->user()->activo;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'expreso' => ['required', 'string', 'max:100'],
            'costo_envio' => ['required', 'decimal:0,2', 'min:0', 'max:9999999999999.99'],
        ];
    }
}
