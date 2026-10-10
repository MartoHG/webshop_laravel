<?php

namespace App\Http\Requests\Administracion\Precios;

class AplicarActualizacionMasivaRequest extends PrevisualizarActualizacionMasivaRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'huella' => ['required', 'string', 'size:64'],
        ]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'huella.required' => 'Falta el resumen de la vista previa. Volvé a previsualizar antes de aplicar.',
            'huella.string' => 'El resumen de la vista previa no es válido. Volvé a previsualizar antes de aplicar.',
            'huella.size' => 'El resumen de la vista previa no es válido. Volvé a previsualizar antes de aplicar.',
        ]);
    }
}
