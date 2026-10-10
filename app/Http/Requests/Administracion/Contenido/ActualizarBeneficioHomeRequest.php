<?php

namespace App\Http\Requests\Administracion\Contenido;

use App\Enums\IconoBeneficio;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarBeneficioHomeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->rol === 'administrador';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'icono' => ['sometimes', 'required', Rule::enum(IconoBeneficio::class)],
            'texto' => ['sometimes', 'required', 'string', 'max:60'],
        ];
    }
}
