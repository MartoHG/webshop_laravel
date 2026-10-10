<?php

namespace App\Http\Requests\Tienda;

use App\Http\Requests\Concerns\ReglasDatosEnvio;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmarCheckoutRequest extends FormRequest
{
    use ReglasDatosEnvio;

    protected function prepareForValidation(): void
    {
        $this->normalizarCodigoPostalEnvio();
    }

    public function authorize(): bool
    {
        return $this->user() instanceof Usuario;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            ...$this->reglasDatosEnvio(),
            'huella' => ['required', 'string', 'size:64'],
        ];
    }
}
