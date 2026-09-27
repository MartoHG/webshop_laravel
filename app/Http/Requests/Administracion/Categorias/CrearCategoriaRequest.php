<?php

namespace App\Http\Requests\Administracion\Categorias;

use Illuminate\Foundation\Http\FormRequest;

class CrearCategoriaRequest extends FormRequest
{
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
            'nombre' => ['required', 'string', 'min:2', 'max:120'],
            'slug' => ['nullable', 'string', 'max:180', 'regex:/^[\pL\pN]+(?:-[\pL\pN]+)*$/u', 'unique:categorias,slug'],
            'orden' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
