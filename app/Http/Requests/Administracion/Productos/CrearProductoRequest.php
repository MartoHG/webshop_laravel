<?php

namespace App\Http\Requests\Administracion\Productos;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CrearProductoRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $variantes = $this->input('variantes');
        if (is_array($variantes)) {
            foreach ($variantes as $indice => $variante) {
                if (! is_array($variante)) {
                    continue;
                }

                foreach (['color', 'etiqueta', 'sku'] as $campo) {
                    if (isset($variante[$campo]) && is_string($variante[$campo])) {
                        $variantes[$indice][$campo] = trim($variante[$campo]);
                    }
                }
            }

            $this->merge(['variantes' => $variantes]);
        }
    }

    public function authorize(): bool
    {
        return $this->user() instanceof Usuario && $this->user()->rol === 'administrador' && $this->user()->activo;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:160'],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['prohibited'],
            'disponible' => ['prohibited'],
            'slug' => ['prohibited'],
            'variantes' => ['required', 'array', 'min:1'],
            'variantes.*.color' => ['bail', 'nullable', 'string', 'max:100'],
            'variantes.*.etiqueta' => ['nullable', 'string', 'max:160'],
            'variantes.*.sku' => ['bail', 'required', 'string', 'max:100', 'distinct:ignore_case', Rule::unique('variantes_producto', 'sku')],
            'variantes.*.precio_base' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
        ];
    }
}
