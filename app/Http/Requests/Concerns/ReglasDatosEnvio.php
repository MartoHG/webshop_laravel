<?php

namespace App\Http\Requests\Concerns;

use App\Enums\Provincia;
use Illuminate\Validation\Rule;

/**
 * Reglas de los datos de envío compartidas por el checkout y la corrección administrativa del envío.
 */
trait ReglasDatosEnvio
{
    /** @return array<string, list<mixed>> */
    protected function reglasDatosEnvio(): array
    {
        return [
            'provincia' => ['required', Rule::enum(Provincia::class)],
            'localidad' => ['required', 'string', 'max:100'],
            'codigo_postal' => ['required', 'string', 'max:10', 'regex:/^(\d{4}|[A-Z]\d{4}[A-Z]{3})$/'],
            'direccion' => ['required', 'string', 'max:160'],
            'numero' => ['required', 'string', 'max:10'],
            'direccion_adicional' => ['nullable', 'string', 'max:60'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function normalizarCodigoPostalEnvio(): void
    {
        $codigoPostal = $this->input('codigo_postal');

        if (is_string($codigoPostal)) {
            $this->merge(['codigo_postal' => strtoupper((string) preg_replace('/\s+/', '', $codigoPostal))]);
        }
    }
}
