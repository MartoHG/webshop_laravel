<?php

namespace App\Servicios\Auditoria;

use App\Models\RegistroAuditoria;
use App\Models\Usuario;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuditoriaService
{
    /** @var list<string> */
    private const array FRAGMENTOS_SENSIBLES = ['password', 'remember_token', 'token', 'clave', 'secret'];

    /**
     * Registra una acción sensible. Debe invocarse dentro de la misma transacción que el cambio auditado,
     * para que una operación revertida no deje registro.
     *
     * @param  array<array-key, mixed>  $datosAnteriores
     * @param  array<array-key, mixed>  $datosNuevos
     */
    public function registrar(string $accion, Model $entidad, array $datosAnteriores = [], array $datosNuevos = [], ?Usuario $usuario = null): RegistroAuditoria
    {
        $autor = $usuario ?? Auth::user();

        return RegistroAuditoria::query()->create([
            'usuario_id' => $autor instanceof Usuario ? $autor->getKey() : null,
            'accion' => $accion,
            'entidad' => $entidad->getTable(),
            'entidad_id' => $entidad->getKey(),
            'datos_anteriores' => $this->datosSeguros($datosAnteriores),
            'datos_nuevos' => $this->datosSeguros($datosNuevos),
            'ip' => $this->ipDeLaSolicitud(),
        ]);
    }

    /**
     * Registra solo los campos cuyo valor cambió y no crea registro si ninguno cambió.
     *
     * @param  array<string, mixed>  $anteriores
     * @param  array<string, mixed>  $nuevos
     */
    public function registrarCambios(string $accion, Model $entidad, array $anteriores, array $nuevos, ?Usuario $usuario = null): ?RegistroAuditoria
    {
        $camposCambiados = array_keys(array_filter(
            $nuevos,
            fn (mixed $valor, string $campo): bool => ($anteriores[$campo] ?? null) !== $valor,
            ARRAY_FILTER_USE_BOTH,
        ));

        if ($camposCambiados === []) {
            return null;
        }

        return $this->registrar(
            $accion,
            $entidad,
            Arr::only($anteriores, $camposCambiados),
            Arr::only($nuevos, $camposCambiados),
            $usuario,
        );
    }

    /**
     * @param  array{entidad?: ?string, entidad_id?: ?int, usuario_id?: ?int, accion?: ?string, desde?: ?string, hasta?: ?string}  $filtros
     * @return LengthAwarePaginator<int, RegistroAuditoria>
     */
    public function listar(array $filtros): LengthAwarePaginator
    {
        return RegistroAuditoria::query()
            ->with('usuario:id,nombre,apellido,email')
            ->when($filtros['entidad'] ?? null, fn (Builder $consulta, string $entidad): Builder => $consulta->where('entidad', $entidad))
            ->when($filtros['entidad_id'] ?? null, fn (Builder $consulta, string|int $entidadId): Builder => $consulta->where('entidad_id', $entidadId))
            ->when($filtros['usuario_id'] ?? null, fn (Builder $consulta, string|int $usuarioId): Builder => $consulta->where('usuario_id', $usuarioId))
            ->when($filtros['accion'] ?? null, fn (Builder $consulta, string $accion): Builder => $consulta->where('accion', $accion))
            ->when($filtros['desde'] ?? null, fn (Builder $consulta, string $desde): Builder => $consulta->where('created_at', '>=', Carbon::parse($desde)->startOfDay()))
            ->when($filtros['hasta'] ?? null, fn (Builder $consulta, string $hasta): Builder => $consulta->where('created_at', '<=', Carbon::parse($hasta)->endOfDay()))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(50);
    }

    /**
     * Solo las solicitudes HTTP enrutadas tienen IP: en consola no hay ruta resuelta y la solicitud
     * por defecto informa una IP ficticia.
     */
    private function ipDeLaSolicitud(): ?string
    {
        $solicitud = request();

        return $solicitud->route() === null ? null : $solicitud->ip();
    }

    /**
     * @param  array<array-key, mixed>  $datos
     * @return array<array-key, mixed>|null
     */
    private function datosSeguros(array $datos): ?array
    {
        $seguros = $this->quitarClavesSensibles($datos);

        return $seguros === [] ? null : $seguros;
    }

    /**
     * @param  array<array-key, mixed>  $datos
     * @return array<array-key, mixed>
     */
    private function quitarClavesSensibles(array $datos): array
    {
        $limpios = [];

        foreach ($datos as $clave => $valor) {
            if (Str::contains(mb_strtolower((string) $clave), self::FRAGMENTOS_SENSIBLES)) {
                continue;
            }

            $limpios[$clave] = is_array($valor) ? $this->quitarClavesSensibles($valor) : $valor;
        }

        return $limpios;
    }
}
