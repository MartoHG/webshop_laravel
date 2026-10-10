<?php

namespace App\Http\Requests\Administracion\Precios;

use App\Models\Usuario;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class ActualizarReglaPrecioPagoRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $porcentaje = $this->input('porcentaje');

        if (is_int($porcentaje) || is_float($porcentaje)) {
            $this->merge(['porcentaje' => (string) $porcentaje]);
        }
    }

    public function authorize(): bool
    {
        return $this->user() instanceof Usuario && $this->user()->rol === 'administrador' && $this->user()->activo;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'tipo_medio_pago' => ['prohibited'],
            'cuotas' => ['prohibited'],
            'porcentaje' => ['bail', 'required_without:activo', 'string', 'regex:/^\d{1,6}(?:\.\d{1,6})?$/', $this->reglaPorcentajeEnRango()],
            'activo' => ['required_without:porcentaje', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'tipo_medio_pago.prohibited' => 'El medio de pago no se puede modificar. Crea otra regla para cambiarlo.',
            'cuotas.prohibited' => 'La cantidad de cuotas no se puede modificar. Crea otra regla para cambiarla.',
            'porcentaje.required_without' => 'Debes indicar el porcentaje o el estado activo.',
            'porcentaje.regex' => 'El porcentaje debe ser un número con hasta 6 decimales.',
            'activo.required_without' => 'Debes indicar el porcentaje o el estado activo.',
            'activo.boolean' => 'El estado activo debe ser verdadero o falso.',
        ];
    }

    private function reglaPorcentajeEnRango(): Closure
    {
        return function (string $atributo, mixed $valor, Closure $fallo): void {
            if (bccomp((string) $valor, '100', 6) >= 0) {
                $fallo('El porcentaje debe ser mayor o igual a 0 y menor a 100.');
            }
        };
    }
}
