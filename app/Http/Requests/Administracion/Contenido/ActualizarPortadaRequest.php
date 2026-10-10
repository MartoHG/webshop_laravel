<?php

namespace App\Http\Requests\Administracion\Contenido;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ActualizarPortadaRequest extends FormRequest
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
     * Solo se aceptan rutas internas: empiezan con "/", no con "//" ni con "/\" (los navegadores tratan esas
     * variantes como un host externo) y no contienen espacios ni barras invertidas.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:80'],
            'subtitulo' => ['nullable', 'string', 'max:200'],
            'boton_texto' => ['nullable', 'string', 'max:30'],
            'boton_destino' => ['nullable', 'string', 'max:200', 'regex:#^/(?![/\\\\])[^\s\\\\]*$#'],
        ];
    }
}
