<?php

namespace App\Servicios\Contenido;

use App\Models\BeneficioHome;
use App\Servicios\Auditoria\AuditoriaService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BeneficioHomeService
{
    public const int MAXIMO_ACTIVOS = 6;

    public function __construct(private AuditoriaService $auditoria) {}

    /** @return Collection<int, BeneficioHome> */
    public function listar(): Collection
    {
        return BeneficioHome::query()->orderBy('orden')->orderBy('id')->get();
    }

    /** @param array{icono: string, texto: string, activo?: bool} $datos */
    public function crear(array $datos): BeneficioHome
    {
        return DB::transaction(function () use ($datos): BeneficioHome {
            $this->bloquearTabla();
            $activo = (bool) ($datos['activo'] ?? true);

            if ($activo) {
                $this->exigirCupoActivo();
            }

            $maximoOrden = BeneficioHome::query()->max('orden');
            $beneficio = BeneficioHome::query()->create([
                'icono' => $datos['icono'],
                'texto' => $datos['texto'],
                'orden' => $maximoOrden === null ? 0 : (int) $maximoOrden + 1,
                'activo' => $activo,
            ]);

            $this->auditoria->registrar('contenido.beneficio_creado', $beneficio, [], $this->atributos($beneficio));

            return $beneficio;
        });
    }

    /** @param array{icono?: string, texto?: string} $datos */
    public function actualizar(BeneficioHome $beneficio, array $datos): BeneficioHome
    {
        return DB::transaction(function () use ($beneficio, $datos): BeneficioHome {
            $actual = BeneficioHome::query()->whereKey($beneficio->getKey())->lockForUpdate()->firstOrFail();
            $anteriores = $this->atributos($actual);

            if (array_key_exists('icono', $datos)) {
                $actual->icono = $datos['icono'];
            }

            if (array_key_exists('texto', $datos)) {
                $actual->texto = $datos['texto'];
            }

            $actual->save();
            $this->auditoria->registrarCambios(
                'contenido.beneficio_actualizado',
                $actual,
                Arr::only($anteriores, ['icono', 'texto']),
                Arr::only($this->atributos($actual), ['icono', 'texto']),
            );

            return $actual;
        });
    }

    public function cambiarEstado(BeneficioHome $beneficio, bool $activo): BeneficioHome
    {
        return DB::transaction(function () use ($beneficio, $activo): BeneficioHome {
            $this->bloquearTabla();
            $actual = BeneficioHome::query()->whereKey($beneficio->getKey())->firstOrFail();

            if ($activo === $actual->activo) {
                return $actual;
            }

            if ($activo) {
                $this->exigirCupoActivo();
            }

            $anterior = $actual->activo;
            $actual->forceFill(['activo' => $activo])->save();
            $this->auditoria->registrarCambios('contenido.beneficio_actualizado', $actual, ['activo' => $anterior], ['activo' => $activo]);

            return $actual;
        });
    }

    /**
     * @param  list<int>  $ids  Orden completo: cada beneficio, activo o no, aparece exactamente una vez.
     *
     * @throws ValidationException
     */
    public function ordenar(array $ids): void
    {
        DB::transaction(function () use ($ids): void {
            $beneficios = BeneficioHome::query()->lockForUpdate()->get()->keyBy('id');
            $esperados = array_map(intval(...), $beneficios->keys()->all());
            $recibidos = array_map(intval(...), $ids);
            sort($esperados);
            sort($recibidos);

            if ($recibidos !== $esperados) {
                throw ValidationException::withMessages(['ids' => 'El nuevo orden debe incluir cada beneficio exactamente una vez.']);
            }

            foreach (array_values($ids) as $posicion => $id) {
                $beneficio = $beneficios->get((int) $id);
                $anterior = (int) $beneficio->orden;

                if ($anterior === $posicion) {
                    continue;
                }

                $beneficio->forceFill(['orden' => $posicion])->save();
                $this->auditoria->registrarCambios('contenido.beneficio_ordenado', $beneficio, ['orden' => $anterior], ['orden' => $posicion]);
            }
        });
    }

    public function eliminar(BeneficioHome $beneficio): void
    {
        DB::transaction(function () use ($beneficio): void {
            $this->bloquearTabla();
            $actual = BeneficioHome::query()->whereKey($beneficio->getKey())->firstOrFail();

            $this->auditoria->registrar('contenido.beneficio_eliminado', $actual, $this->atributos($actual));
            $actual->delete();
        });
    }

    /**
     * Bloquea toda la tabla (son pocas filas) para que dos cambios simultáneos no superen el cupo de activos.
     */
    private function bloquearTabla(): void
    {
        BeneficioHome::query()->lockForUpdate()->get(['id']);
    }

    /**
     * @throws ValidationException
     */
    private function exigirCupoActivo(): void
    {
        if (BeneficioHome::query()->where('activo', true)->count() >= self::MAXIMO_ACTIVOS) {
            throw ValidationException::withMessages([
                'activo' => 'La home admite hasta 6 beneficios activos. Desactivá uno antes de activar otro.',
            ]);
        }
    }

    /** @return array{icono: string, texto: string, orden: int, activo: bool} */
    private function atributos(BeneficioHome $beneficio): array
    {
        return [
            'icono' => $beneficio->icono->value,
            'texto' => $beneficio->texto,
            'orden' => (int) $beneficio->orden,
            'activo' => (bool) $beneficio->activo,
        ];
    }
}
