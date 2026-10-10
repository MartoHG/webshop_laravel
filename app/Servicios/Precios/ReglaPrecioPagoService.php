<?php

namespace App\Servicios\Precios;

use App\Enums\TipoMedioPago;
use App\Models\ReglaPrecioPago;
use App\Servicios\Auditoria\AuditoriaService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReglaPrecioPagoService
{
    public function __construct(private AuditoriaService $auditoria) {}

    /** @return Collection<int, ReglaPrecioPago> */
    public function listar(): Collection
    {
        return ReglaPrecioPago::query()
            ->orderBy('tipo_medio_pago')
            ->orderBy('cuotas')
            ->get();
    }

    /** @param array{tipo_medio_pago: TipoMedioPago|string, cuotas: int|string, porcentaje: numeric-string|int|float, activo?: bool|string|int} $datos */
    public function crear(array $datos): ReglaPrecioPago
    {
        return DB::transaction(function () use ($datos): ReglaPrecioPago {
            $tipo = $this->tipoDe($datos['tipo_medio_pago']);
            $cuotas = (int) $datos['cuotas'];
            $porcentaje = $this->normalizarPorcentaje($datos['porcentaje']);
            $activo = $this->booleano($datos['activo'] ?? false);

            $this->validarCuotas($tipo, $cuotas);
            $this->validarPorcentaje($porcentaje);

            $existe = ReglaPrecioPago::query()
                ->lockForUpdate()
                ->where('tipo_medio_pago', $tipo)
                ->where('cuotas', $cuotas)
                ->exists();

            if ($existe) {
                throw ValidationException::withMessages([
                    'cuotas' => __('Ya existe una regla para este medio de pago y cantidad de cuotas.'),
                ]);
            }

            $regla = ReglaPrecioPago::query()->create([
                'tipo_medio_pago' => $tipo,
                'cuotas' => $cuotas,
                'porcentaje' => $porcentaje,
                'activo' => $activo,
            ]);

            $this->auditoria->registrar('regla_precio_pago.creada', $regla, [], [
                'tipo_medio_pago' => $tipo->value,
                'cuotas' => $cuotas,
                'porcentaje' => $porcentaje,
                'activo' => $activo,
            ]);

            return $regla->refresh();
        });
    }

    /**
     * Solo cambian el porcentaje y el estado activo: tipo y cuotas son inmutables, para cambiarlos se crea otra regla.
     *
     * @param  array{porcentaje?: numeric-string|int|float, activo?: bool|string|int}  $datos
     */
    public function actualizar(ReglaPrecioPago $regla, array $datos): ReglaPrecioPago
    {
        return DB::transaction(function () use ($regla, $datos): ReglaPrecioPago {
            $regla = ReglaPrecioPago::query()->lockForUpdate()->findOrFail($regla->getKey());
            $cambios = Arr::only($datos, ['porcentaje', 'activo']);

            $anteriores = [
                'porcentaje' => $this->normalizarPorcentaje($regla->porcentaje),
                'activo' => $regla->activo,
            ];
            $nuevos = [
                'porcentaje' => array_key_exists('porcentaje', $cambios)
                    ? $this->normalizarPorcentaje($cambios['porcentaje'])
                    : $anteriores['porcentaje'],
                'activo' => array_key_exists('activo', $cambios)
                    ? $this->booleano($cambios['activo'])
                    : $anteriores['activo'],
            ];

            $this->validarPorcentaje($nuevos['porcentaje']);

            $regla->porcentaje = $nuevos['porcentaje'];
            $regla->activo = $nuevos['activo'];
            $regla->save();

            $this->auditoria->registrarCambios('regla_precio_pago.actualizada', $regla, $anteriores, $nuevos);

            return $regla->refresh();
        });
    }

    private function tipoDe(TipoMedioPago|string $tipo): TipoMedioPago
    {
        $tipoMedioPago = $tipo instanceof TipoMedioPago ? $tipo : TipoMedioPago::tryFrom($tipo);

        if ($tipoMedioPago === null) {
            throw ValidationException::withMessages(['tipo_medio_pago' => __('El medio de pago no es válido.')]);
        }

        return $tipoMedioPago;
    }

    private function validarCuotas(TipoMedioPago $tipo, int $cuotas): void
    {
        if ($cuotas < 1) {
            throw ValidationException::withMessages(['cuotas' => __('La cantidad de cuotas debe ser al menos 1.')]);
        }

        if ($cuotas !== 1 && ! $tipo->admiteCuotas()) {
            throw ValidationException::withMessages(['cuotas' => __('Este medio de pago solo admite un pago (1 cuota).')]);
        }
    }

    private function validarPorcentaje(string $porcentaje): void
    {
        if (bccomp($porcentaje, '0', 6) < 0 || bccomp($porcentaje, '100', 6) >= 0) {
            throw ValidationException::withMessages([
                'porcentaje' => __('El porcentaje debe ser mayor o igual a 0 y menor a 100.'),
            ]);
        }
    }

    private function normalizarPorcentaje(string|int|float $porcentaje): string
    {
        $normalizado = bcadd((string) $porcentaje, '0', 6);

        return rtrim(rtrim($normalizado, '0'), '.');
    }

    private function booleano(mixed $valor): bool
    {
        return filter_var($valor, FILTER_VALIDATE_BOOLEAN);
    }
}
