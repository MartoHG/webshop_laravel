<?php

namespace App\Servicios\Pedidos;

use App\Enums\EstadoEnvio;
use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Models\ItemPedido;
use App\Models\Pedido;
use App\Models\VarianteProducto;
use App\Servicios\Auditoria\AuditoriaService;
use App\Servicios\Precios\AjusteEcommerceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PedidoEdicionService
{
    private const int CANTIDAD_MINIMA = 1;

    private const int CANTIDAD_MAXIMA = 99;

    public function __construct(
        private AuditoriaService $auditoria,
        private AjusteEcommerceService $ajuste,
    ) {}

    /**
     * Agrega una variante. Si el pedido ya la tiene, suma la cantidad a esa línea y conserva su precio snapshot.
     *
     * @throws ValidationException
     */
    public function agregarItem(Pedido $pedido, int $varianteId, int $cantidad): Pedido
    {
        return DB::transaction(function () use ($pedido, $varianteId, $cantidad): Pedido {
            $pedido = $this->bloquear($pedido);
            $this->exigirEditable($pedido);
            $this->exigirCantidad($cantidad);

            $variante = VarianteProducto::query()->with('producto')->find($varianteId);
            $this->exigirDisponible($variante);

            $totalesAnteriores = $this->totalesDe($pedido);
            $existente = $pedido->items()->where('variante_producto_id', $variante->getKey())->lockForUpdate()->first();

            if ($existente !== null) {
                $lineaAnterior = $this->lineaDe($existente);
                $this->sumarCantidad($existente, $cantidad);
                $lineaNueva = $this->lineaDe($existente);
            } else {
                $lineaAnterior = null;
                $item = $this->crearLinea($pedido, $variante, $cantidad);
                $lineaNueva = $this->lineaDe($item);
            }

            $this->recalcularTotales($pedido);

            $this->auditoria->registrar('pedido.item_agregado', $pedido, [
                ...$totalesAnteriores,
                'linea' => $lineaAnterior,
            ], [
                ...$this->totalesDe($pedido),
                'linea' => $lineaNueva,
            ]);

            return $this->finalizar($pedido);
        });
    }

    /**
     * Cambia la cantidad de una línea y mantiene su precio snapshot.
     *
     * @throws ValidationException
     */
    public function cambiarCantidad(Pedido $pedido, ItemPedido $item, int $cantidad): Pedido
    {
        return DB::transaction(function () use ($pedido, $item, $cantidad): Pedido {
            $pedido = $this->bloquear($pedido);
            $item = $this->bloquearItem($pedido, $item);
            $this->exigirEditable($pedido);
            $this->exigirCantidad($cantidad);

            if ($item->cantidad === $cantidad) {
                return $this->finalizar($pedido);
            }

            $totalesAnteriores = $this->totalesDe($pedido);
            $lineaAnterior = $this->lineaDe($item);

            $this->fijarCantidad($item, $cantidad);
            $this->recalcularTotales($pedido);

            $this->auditoria->registrar('pedido.item_cantidad_cambiada', $pedido, [
                ...$totalesAnteriores,
                'linea' => $lineaAnterior,
            ], [
                ...$this->totalesDe($pedido),
                'linea' => $this->lineaDe($item),
            ]);

            return $this->finalizar($pedido);
        });
    }

    /**
     * Cambia la línea a otra variante del mismo producto, con precio y datos de la nueva variante.
     * Si el pedido ya tiene esa variante en otra línea, las cantidades se fusionan en esa línea.
     *
     * @throws ValidationException
     */
    public function cambiarVariante(Pedido $pedido, ItemPedido $item, int $nuevaVarianteId): Pedido
    {
        return DB::transaction(function () use ($pedido, $item, $nuevaVarianteId): Pedido {
            $pedido = $this->bloquear($pedido);
            $item = $this->bloquearItem($pedido, $item);
            $this->exigirEditable($pedido);

            $variante = VarianteProducto::query()->with('producto')->find($nuevaVarianteId);
            $this->exigirDisponible($variante);
            $this->exigirMismoProducto($item, $variante);

            if ((int) $variante->getKey() === (int) $item->variante_producto_id) {
                throw ValidationException::withMessages(['variante_producto_id' => 'El producto ya tiene esa variante.']);
            }

            $totalesAnteriores = $this->totalesDe($pedido);
            $lineaAnterior = $this->lineaDe($item);
            $otraLinea = $pedido->items()
                ->where('variante_producto_id', $variante->getKey())
                ->whereKeyNot($item->getKey())
                ->lockForUpdate()
                ->first();

            if ($otraLinea !== null) {
                $lineaDestinoAnterior = $this->lineaDe($otraLinea);

                $this->sumarCantidad($otraLinea, $item->cantidad);
                $item->delete();
                $this->recalcularTotales($pedido);

                $this->auditoria->registrar('pedido.item_variante_cambiada', $pedido, [
                    ...$totalesAnteriores,
                    'linea' => $lineaAnterior,
                    'linea_destino' => $lineaDestinoAnterior,
                ], [
                    ...$this->totalesDe($pedido),
                    'linea' => null,
                    'linea_destino' => $this->lineaDe($otraLinea),
                ]);

                return $this->finalizar($pedido);
            }

            $this->aplicarVariante($item, $variante);
            $this->recalcularTotales($pedido);

            $this->auditoria->registrar('pedido.item_variante_cambiada', $pedido, [
                ...$totalesAnteriores,
                'linea' => $lineaAnterior,
            ], [
                ...$this->totalesDe($pedido),
                'linea' => $this->lineaDe($item),
            ]);

            return $this->finalizar($pedido);
        });
    }

    /**
     * El último producto no se puede quitar: para eso se cancela el pedido.
     *
     * @throws ValidationException
     */
    public function quitarItem(Pedido $pedido, ItemPedido $item): Pedido
    {
        return DB::transaction(function () use ($pedido, $item): Pedido {
            $pedido = $this->bloquear($pedido);
            $item = $this->bloquearItem($pedido, $item);
            $this->exigirEditable($pedido);

            if ($pedido->items()->count() <= 1) {
                throw ValidationException::withMessages([
                    'pedido' => 'El pedido tiene que conservar al menos un producto. Si ya no lo querés, cancelá el pedido.',
                ]);
            }

            $totalesAnteriores = $this->totalesDe($pedido);
            $lineaAnterior = $this->lineaDe($item);

            $item->delete();
            $this->recalcularTotales($pedido);

            $this->auditoria->registrar('pedido.item_quitado', $pedido, [
                ...$totalesAnteriores,
                'linea' => $lineaAnterior,
            ], [
                ...$this->totalesDe($pedido),
                'linea' => null,
            ]);

            return $this->finalizar($pedido);
        });
    }

    private function bloquear(Pedido $pedido): Pedido
    {
        return Pedido::query()->lockForUpdate()->findOrFail($pedido->getKey());
    }

    /**
     * Un item de otro pedido responde 404: la URL no debe permitir tocar líneas ajenas.
     */
    private function bloquearItem(Pedido $pedido, ItemPedido $item): ItemPedido
    {
        $bloqueado = ItemPedido::query()->lockForUpdate()->find($item->getKey());

        if ($bloqueado === null || (int) $bloqueado->pedido_id !== (int) $pedido->getKey()) {
            throw new NotFoundHttpException('El producto no pertenece a este pedido.');
        }

        return $bloqueado;
    }

    /**
     * @throws ValidationException
     */
    private function exigirEditable(Pedido $pedido): void
    {
        if ($pedido->estado_pedido === EstadoPedido::Cancelado || $pedido->estado_pago === EstadoPago::Cancelado) {
            throw ValidationException::withMessages(['pedido' => 'El pedido está cancelado y no admite cambios.']);
        }

        if ($pedido->estado_pedido !== EstadoPedido::PendienteCoordinacion || $pedido->estado_pago === EstadoPago::Pagado) {
            throw ValidationException::withMessages(['pedido' => 'El pedido ya está pagado y no admite cambios.']);
        }

        if ($pedido->estado_pago !== EstadoPago::Deshabilitado) {
            throw ValidationException::withMessages([
                'pedido' => 'El pedido ya tiene una orden de pago. Para cambiarlo, reemplazalo por un pedido nuevo.',
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    private function exigirCantidad(int $cantidad): void
    {
        if ($cantidad < self::CANTIDAD_MINIMA || $cantidad > self::CANTIDAD_MAXIMA) {
            throw ValidationException::withMessages(['cantidad' => 'La cantidad debe estar entre 1 y 99.']);
        }
    }

    /**
     * Misma regla que el carrito: la variante y su producto deben estar activos y con stock.
     *
     * @throws ValidationException
     */
    private function exigirDisponible(?VarianteProducto $variante): void
    {
        $motivo = $variante === null ? 'no_disponible' : $variante->motivoNoDisponible();

        if ($motivo === null) {
            return;
        }

        throw ValidationException::withMessages([
            'variante_producto_id' => $motivo === 'agotado'
                ? 'La variante está agotada y no se puede usar en el pedido.'
                : 'La variante no está disponible para la compra.',
        ]);
    }

    /**
     * @throws ValidationException
     */
    private function exigirMismoProducto(ItemPedido $item, VarianteProducto $variante): void
    {
        if ((int) $variante->producto_id !== (int) $item->producto_id) {
            throw ValidationException::withMessages([
                'variante_producto_id' => 'La nueva variante tiene que ser de este mismo producto.',
            ]);
        }
    }

    private function crearLinea(Pedido $pedido, VarianteProducto $variante, int $cantidad): ItemPedido
    {
        $precioUnitario = $this->precioPublicado($variante);

        return $pedido->items()->create([
            'producto_id' => $variante->producto_id,
            'variante_producto_id' => $variante->getKey(),
            'nombre_producto' => $variante->producto->nombre,
            'color' => $variante->color,
            'etiqueta' => $variante->etiqueta,
            'sku' => $variante->sku,
            'precio_unitario' => $precioUnitario,
            'cantidad' => $cantidad,
            'subtotal' => bcmul($precioUnitario, (string) $cantidad, 2),
        ]);
    }

    /**
     * Toma color, etiqueta, sku y precio actual de la nueva variante. El nombre no cambia: es el mismo producto.
     */
    private function aplicarVariante(ItemPedido $item, VarianteProducto $variante): void
    {
        $precioUnitario = $this->precioPublicado($variante);

        $item->variante_producto_id = $variante->getKey();
        $item->color = $variante->color;
        $item->etiqueta = $variante->etiqueta;
        $item->sku = $variante->sku;
        $item->precio_unitario = $precioUnitario;
        $item->subtotal = bcmul($precioUnitario, (string) $item->cantidad, 2);
        $item->save();
    }

    /**
     * @throws ValidationException
     */
    private function precioPublicado(VarianteProducto $variante): string
    {
        $precio = $this->ajuste->calcularPrecioPublicado((string) $variante->precio_base);

        if ($precio === null) {
            throw ValidationException::withMessages(['pedido' => 'Falta configurar el ajuste e-commerce para editar el pedido.']);
        }

        return $precio;
    }

    /**
     * @throws ValidationException
     */
    private function sumarCantidad(ItemPedido $item, int $cantidad): void
    {
        $total = $item->cantidad + $cantidad;

        if ($total > self::CANTIDAD_MAXIMA) {
            throw ValidationException::withMessages([
                'cantidad' => 'Una misma variante admite hasta 99 unidades en el pedido.',
            ]);
        }

        $this->fijarCantidad($item, $total);
    }

    private function fijarCantidad(ItemPedido $item, int $cantidad): void
    {
        $item->cantidad = $cantidad;
        $item->subtotal = bcmul((string) $item->precio_unitario, (string) $cantidad, 2);
        $item->save();
    }

    /**
     * El subtotal se suma desde las líneas. Si hay costo de envío acordado, el total base lo incluye.
     * Un envío aceptado vuelve a cotizado: el cliente tiene que aceptar el nuevo total.
     */
    private function recalcularTotales(Pedido $pedido): void
    {
        $subtotal = $pedido->items()->get()->reduce(
            fn (string $acumulado, ItemPedido $linea): string => bcadd($acumulado, (string) $linea->subtotal, 2),
            '0.00',
        );

        $pedido->subtotal_productos = $subtotal;
        $pedido->total_base = $pedido->costo_envio === null ? null : bcadd($subtotal, (string) $pedido->costo_envio, 2);

        if ($pedido->estado_envio === EstadoEnvio::Aceptado) {
            $pedido->estado_envio = EstadoEnvio::Cotizado;
        }

        $pedido->save();
    }

    private function finalizar(Pedido $pedido): Pedido
    {
        return $pedido->refresh()->load(['items', 'canceladoPor']);
    }

    /** @return array{subtotal_productos: string, total_base: ?string, estado_envio: string} */
    private function totalesDe(Pedido $pedido): array
    {
        return [
            'subtotal_productos' => (string) $pedido->subtotal_productos,
            'total_base' => $pedido->total_base,
            'estado_envio' => $pedido->estado_envio->value,
        ];
    }

    /** @return array{item_pedido_id: int, variante_producto_id: int, nombre_producto: string, color: ?string, etiqueta: ?string, sku: string, precio_unitario: string, cantidad: int, subtotal: string} */
    private function lineaDe(ItemPedido $item): array
    {
        return [
            'item_pedido_id' => (int) $item->getKey(),
            'variante_producto_id' => (int) $item->variante_producto_id,
            'nombre_producto' => $item->nombre_producto,
            'color' => $item->color,
            'etiqueta' => $item->etiqueta,
            'sku' => $item->sku,
            'precio_unitario' => $item->precio_unitario,
            'cantidad' => $item->cantidad,
            'subtotal' => $item->subtotal,
        ];
    }
}
