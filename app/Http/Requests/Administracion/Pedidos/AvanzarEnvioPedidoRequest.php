<?php

namespace App\Http\Requests\Administracion\Pedidos;

use App\Enums\EstadoEnvio;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AvanzarEnvioPedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof Usuario && $this->user()->rol === 'administrador' && $this->user()->activo;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'estado_envio' => [
                'required',
                Rule::enum(EstadoEnvio::class)->only([EstadoEnvio::Preparado, EstadoEnvio::Despachado, EstadoEnvio::Entregado]),
            ],
        ];
    }
}
