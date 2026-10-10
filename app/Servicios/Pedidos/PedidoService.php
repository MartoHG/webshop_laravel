<?php

namespace App\Servicios\Pedidos;

use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Enums\MotivoCancelacion;
use App\Models\ItemPedido;
use App\Models\Pedido;
use App\Models\Usuario;
use App\Servicios\Auditoria\AuditoriaService;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PedidoService
{
    private const int POR_PAGINA_CLIENTE = 20;

    private const int POR_PAGINA_ADMINISTRACION = 30;

    public function __construct(
        private AuditoriaService $auditoria,
        private WhatsAppEnlaceService $whatsapp,
    ) {}

    /** @return LengthAwarePaginator<int, Pedido> */
    public function listarDelCliente(Usuario $usuario): LengthAwarePaginator
    {
        return Pedido::query()
            ->where('usuario_id', $usuario->getKey())
            ->with('items')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::POR_PAGINA_CLIENTE);
    }

    public function mostrarDelCliente(Usuario $usuario, string $numero): Pedido
    {
        return Pedido::query()
            ->where('usuario_id', $usuario->getKey())
            ->where('numero', $numero)
            ->with('items')
            ->firstOrFail();
    }

    /**
     * @param  array{estado_pedido?: string, estado_contacto?: string, estado_envio?: string, estado_pago?: string, buscar?: string, desde?: string, hasta?: string}  $filtros
     * @return LengthAwarePaginator<int, Pedido>
     */
    public function listarParaAdministracion(array $filtros): LengthAwarePaginator
    {
        return Pedido::query()
            ->with(['items', 'canceladoPor'])
            ->when($filtros['estado_pedido'] ?? null, fn (Builder $consulta, string $estado): Builder => $consulta->where('estado_pedido', $estado))
            ->when($filtros['estado_contacto'] ?? null, fn (Builder $consulta, string $estado): Builder => $consulta->where('estado_contacto', $estado))
            ->when($filtros['estado_envio'] ?? null, fn (Builder $consulta, string $estado): Builder => $consulta->where('estado_envio', $estado))
            ->when($filtros['estado_pago'] ?? null, fn (Builder $consulta, string $estado): Builder => $consulta->where('estado_pago', $estado))
            ->when($filtros['buscar'] ?? null, fn (Builder $consulta, string $texto): Builder => $this->aplicarBusqueda($consulta, $texto))
            ->when($filtros['desde'] ?? null, fn (Builder $consulta, string $desde): Builder => $consulta->where('created_at', '>=', Carbon::parse($desde)->startOfDay()))
            ->when($filtros['hasta'] ?? null, fn (Builder $consulta, string $hasta): Builder => $consulta->where('created_at', '<=', Carbon::parse($hasta)->endOfDay()))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::POR_PAGINA_ADMINISTRACION);
    }

    public function mostrarParaAdministracion(Pedido $pedido): Pedido
    {
        return $pedido->load(['items', 'canceladoPor']);
    }

    /**
     * Solo se cancela antes de que haya un pago confirmado o en curso. Debe quedar auditado.
     *
     * @throws ValidationException
     */
    public function cancelar(Usuario $administrador, Pedido $pedido, MotivoCancelacion $motivo, ?string $detalle): Pedido
    {
        $detalle = $this->normalizarDetalle($detalle);

        return DB::transaction(function () use ($administrador, $pedido, $motivo, $detalle): Pedido {
            $pedido = Pedido::query()->lockForUpdate()->findOrFail($pedido->getKey());

            $this->exigirCancelable($pedido, $motivo, $detalle);

            $anteriores = [
                'estado_pedido' => $pedido->estado_pedido->value,
                'estado_pago' => $pedido->estado_pago->value,
            ];

            $pedido->estado_pedido = EstadoPedido::Cancelado;
            $pedido->estado_pago = EstadoPago::Cancelado;
            $pedido->cancelado_en = now();
            $pedido->cancelado_por = $administrador->getKey();
            $pedido->motivo_cancelacion = $motivo;
            $pedido->detalle_cancelacion = $detalle;
            $pedido->token_pago = null;
            $pedido->save();

            $this->auditoria->registrar('pedido.cancelado', $pedido, $anteriores, [
                'estado_pedido' => EstadoPedido::Cancelado->value,
                'estado_pago' => EstadoPago::Cancelado->value,
                'motivo_cancelacion' => $motivo->value,
                'detalle_cancelacion' => $detalle,
            ], $administrador);

            return $pedido->refresh()->load(['items', 'canceladoPor']);
        });
    }

    /**
     * Vista del cliente: no expone el token de pago, los ids internos ni datos de administración.
     *
     * @return array<string, mixed>
     */
    public function representarParaCliente(Pedido $pedido): array
    {
        return [
            'numero' => $pedido->numero,
            'estado_pedido' => $pedido->estado_pedido->value,
            'estado_envio' => $pedido->estado_envio->value,
            'estado_pago' => $pedido->estado_pago->value,
            'creado_en' => $this->fecha($pedido->created_at),
            'cancelado_en' => $this->fecha($pedido->cancelado_en),
            'subtotal_productos' => $pedido->subtotal_productos,
            'costo_envio' => $pedido->costo_envio,
            'total_base' => $pedido->total_base,
            'cliente' => [
                'nombre' => $pedido->nombre_cliente,
                'apellido' => $pedido->apellido_cliente,
                'dni' => $pedido->dni_cliente,
                'email' => $pedido->email_cliente,
                'telefono' => $pedido->telefono_cliente,
            ],
            'envio' => $this->envioDe($pedido),
            'items' => $pedido->items->map(fn (ItemPedido $item): array => $this->representarItem($item))->values()->all(),
        ];
    }

    /**
     * Vista administrativa: incluye estados internos, fechas de gestión, auditoría de cancelación y ids.
     *
     * @return array<string, mixed>
     */
    public function representarParaAdministracion(Pedido $pedido): array
    {
        return [
            'id' => $pedido->id,
            'numero' => $pedido->numero,
            'usuario_id' => $pedido->usuario_id,
            'pedido_origen_id' => $pedido->pedido_origen_id,
            'estado_pedido' => $pedido->estado_pedido->value,
            'estado_contacto' => $pedido->estado_contacto->value,
            'estado_envio' => $pedido->estado_envio->value,
            'estado_pago' => $pedido->estado_pago->value,
            'creado_en' => $this->fecha($pedido->created_at),
            'contactado_en' => $this->fecha($pedido->contactado_en),
            'pago_habilitado_en' => $this->fecha($pedido->pago_habilitado_en),
            'pagado_en' => $this->fecha($pedido->pagado_en),
            'preparado_en' => $this->fecha($pedido->preparado_en),
            'despachado_en' => $this->fecha($pedido->despachado_en),
            'entregado_en' => $this->fecha($pedido->entregado_en),
            'cancelado_en' => $this->fecha($pedido->cancelado_en),
            'cancelado_por' => $pedido->canceladoPor === null ? null : [
                'id' => $pedido->canceladoPor->id,
                'nombre' => $pedido->canceladoPor->nombre,
                'apellido' => $pedido->canceladoPor->apellido,
            ],
            'motivo_cancelacion' => $pedido->motivo_cancelacion?->value,
            'detalle_cancelacion' => $pedido->detalle_cancelacion,
            'expreso' => $pedido->expreso,
            'subtotal_productos' => $pedido->subtotal_productos,
            'costo_envio' => $pedido->costo_envio,
            'total_base' => $pedido->total_base,
            'cliente' => [
                'nombre' => $pedido->nombre_cliente,
                'apellido' => $pedido->apellido_cliente,
                'dni' => $pedido->dni_cliente,
                'email' => $pedido->email_cliente,
                'telefono' => $pedido->telefono_cliente,
            ],
            'envio' => $this->envioDe($pedido),
            'items' => $pedido->items->map(fn (ItemPedido $item): array => [
                'producto_id' => $item->producto_id,
                'variante_producto_id' => $item->variante_producto_id,
                ...$this->representarItem($item),
            ])->values()->all(),
            'whatsapp' => $this->whatsapp->enlacesDelPedido($pedido),
        ];
    }

    private function aplicarBusqueda(Builder $consulta, string $texto): Builder
    {
        $patron = '%'.addcslashes($texto, '%_\\').'%';

        return $consulta->where(function (Builder $coincidencia) use ($patron): void {
            $coincidencia
                ->where('numero', 'like', $patron)
                ->orWhere('dni_cliente', 'like', $patron)
                ->orWhere('email_cliente', 'like', $patron)
                ->orWhere('apellido_cliente', 'like', $patron);
        });
    }

    private function exigirCancelable(Pedido $pedido, MotivoCancelacion $motivo, ?string $detalle): void
    {
        if ($pedido->estado_pedido === EstadoPedido::Cancelado) {
            throw ValidationException::withMessages(['pedido' => 'El pedido ya está cancelado.']);
        }

        if (in_array($pedido->estado_pago, [EstadoPago::Pagado, EstadoPago::Pendiente], true)) {
            throw ValidationException::withMessages([
                'pedido' => 'No se puede cancelar un pedido con el pago confirmado o pendiente.',
            ]);
        }

        if (! $motivo->elegibleManualmente()) {
            throw ValidationException::withMessages([
                'motivo' => 'El motivo reemplazado lo asigna el sistema y no puede elegirse manualmente.',
            ]);
        }

        if ($motivo === MotivoCancelacion::Otro && $detalle === null) {
            throw ValidationException::withMessages(['detalle' => 'Indicá el detalle cuando el motivo es otro.']);
        }
    }

    private function normalizarDetalle(?string $detalle): ?string
    {
        $limpio = trim((string) $detalle);

        return $limpio === '' ? null : $limpio;
    }

    /** @return array<string, mixed> */
    private function envioDe(Pedido $pedido): array
    {
        return [
            'provincia' => $pedido->provincia_envio->value,
            'provincia_nombre' => $pedido->provincia_envio->nombre(),
            'localidad' => $pedido->localidad_envio,
            'codigo_postal' => $pedido->codigo_postal_envio,
            'direccion' => $pedido->direccion_envio,
            'numero' => $pedido->numero_envio,
            'direccion_adicional' => $pedido->direccion_adicional,
            'observaciones' => $pedido->observaciones_envio,
        ];
    }

    /** @return array<string, mixed> */
    private function representarItem(ItemPedido $item): array
    {
        return [
            'nombre_producto' => $item->nombre_producto,
            'color' => $item->color,
            'etiqueta' => $item->etiqueta,
            'sku' => $item->sku,
            'precio_unitario' => $item->precio_unitario,
            'cantidad' => $item->cantidad,
            'subtotal' => $item->subtotal,
        ];
    }

    private function fecha(?CarbonInterface $fecha): ?string
    {
        return $fecha?->toIso8601String();
    }
}
