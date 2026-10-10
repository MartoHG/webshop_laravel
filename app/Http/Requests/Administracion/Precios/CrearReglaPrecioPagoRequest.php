<?php

namespace App\Http\Requests\Administracion\Precios;

use App\Enums\TipoMedioPago;
use App\Models\Usuario;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CrearReglaPrecioPagoRequest extends FormRequest
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
            'tipo_medio_pago' => ['required', Rule::enum(TipoMedioPago::class)],
            'cuotas' => [
                'bail',
                'required',
                'integer',
                'between:1,24',
                Rule::unique('reglas_precio_pago', 'cuotas')->where('tipo_medio_pago', $this->tipoMedioPagoIngresado()),
            ],
            'porcentaje' => ['bail', 'required', 'string', 'regex:/^\d{1,6}(?:\.\d{1,6})?$/', $this->reglaPorcentajeEnRango()],
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    /** @return list<Closure(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validador): void {
                $tipo = TipoMedioPago::tryFrom($this->tipoMedioPagoIngresado());

                if ($tipo === null || $tipo->admiteCuotas() || $validador->errors()->has('cuotas')) {
                    return;
                }

                if ((int) $this->input('cuotas') !== 1) {
                    $validador->errors()->add('cuotas', 'Este medio de pago solo admite un pago (1 cuota).');
                }
            },
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'tipo_medio_pago.required' => 'Debes indicar el medio de pago.',
            'tipo_medio_pago.enum' => 'El medio de pago no es válido.',
            'cuotas.required' => 'Debes indicar la cantidad de cuotas.',
            'cuotas.integer' => 'La cantidad de cuotas debe ser un número entero.',
            'cuotas.between' => 'La cantidad de cuotas debe estar entre 1 y 24.',
            'cuotas.unique' => 'Ya existe una regla para este medio de pago y cantidad de cuotas.',
            'porcentaje.required' => 'Debes indicar el porcentaje.',
            'porcentaje.regex' => 'El porcentaje debe ser un número con hasta 6 decimales.',
            'activo.boolean' => 'El estado activo debe ser verdadero o falso.',
        ];
    }

    private function tipoMedioPagoIngresado(): string
    {
        $tipo = $this->input('tipo_medio_pago');

        return is_string($tipo) ? $tipo : '';
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
