<?php

namespace App\Http\Requests\Administracion\Productos;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarVarianteProductoRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['color', 'etiqueta'] as $campo) {
            $valor = $this->input($campo);
            if ($this->exists($campo) && is_string($valor)) {
                $valor = trim($valor);
                $this->merge([$campo => $valor === '' ? null : $valor]);
            }
        }

        $sku = $this->input('sku');
        if ($this->exists('sku') && is_string($sku)) {
            $this->merge(['sku' => trim($sku)]);
        }
    }

    public function authorize(): bool
    {
        return $this->user() instanceof Usuario && $this->user()->rol === 'administrador' && $this->user()->activo;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $variante = $this->route('varianteProducto');

        return [
            'color' => ['bail', 'sometimes', 'nullable', 'string', 'max:100'],
            'etiqueta' => ['sometimes', 'nullable', 'string', 'max:160'],
            'sku' => ['bail', 'sometimes', 'required', 'string', 'max:100', Rule::unique('variantes_producto', 'sku')->ignore($variante)],
            'precio_base' => ['sometimes', 'required', 'numeric', 'gt:0', 'decimal:0,2'],
            'activo' => ['prohibited'],
            'disponible' => ['prohibited'],
        ];
    }
}
