<?php

namespace App\Http\Requests\Administracion\Categorias;

use Illuminate\Foundation\Http\FormRequest;

class AsignarCategoriasProductoRequest extends FormRequest
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
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'categoria_ids' => ['present', 'array'],
            'categoria_ids.*' => ['required', 'integer', 'distinct', 'exists:categorias,id'],
        ];
    }
}
