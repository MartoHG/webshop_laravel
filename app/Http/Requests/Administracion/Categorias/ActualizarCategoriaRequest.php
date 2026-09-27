<?php

namespace App\Http\Requests\Administracion\Categorias;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarCategoriaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'required', 'string', 'min:2', 'max:120'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:180', 'regex:/^[\pL\pN]+(?:-[\pL\pN]+)*$/u', Rule::unique('categorias', 'slug')->ignore((int) $this->route('categoria'))],
            'orden' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
