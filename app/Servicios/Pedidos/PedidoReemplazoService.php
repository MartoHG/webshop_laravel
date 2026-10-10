<?php

namespace App\Servicios\Pedidos;

use App\Enums\EstadoEnvio;
use App\Enums\EstadoIntentoPago;
use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Enums\MotivoCancelacion;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Usuario;
use App\Servicios\Auditoria\AuditoriaService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PedidoReemplazoService
{
    /** @var list<string> */
    private const array CAMPOS_ITEM = [
        'producto_id',
        'variante_producto_id',
        'nombre_producto',
        'color',
        'etiqueta',
        'sku',
        'precio_unitario',
        'cantidad',
        'subtotal',
    ];

    public function __construct(private AuditoriaService $auditoria) {}

    /**
     * Cancela el pedido que tiene orden de pago y crea uno nuevo con sus datos, envío e items, para que el
     * administrador lo edite y vuelva a habilitar el pago. Todo ocurre en una transacción.
     *
     * @throws ValidationException
     */
    public function reemplazar(Usuario $administrador, Pedido $original, ?string $nota): Pedido
    {
        $nota = $this->normalizarNota($nota);

        return DB::transaction(function () use ($administrador, $original, $nota): Pedido {
            $original = Pedido::query()->lockForUpdate()->findOrFail($original->getKey());
            $this->exigirReemplazable($original);
            $original->load('items');

            $nuevo = $this->crearPedidoNuevo($original);

            $anteriores = [
                'estado_pedido' => $original->estado_pedido->value,
                'estado_pago' => $original->estado_pago->value,
            ];

            $original->estado_pedido = EstadoPedido::Cancelado;
            $original->estado_pago = EstadoPago::Cancelado;
            $original->motivo_cancelacion = MotivoCancelacion::Reemplazado;
            $original->detalle_cancelacion = $nota;
            $original->cancelado_en = now();
            $original->cancelado_por = $administrador->getKey();
            $original->token_pago = null;
            $original->save();

            Pago::query()
                ->where('pedido_id', $original->getKey())
                ->where('estado', EstadoIntentoPago::Creado->value)
                ->update(['estado' => EstadoIntentoPago::Cancelado->value, 'updated_at' => now()]);

            $this->auditoria->registrar('pedido.reemplazado', $original, $anteriores, [
                'estado_pedido' => EstadoPedido::Cancelado->value,
                'estado_pago' => EstadoPago::Cancelado->value,
                'motivo_cancelacion' => MotivoCancelacion::Reemplazado->value,
                'detalle_cancelacion' => $nota,
                'reemplazado_por' => $nuevo->numero,
            ], $administrador);

            $this->auditoria->registrar('pedido.creado_por_reemplazo', $nuevo, [], [
                'reemplaza_a' => $original->numero,
                'estado_pago' => EstadoPago::Deshabilitado->value,
            ], $administrador);

            return $nuevo->refresh()->load(['items', 'canceladoPor']);
        });
    }

    /**
     * Solo un pedido con orden de pago vigente se reemplaza. Un pago en curso o en revisión bloquea el reemplazo
     * porque el dinero puede estar entrando.
     *
     * @throws ValidationException
     */
    private function exigirReemplazable(Pedido $pedido): void
    {
        if ($pedido->estado_pedido === EstadoPedido::Cancelado || $pedido->estado_pago === EstadoPago::Cancelado) {
            throw ValidationException::withMessages(['pedido' => 'El pedido está cancelado y no se puede reemplazar.']);
        }

        if ($pedido->estado_pedido !== EstadoPedido::PendienteCoordinacion || $pedido->estado_pago === EstadoPago::Pagado) {
            throw ValidationException::withMessages(['pedido' => 'El pedido ya está pagado y no se puede reemplazar.']);
        }

        if ($pedido->estado_pago === EstadoPago::Pendiente) {
            throw ValidationException::withMessages([
                'pedido' => 'El pago está en curso. Esperá a que se resuelva antes de reemplazar el pedido.',
            ]);
        }

        if ($pedido->estado_pago === EstadoPago::Deshabilitado) {
            throw ValidationException::withMessages([
                'pedido' => 'El pedido todavía no tiene orden de pago. Editalo directamente.',
            ]);
        }

        $pagoEnCurso = Pago::query()
            ->where('pedido_id', $pedido->getKey())
            ->whereIn('estado', [EstadoIntentoPago::Pendiente->value, EstadoIntentoPago::RequiereRevision->value])
            ->exists();

        if ($pagoEnCurso) {
            throw ValidationException::withMessages([
                'pedido' => 'Hay un pago pendiente o en revisión. Esperá a que se resuelva antes de reemplazar el pedido.',
            ]);
        }
    }

    /**
     * Copia cliente, envío, costo, estado de contacto y líneas. El envío vuelve a cotizado para que el cliente
     * acepte de nuevo, y el pago queda deshabilitado hasta que el administrador lo vuelva a habilitar.
     */
    private function crearPedidoNuevo(Pedido $original): Pedido
    {
        $nuevo = Pedido::query()->create([
            'usuario_id' => $original->usuario_id,
            'pedido_origen_id' => $original->getKey(),
            'nombre_cliente' => $original->nombre_cliente,
            'apellido_cliente' => $original->apellido_cliente,
            'dni_cliente' => $original->dni_cliente,
            'email_cliente' => $original->email_cliente,
            'telefono_cliente' => $original->telefono_cliente,
            'provincia_envio' => $original->provincia_envio,
            'localidad_envio' => $original->localidad_envio,
            'codigo_postal_envio' => $original->codigo_postal_envio,
            'direccion_envio' => $original->direccion_envio,
            'numero_envio' => $original->numero_envio,
            'direccion_adicional' => $original->direccion_adicional,
            'observaciones_envio' => $original->observaciones_envio,
            'expreso' => $original->expreso,
            'subtotal_productos' => $original->subtotal_productos,
            'costo_envio' => $original->costo_envio,
            'total_base' => $original->total_base,
            'estado_pedido' => EstadoPedido::PendienteCoordinacion,
            'estado_contacto' => $original->estado_contacto,
            'estado_envio' => $original->costo_envio === null ? EstadoEnvio::PendienteCoordinacion : EstadoEnvio::Cotizado,
            'estado_pago' => EstadoPago::Deshabilitado,
            'huella_checkout' => null,
        ]);

        $nuevo->numero = $nuevo->generarNumero();
        $nuevo->contactado_en = $original->contactado_en;
        $nuevo->save();

        foreach ($original->items as $item) {
            $nuevo->items()->create(Arr::only($item->attributesToArray(), self::CAMPOS_ITEM));
        }

        return $nuevo;
    }

    private function normalizarNota(?string $nota): ?string
    {
        $limpia = trim((string) $nota);

        return $limpia === '' ? null : $limpia;
    }
}
