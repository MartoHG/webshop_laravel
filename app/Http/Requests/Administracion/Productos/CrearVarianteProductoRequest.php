<?php

namespace App\Http\Requests\Administracion\Productos;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CrearVarianteProductoRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $datos = [];

        foreach (['color', 'etiqueta', 'sku'] as $campo) {
            $valor = $this->input($campo);
            if (is_string($valor)) {
                $valor = trim($valor);
                $datos[$campo] = in_array($campo, ['color', 'etiqueta'], true) && $valor === '' ? null : $valor;
            }
        }

        $this->merge($datos);
    }

    public function authorize(): bool
    {
        return $this->user() instanceof Usuario && $this->user()->rol === 'administrador' && $this->user()->activo;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'color' => ['bail', 'nullable', 'string', 'max:100'],
            'etiqueta' => ['nullable', 'string', 'max:160'],
            'sku' => ['bail', 'required', 'string', 'max:100', Rule::unique('variantes_producto', 'sku')],
            'precio_base' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'activo' => ['prohibited'],
            'disponible' => ['prohibited'],
        ];
    }
}
