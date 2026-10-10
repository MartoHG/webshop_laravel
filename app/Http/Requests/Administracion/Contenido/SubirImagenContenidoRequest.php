<?php

namespace App\Http\Requests\Administracion\Contenido;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class SubirImagenContenidoRequest extends FormRequest
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
            'imagen' => [
                'required',
                'file',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:'.config('imagenes.maximo_kilobytes'),
                function (string $atributo, mixed $valor, Closure $fallar): void {
                    $ruta = $valor instanceof UploadedFile ? $valor->getRealPath() : false;
                    $dimensiones = is_string($ruta) ? @getimagesize($ruta) : false;

                    if ($dimensiones !== false && ($dimensiones[0] * $dimensiones[1]) > (int) config('imagenes.maximo_pixeles')) {
                        $fallar('La imagen supera el límite de píxeles permitido.');
                    }
                },
            ],
        ];
    }
}
