<?php

namespace App\Http\Requests\Administracion\Pedidos;

use App\Enums\EstadoContacto;
use App\Enums\EstadoEnvio;
use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListarPedidosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof Usuario && $this->user()->rol === 'administrador' && $this->user()->activo;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'estado_pedido' => ['nullable', Rule::enum(EstadoPedido::class)],
            'estado_contacto' => ['nullable', Rule::enum(EstadoContacto::class)],
            'estado_envio' => ['nullable', Rule::enum(EstadoEnvio::class)],
            'estado_pago' => ['nullable', Rule::enum(EstadoPago::class)],
            'buscar' => ['nullable', 'string', 'max:100'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ];
    }
}
