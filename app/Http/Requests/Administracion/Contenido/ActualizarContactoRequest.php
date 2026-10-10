<?php

namespace App\Http\Requests\Administracion\Contenido;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ActualizarContactoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->rol === 'administrador';
    }

    /**
     * Acepta el usuario de Instagram con o sin arroba inicial.
     */
    protected function prepareForValidation(): void
    {
        $instagram = $this->input('instagram');

        if (is_string($instagram) && str_starts_with($instagram, '@')) {
            $this->merge(['instagram' => substr($instagram, 1)]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Las reglas de negocio (WhatsApp normalizable, horarios sin superposición) se validan en el servicio.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email:rfc', 'max:120'],
            'direccion' => ['nullable', 'string', 'max:160'],
            'instagram' => ['nullable', 'string', 'regex:/^[A-Za-z0-9._]{1,30}$/'],
            'facebook' => [
                'nullable',
                'string',
                'max:200',
                'url:https',
                function (string $atributo, mixed $valor, Closure $fallar): void {
                    $host = is_string($valor) ? strtolower((string) parse_url($valor, PHP_URL_HOST)) : '';

                    if (! in_array($host, ['facebook.com', 'www.facebook.com'], true)) {
                        $fallar('El enlace de Facebook debe ser una dirección de facebook.com.');
                    }
                },
            ],
            'horarios' => ['nullable', 'array:lunes,martes,miercoles,jueves,viernes,sabado,domingo'],
            'horarios.*' => ['array', 'max:3'],
            'horarios.*.*' => ['array:desde,hasta'],
            'horarios.*.*.desde' => ['required', 'string', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'horarios.*.*.hasta' => ['required', 'string', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
        ];
    }
}
