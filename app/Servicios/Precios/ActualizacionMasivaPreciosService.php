<?php

namespace App\Servicios\Precios;

use App\Enums\AlcancePrecioMasivo;
use App\Enums\OperacionPrecioMasiva;
use App\Models\HistorialActualizacionPrecios;
use App\Models\Usuario;
use App\Models\VarianteProducto;
use App\Servicios\Auditoria\AuditoriaService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RoundingMode;

class ActualizacionMasivaPreciosService
{
    private const string ACCION_AUDITORIA = 'precios.actualizacion_masiva';

    private const int REGISTROS_POR_PAGINA = 30;

    private const string PRECIO_MAXIMO = '9999999999999.99';

    public function __construct(private AuditoriaService $auditoria) {}

    /**
     * Calcula los precios nuevos sin modificar nada. Solo incluye las variantes cuyo precio cambia.
     *
     * @param  array<string, mixed>  $parametros
     * @return array{filas: list<array<string, mixed>>, cantidad: int, huella: string}
     */
    public function previsualizar(array $parametros): array
    {
        $normalizados = $this->normalizarParametros($parametros);
        $filas = $this->calcularFilas($normalizados);
        $this->validarFilas($filas);

        return [
            'filas' => $filas,
            'cantidad' => count($filas),
            'huella' => $this->huella($normalizados, $filas),
        ];
    }

    /**
     * Bloquea las variantes alcanzadas, recalcula la vista previa dentro de la transacción y aplica solo si
     * coincide con la huella que vio el administrador. El bloqueo va primero para que la lectura posterior
     * vea precios confirmados y no una instantánea anterior.
     *
     * @param  array<string, mixed>  $parametros
     */
    public function aplicar(Usuario $administrador, array $parametros, string $huella): HistorialActualizacionPrecios
    {
        return DB::transaction(function () use ($administrador, $parametros, $huella): HistorialActualizacionPrecios {
            $normalizados = $this->normalizarParametros($parametros);
            $this->bloquearVariantes($normalizados);

            $filas = $this->calcularFilas($normalizados);

            if (! hash_equals($huella, $this->huella($normalizados, $filas))) {
                throw ValidationException::withMessages([
                    'huella' => __('Los precios cambiaron desde la vista previa. Volvé a previsualizar antes de aplicar.'),
                ]);
            }

            $this->validarFilas($filas);

            foreach ($filas as $fila) {
                VarianteProducto::query()
                    ->whereKey($fila['variante_producto_id'])
                    ->update(['precio_base' => $fila['precio_nuevo']]);
            }

            $historial = HistorialActualizacionPrecios::query()->create([
                'usuario_id' => $administrador->getKey(),
                'operacion' => $normalizados['operacion'],
                'valor' => $normalizados['valor'],
                'alcance' => $normalizados['alcance'],
                'filtro' => $normalizados['filtro'],
                'cantidad_afectados' => count($filas),
                'detalle' => array_map(fn (array $fila): array => [
                    'variante_producto_id' => $fila['variante_producto_id'],
                    'sku' => $fila['sku'],
                    'precio_anterior' => $fila['precio_anterior'],
                    'precio_nuevo' => $fila['precio_nuevo'],
                ], $filas),
            ]);

            $this->auditoria->registrar(self::ACCION_AUDITORIA, $historial, [], [
                'historial_id' => $historial->getKey(),
                'operacion' => $normalizados['operacion']->value,
                'valor' => $normalizados['valor'],
                'alcance' => $normalizados['alcance']->value,
                'cantidad_afectados' => count($filas),
            ], $administrador);

            return $historial;
        });
    }

    /** @return LengthAwarePaginator<int, HistorialActualizacionPrecios> */
    public function listarHistorial(): LengthAwarePaginator
    {
        return HistorialActualizacionPrecios::query()
            ->select(['id', 'usuario_id', 'operacion', 'valor', 'alcance', 'filtro', 'cantidad_afectados', 'created_at'])
            ->with('usuario:id,nombre,apellido')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::REGISTROS_POR_PAGINA);
    }

    public function mostrarHistorial(HistorialActualizacionPrecios $historial): HistorialActualizacionPrecios
    {
        return $historial->load('usuario:id,nombre,apellido');
    }

    /**
     * @param  array<string, mixed>  $parametros
     * @return array{operacion: OperacionPrecioMasiva, valor: string, alcance: AlcancePrecioMasivo, filtro: ?array<string, mixed>}
     */
    private function normalizarParametros(array $parametros): array
    {
        $operacion = OperacionPrecioMasiva::from((string) $parametros['operacion']);
        $alcance = AlcancePrecioMasivo::from((string) $parametros['alcance']);

        return [
            'operacion' => $operacion,
            'valor' => $this->normalizarValor($operacion, (string) $parametros['valor']),
            'alcance' => $alcance,
            'filtro' => $this->normalizarFiltro($alcance, $parametros),
        ];
    }

    private function normalizarValor(OperacionPrecioMasiva $operacion, string $valor): string
    {
        $redondeado = bcadd($valor, '0', $operacion->decimalesPermitidos());

        return rtrim(rtrim($redondeado, '0'), '.');
    }

    /**
     * @param  array<string, mixed>  $parametros
     * @return ?array<string, mixed>
     */
    private function normalizarFiltro(AlcancePrecioMasivo $alcance, array $parametros): ?array
    {
        return match ($alcance) {
            AlcancePrecioMasivo::Variante => ['variante_producto_id' => (int) $parametros['variante_producto_id']],
            AlcancePrecioMasivo::Producto => ['producto_id' => (int) $parametros['producto_id']],
            AlcancePrecioMasivo::Productos => ['producto_ids' => $this->ordenarIds($parametros['producto_ids'])],
            AlcancePrecioMasivo::Categoria => ['categoria_id' => (int) $parametros['categoria_id']],
            AlcancePrecioMasivo::Todos => null,
        };
    }

    /**
     * @param  array<array-key, mixed>  $ids
     * @return list<int>
     */
    private function ordenarIds(array $ids): array
    {
        $enteros = array_values(array_unique(array_map(intval(...), $ids)));
        sort($enteros);

        return $enteros;
    }

    /**
     * Variantes alcanzadas por el filtro, incluidas las inactivas y las de productos inactivos.
     *
     * @param  array{operacion: OperacionPrecioMasiva, valor: string, alcance: AlcancePrecioMasivo, filtro: ?array<string, mixed>}  $normalizados
     */
    private function consultaFiltrada(array $normalizados): Builder
    {
        $consulta = VarianteProducto::query();
        $filtro = $normalizados['filtro'];

        return match ($normalizados['alcance']) {
            AlcancePrecioMasivo::Variante => $consulta->where('variantes_producto.id', $filtro['variante_producto_id']),
            AlcancePrecioMasivo::Producto => $consulta->where('variantes_producto.producto_id', $filtro['producto_id']),
            AlcancePrecioMasivo::Productos => $consulta->whereIn('variantes_producto.producto_id', $filtro['producto_ids']),
            AlcancePrecioMasivo::Categoria => $consulta->whereIn(
                'variantes_producto.producto_id',
                DB::table('categoria_producto')->select('producto_id')->where('categoria_id', $filtro['categoria_id']),
            ),
            AlcancePrecioMasivo::Todos => $consulta,
        };
    }

    /**
     * @param  array{operacion: OperacionPrecioMasiva, valor: string, alcance: AlcancePrecioMasivo, filtro: ?array<string, mixed>}  $normalizados
     */
    private function bloquearVariantes(array $normalizados): void
    {
        $this->consultaFiltrada($normalizados)
            ->orderBy('variantes_producto.id')
            ->lockForUpdate()
            ->get(['variantes_producto.id']);
    }

    /**
     * @param  array{operacion: OperacionPrecioMasiva, valor: string, alcance: AlcancePrecioMasivo, filtro: ?array<string, mixed>}  $normalizados
     * @return list<array<string, mixed>>
     */
    private function calcularFilas(array $normalizados): array
    {
        return $this->consultaFiltrada($normalizados)
            ->join('productos', 'productos.id', '=', 'variantes_producto.producto_id')
            ->select(['variantes_producto.*', 'productos.nombre as nombre_producto'])
            ->orderBy('productos.nombre')
            ->orderBy('variantes_producto.id')
            ->get()
            ->map(fn (VarianteProducto $variante): array => [
                'variante_producto_id' => $variante->id,
                'producto_id' => $variante->producto_id,
                'nombre_producto' => $variante->nombre_producto,
                'color' => $variante->color,
                'etiqueta' => $variante->etiqueta,
                'sku' => $variante->sku,
                'activo' => $variante->activo,
                'precio_anterior' => (string) $variante->precio_base,
                'precio_nuevo' => $this->calcularPrecioNuevo(
                    $normalizados['operacion'],
                    $normalizados['valor'],
                    (string) $variante->precio_base,
                ),
            ])
            ->filter(fn (array $fila): bool => bccomp($fila['precio_nuevo'], $fila['precio_anterior'], 2) !== 0)
            ->values()
            ->all();
    }

    private function calcularPrecioNuevo(OperacionPrecioMasiva $operacion, string $valor, string $precioAnterior): string
    {
        $precioCalculado = match ($operacion) {
            OperacionPrecioMasiva::AumentarPorcentaje => bcmul(
                $precioAnterior,
                bcadd('1', bcdiv($valor, '100', 12), 12),
                12,
            ),
            OperacionPrecioMasiva::DisminuirPorcentaje => bcmul(
                $precioAnterior,
                bcsub('1', bcdiv($valor, '100', 12), 12),
                12,
            ),
            OperacionPrecioMasiva::FijarImporte => $valor,
        };

        return bcadd(bcround($precioCalculado, 2, RoundingMode::HalfAwayFromZero), '0', 2);
    }

    /** @param list<array<string, mixed>> $filas */
    private function validarFilas(array $filas): void
    {
        if ($filas === []) {
            throw ValidationException::withMessages(['valor' => __('Con estos datos ningún precio cambia.')]);
        }

        foreach ($filas as $fila) {
            if (bccomp($fila['precio_nuevo'], '0', 2) <= 0) {
                throw ValidationException::withMessages([
                    'valor' => __('El precio de :sku quedaría en cero o menos. Se rechazó toda la actualización.', ['sku' => $fila['sku']]),
                ]);
            }

            if (bccomp($fila['precio_nuevo'], self::PRECIO_MAXIMO, 2) > 0) {
                throw ValidationException::withMessages([
                    'valor' => __('El precio de :sku supera el máximo permitido. Se rechazó toda la actualización.', ['sku' => $fila['sku']]),
                ]);
            }
        }
    }

    /**
     * @param  array{operacion: OperacionPrecioMasiva, valor: string, alcance: AlcancePrecioMasivo, filtro: ?array<string, mixed>}  $normalizados
     * @param  list<array<string, mixed>>  $filas
     */
    private function huella(array $normalizados, array $filas): string
    {
        $parametros = [
            'operacion' => $normalizados['operacion']->value,
            'valor' => $normalizados['valor'],
            'alcance' => $normalizados['alcance']->value,
            'filtro' => $normalizados['filtro'],
        ];

        $cambios = collect($filas)
            ->sortBy('variante_producto_id')
            ->map(fn (array $fila): array => [$fila['variante_producto_id'], $fila['precio_anterior'], $fila['precio_nuevo']])
            ->values()
            ->all();

        return hash('sha256', json_encode(['parametros' => $parametros, 'cambios' => $cambios], JSON_THROW_ON_ERROR));
    }
}
