<?php

namespace App\Http\Requests\Administracion\Precios;

use App\Enums\AlcancePrecioMasivo;
use App\Enums\OperacionPrecioMasiva;
use App\Models\Usuario;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrevisualizarActualizacionMasivaRequest extends FormRequest
{
    private const int MAXIMO_PRODUCTOS = 500;

    protected function prepareForValidation(): void
    {
        $valor = $this->input('valor');

        if (is_int($valor) || is_float($valor)) {
            $this->merge(['valor' => (string) $valor]);
        }
    }

    public function authorize(): bool
    {
        return $this->user() instanceof Usuario && $this->user()->rol === 'administrador' && $this->user()->activo;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $alcance = $this->input('alcance');

        return [
            'operacion' => ['required', Rule::enum(OperacionPrecioMasiva::class)],
            'alcance' => ['required', Rule::enum(AlcancePrecioMasivo::class)],
            'valor' => ['bail', 'required', 'string', $this->reglaValor()],
            'variante_producto_id' => [
                Rule::requiredIf($alcance === AlcancePrecioMasivo::Variante->value),
                'integer',
                'exists:variantes_producto,id',
            ],
            'producto_id' => [
                Rule::requiredIf($alcance === AlcancePrecioMasivo::Producto->value),
                'integer',
                'exists:productos,id',
            ],
            'producto_ids' => [
                Rule::requiredIf($alcance === AlcancePrecioMasivo::Productos->value),
                'array',
                'between:1,'.self::MAXIMO_PRODUCTOS,
            ],
            'producto_ids.*' => ['integer', 'distinct', 'exists:productos,id'],
            'categoria_id' => [
                Rule::requiredIf($alcance === AlcancePrecioMasivo::Categoria->value),
                'integer',
                'exists:categorias,id',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'operacion.required' => 'Debes indicar la operación.',
            'operacion.enum' => 'La operación elegida no es válida.',
            'alcance.required' => 'Debes indicar a qué precios se aplica la actualización.',
            'alcance.enum' => 'El alcance elegido no es válido.',
            'valor.required' => 'Debes indicar el valor.',
            'valor.string' => 'El valor debe ser un número.',
            'variante_producto_id.required' => 'Debes indicar la variante.',
            'variante_producto_id.integer' => 'La variante indicada no es válida.',
            'variante_producto_id.exists' => 'La variante elegida no existe.',
            'producto_id.required' => 'Debes indicar el producto.',
            'producto_id.integer' => 'El producto indicado no es válido.',
            'producto_id.exists' => 'El producto elegido no existe.',
            'producto_ids.required' => 'Debes seleccionar al menos un producto.',
            'producto_ids.array' => 'Los productos deben enviarse como una lista.',
            'producto_ids.between' => 'Debes seleccionar entre 1 y 500 productos.',
            'producto_ids.*.integer' => 'Alguno de los productos indicados no es válido.',
            'producto_ids.*.distinct' => 'No puedes seleccionar el mismo producto dos veces.',
            'producto_ids.*.exists' => 'Alguno de los productos elegidos no existe.',
            'categoria_id.required' => 'Debes indicar la categoría.',
            'categoria_id.integer' => 'La categoría indicada no es válida.',
            'categoria_id.exists' => 'La categoría elegida no existe.',
        ];
    }

    /**
     * Reglas del valor según la operación: importes fijos con hasta 2 decimales y porcentajes con hasta 4.
     * Se comparan con bcmath para no perder precisión.
     */
    private function reglaValor(): Closure
    {
        return function (string $atributo, mixed $valor, Closure $fallo): void {
            $operacion = OperacionPrecioMasiva::tryFrom($this->operacionIngresada());

            if ($operacion === null || ! is_string($valor)) {
                return;
            }

            $esImporte = $operacion === OperacionPrecioMasiva::FijarImporte;
            $decimales = $operacion->decimalesPermitidos();
            $digitosEnteros = $esImporte ? 9 : 4;

            if (preg_match('/^\d{1,'.$digitosEnteros.'}(?:\.\d{1,'.$decimales.'})?$/', $valor) !== 1) {
                $fallo($esImporte
                    ? 'El importe debe ser un número con hasta 2 decimales.'
                    : 'El porcentaje debe ser un número con hasta 4 decimales.');

                return;
            }

            if ($operacion === OperacionPrecioMasiva::AumentarPorcentaje && (bccomp($valor, '0', 4) <= 0 || bccomp($valor, '1000', 4) > 0)) {
                $fallo('El aumento debe ser mayor a 0% y como máximo 1000%.');
            }

            if ($operacion === OperacionPrecioMasiva::DisminuirPorcentaje && (bccomp($valor, '0', 4) <= 0 || bccomp($valor, '100', 4) >= 0)) {
                $fallo('La disminución debe ser mayor a 0% y menor a 100%.');
            }

            if ($esImporte && bccomp($valor, '0', 2) <= 0) {
                $fallo('El importe fijo debe ser mayor a cero.');
            }
        };
    }

    private function operacionIngresada(): string
    {
        $operacion = $this->input('operacion');

        return is_string($operacion) ? $operacion : '';
    }
}
