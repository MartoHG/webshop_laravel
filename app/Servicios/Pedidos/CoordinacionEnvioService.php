<?php

namespace App\Servicios\Pedidos;

use App\Enums\EstadoContacto;
use App\Enums\EstadoEnvio;
use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Models\Pedido;
use App\Models\ReglaPrecioPago;
use App\Models\VarianteProducto;
use App\Servicios\Auditoria\AuditoriaService;
use App\Servicios\Precios\AjusteEcommerceService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CoordinacionEnvioService
{
    /** @var list<EstadoEnvio> */
    private const array SECUENCIA_AVANCE_ENVIO = [
        EstadoEnvio::Aceptado,
        EstadoEnvio::Preparado,
        EstadoEnvio::Despachado,
        EstadoEnvio::Entregado,
    ];

    public function __construct(
        private AuditoriaService $auditoria,
        private AjusteEcommerceService $ajuste,
    ) {}

    /**
     * La fecha de contacto se guarda la primera vez que el cliente queda contactado o responde.
     *
     * @throws ValidationException
     */
    public function registrarContacto(Pedido $pedido, EstadoContacto $estado): Pedido
    {
        return DB::transaction(function () use ($pedido, $estado): Pedido {
            $pedido = $this->bloquear($pedido);
            $this->exigirNoCancelado($pedido);

            $anteriores = $this->datosDeContacto($pedido);

            $pedido->estado_contacto = $estado;

            if (in_array($estado, [EstadoContacto::Contactado, EstadoContacto::Respondio], true) && $pedido->contactado_en === null) {
                $pedido->contactado_en = now();
            }

            $pedido->save();

            $this->auditoria->registrarCambios('pedido.contacto_actualizado', $pedido, $anteriores, $this->datosDeContacto($pedido));

            return $this->finalizar($pedido);
        });
    }

    /**
     * @throws ValidationException
     */
    public function cotizarEnvio(Pedido $pedido, string $expreso, string $costoEnvio): Pedido
    {
        return DB::transaction(function () use ($pedido, $expreso, $costoEnvio): Pedido {
            $pedido = $this->bloquear($pedido);
            $this->exigirPendienteCoordinacion($pedido);
            $this->exigirPagoDeshabilitado($pedido);

            $costo = $this->normalizarCosto($costoEnvio);
            $anteriores = $this->datosDeEnvio($pedido);

            $pedido->expreso = trim($expreso);
            $pedido->costo_envio = $costo;
            $pedido->total_base = bcadd((string) $pedido->subtotal_productos, $costo, 2);
            $pedido->estado_envio = EstadoEnvio::Cotizado;
            $pedido->save();

            $this->auditoria->registrarCambios('pedido.envio_cotizado', $pedido, $anteriores, $this->datosDeEnvio($pedido));

            return $this->finalizar($pedido);
        });
    }

    /**
     * @throws ValidationException
     */
    public function registrarAceptacion(Pedido $pedido): Pedido
    {
        return DB::transaction(function () use ($pedido): Pedido {
            $pedido = $this->bloquear($pedido);
            $this->exigirPendienteCoordinacion($pedido);
            $this->exigirPagoDeshabilitado($pedido);

            if ($pedido->estado_envio !== EstadoEnvio::Cotizado) {
                throw ValidationException::withMessages(['pedido' => 'Solo se puede registrar la aceptación de un envío cotizado.']);
            }

            $anteriores = $this->datosDeEnvio($pedido);

            $pedido->estado_envio = EstadoEnvio::Aceptado;
            $pedido->save();

            $this->auditoria->registrarCambios('pedido.envio_aceptado', $pedido, $anteriores, $this->datosDeEnvio($pedido));

            return $this->finalizar($pedido);
        });
    }

    /**
     * Habilitar el pago genera la orden de pago: crea el token del link y bloquea la edición de envío e importes.
     *
     * @throws ValidationException
     */
    public function habilitarPago(Pedido $pedido): Pedido
    {
        return DB::transaction(function () use ($pedido): Pedido {
            $pedido = $this->bloquear($pedido);
            $this->exigirPendienteCoordinacion($pedido);

            if ($pedido->estado_envio !== EstadoEnvio::Aceptado) {
                throw ValidationException::withMessages(['pedido' => 'El cliente tiene que aceptar el envío antes de habilitar el pago.']);
            }

            $this->exigirPagoDeshabilitado($pedido);

            if ($pedido->costo_envio === null) {
                throw ValidationException::withMessages(['pedido' => 'Falta cotizar el envío.']);
            }

            if ($pedido->total_base === null || bccomp((string) $pedido->total_base, '0', 2) <= 0) {
                throw ValidationException::withMessages(['pedido' => 'El total del pedido tiene que ser mayor a cero.']);
            }

            if ($pedido->items()->doesntExist()) {
                throw ValidationException::withMessages(['pedido' => 'El pedido no tiene productos.']);
            }

            if (! ReglaPrecioPago::query()->where('activo', true)->exists()) {
                throw ValidationException::withMessages([
                    'pago' => 'No hay modalidades de pago activas. Configurá las reglas de pago antes de habilitar el pago.',
                ]);
            }

            $anteriores = [
                'estado_pago' => $pedido->estado_pago->value,
                'pago_habilitado_en' => $this->fecha($pedido->pago_habilitado_en),
            ];

            $pedido->token_pago = $this->generarTokenPago();
            $pedido->estado_pago = EstadoPago::Habilitado;
            $pedido->pago_habilitado_en = now();
            $pedido->save();

            $this->auditoria->registrarCambios('pedido.pago_habilitado', $pedido, $anteriores, [
                'estado_pago' => $pedido->estado_pago->value,
                'pago_habilitado_en' => $this->fecha($pedido->pago_habilitado_en),
            ]);

            return $this->finalizar($pedido);
        });
    }

    /**
     * Reprecia cada item con el precio publicado actual de su variante. Los totales se recalculan y se auditan.
     *
     * @throws ValidationException
     */
    public function recalcularImportes(Pedido $pedido): Pedido
    {
        return DB::transaction(function () use ($pedido): Pedido {
            $pedido = $this->bloquear($pedido);
            $this->exigirPendienteCoordinacion($pedido);
            $this->exigirPagoDeshabilitado($pedido);

            $porcentaje = $this->ajuste->obtenerPorcentaje();

            if ($porcentaje === null) {
                throw ValidationException::withMessages(['pedido' => 'Falta configurar el ajuste e-commerce para recalcular los importes.']);
            }

            $pedido->load('items');
            $variantes = VarianteProducto::query()
                ->whereIn('id', $pedido->items->pluck('variante_producto_id'))
                ->get()
                ->keyBy('id');

            $anteriores = $this->importesDe($pedido);
            $subtotalProductos = '0.00';

            foreach ($pedido->items as $item) {
                $variante = $variantes->get($item->variante_producto_id);

                if ($variante === null) {
                    throw ValidationException::withMessages(['pedido' => 'Una variante del pedido ya no existe.']);
                }

                $precioUnitario = (string) $this->ajuste->calcularPrecioPublicado((string) $variante->precio_base, $porcentaje);
                $subtotal = bcmul($precioUnitario, (string) $item->cantidad, 2);

                $item->precio_unitario = $precioUnitario;
                $item->subtotal = $subtotal;
                $item->save();

                $subtotalProductos = bcadd($subtotalProductos, $subtotal, 2);
            }

            $pedido->subtotal_productos = $subtotalProductos;
            $pedido->total_base = $pedido->costo_envio === null
                ? null
                : bcadd($subtotalProductos, (string) $pedido->costo_envio, 2);
            $pedido->save();

            $this->auditoria->registrarCambios('pedido.importes_recalculados', $pedido, $anteriores, $this->importesDe($pedido));

            return $this->finalizar($pedido);
        });
    }

    /**
     * Avanza el envío de a un paso (aceptado, preparado, despachado, entregado) y solo después del pago.
     *
     * @throws ValidationException
     */
    public function avanzarEnvio(Pedido $pedido, EstadoEnvio $destino): Pedido
    {
        return DB::transaction(function () use ($pedido, $destino): Pedido {
            $pedido = $this->bloquear($pedido);
            $this->exigirNoCancelado($pedido);

            if (! in_array($destino, [EstadoEnvio::Preparado, EstadoEnvio::Despachado, EstadoEnvio::Entregado], true)) {
                throw ValidationException::withMessages(['estado_envio' => 'El envío solo puede pasar a preparado, despachado o entregado.']);
            }

            if ($pedido->estado_pago !== EstadoPago::Pagado) {
                throw ValidationException::withMessages(['pago' => 'El envío solo puede avanzar después del pago confirmado.']);
            }

            $this->exigirSiguientePaso($pedido->estado_envio, $destino);

            $campoFecha = match ($destino) {
                EstadoEnvio::Preparado => 'preparado_en',
                EstadoEnvio::Despachado => 'despachado_en',
                EstadoEnvio::Entregado => 'entregado_en',
            };

            $anteriores = [
                'estado_envio' => $pedido->estado_envio->value,
                $campoFecha => $this->fecha($pedido->{$campoFecha}),
            ];

            $pedido->estado_envio = $destino;
            $pedido->{$campoFecha} = now();
            $pedido->save();

            $this->auditoria->registrarCambios('pedido.envio_avanzado', $pedido, $anteriores, [
                'estado_envio' => $destino->value,
                $campoFecha => $this->fecha($pedido->{$campoFecha}),
            ]);

            return $this->finalizar($pedido);
        });
    }

    /**
     * Corrige la dirección y observaciones de envío antes o después del pago. Solo audita los campos que cambiaron.
     *
     * @param  array{provincia: string, localidad: string, codigo_postal: string, direccion: string, numero: string, direccion_adicional?: ?string, observaciones?: ?string}  $datos
     *
     * @throws ValidationException
     */
    public function actualizarDatosEnvio(Pedido $pedido, array $datos): Pedido
    {
        return DB::transaction(function () use ($pedido, $datos): Pedido {
            $pedido = $this->bloquear($pedido);
            $this->exigirNoCancelado($pedido);

            $nuevos = [
                'provincia_envio' => $datos['provincia'],
                'localidad_envio' => $datos['localidad'],
                'codigo_postal_envio' => $datos['codigo_postal'],
                'direccion_envio' => $datos['direccion'],
                'numero_envio' => $datos['numero'],
                'direccion_adicional' => $datos['direccion_adicional'] ?? null,
                'observaciones_envio' => $datos['observaciones'] ?? null,
            ];

            $anteriores = [
                'provincia_envio' => $pedido->provincia_envio->value,
                'localidad_envio' => $pedido->localidad_envio,
                'codigo_postal_envio' => $pedido->codigo_postal_envio,
                'direccion_envio' => $pedido->direccion_envio,
                'numero_envio' => $pedido->numero_envio,
                'direccion_adicional' => $pedido->direccion_adicional,
                'observaciones_envio' => $pedido->observaciones_envio,
            ];

            $pedido->fill($nuevos);
            $pedido->save();

            $this->auditoria->registrarCambios('pedido.datos_envio_actualizados', $pedido, $anteriores, $nuevos);

            return $this->finalizar($pedido);
        });
    }

    private function bloquear(Pedido $pedido): Pedido
    {
        return Pedido::query()->lockForUpdate()->findOrFail($pedido->getKey());
    }

    private function finalizar(Pedido $pedido): Pedido
    {
        return $pedido->refresh()->load(['items', 'canceladoPor']);
    }

    /**
     * @throws ValidationException
     */
    private function exigirNoCancelado(Pedido $pedido): void
    {
        if ($pedido->estado_pedido === EstadoPedido::Cancelado) {
            throw ValidationException::withMessages(['pedido' => 'El pedido está cancelado y no admite cambios.']);
        }
    }

    /**
     * @throws ValidationException
     */
    private function exigirPendienteCoordinacion(Pedido $pedido): void
    {
        $this->exigirNoCancelado($pedido);

        if ($pedido->estado_pedido !== EstadoPedido::PendienteCoordinacion) {
            throw ValidationException::withMessages(['pedido' => 'El pedido ya está confirmado y no admite cambios de envío ni de importes.']);
        }
    }

    /**
     * @throws ValidationException
     */
    private function exigirPagoDeshabilitado(Pedido $pedido): void
    {
        if ($pedido->estado_pago !== EstadoPago::Deshabilitado) {
            throw ValidationException::withMessages([
                'pago' => 'El pago ya está habilitado. El envío y los importes no se pueden modificar.',
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    private function exigirSiguientePaso(EstadoEnvio $actual, EstadoEnvio $destino): void
    {
        $posicionActual = array_search($actual, self::SECUENCIA_AVANCE_ENVIO, true);
        $posicionDestino = array_search($destino, self::SECUENCIA_AVANCE_ENVIO, true);

        if ($posicionActual === false) {
            throw ValidationException::withMessages(['estado_envio' => 'El cliente todavía no aceptó el envío.']);
        }

        if ($posicionDestino <= $posicionActual) {
            throw ValidationException::withMessages([
                'estado_envio' => "El envío ya está en {$actual->value}. No se puede repetir ni retroceder el estado.",
            ]);
        }

        if ($posicionDestino > $posicionActual + 1) {
            $siguiente = self::SECUENCIA_AVANCE_ENVIO[$posicionActual + 1];

            throw ValidationException::withMessages([
                'estado_envio' => "El envío avanza de a un paso. El siguiente estado es {$siguiente->value}.",
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    private function normalizarCosto(string $costo): string
    {
        if (preg_match('/^\d{1,13}(\.\d{1,2})?$/', $costo) !== 1) {
            throw ValidationException::withMessages([
                'costo_envio' => 'El costo del envío debe ser un importe mayor o igual a cero, con hasta dos decimales.',
            ]);
        }

        return bcadd($costo, '0', 2);
    }

    private function generarTokenPago(): string
    {
        do {
            $token = bin2hex(random_bytes(24));
        } while (Pedido::query()->where('token_pago', $token)->exists());

        return $token;
    }

    /** @return array{estado_contacto: string, contactado_en: ?string} */
    private function datosDeContacto(Pedido $pedido): array
    {
        return [
            'estado_contacto' => $pedido->estado_contacto->value,
            'contactado_en' => $this->fecha($pedido->contactado_en),
        ];
    }

    /** @return array{expreso: ?string, costo_envio: ?string, total_base: ?string, estado_envio: string} */
    private function datosDeEnvio(Pedido $pedido): array
    {
        return [
            'expreso' => $pedido->expreso,
            'costo_envio' => $pedido->costo_envio,
            'total_base' => $pedido->total_base,
            'estado_envio' => $pedido->estado_envio->value,
        ];
    }

    /** @return array{subtotal_productos: string, total_base: ?string} */
    private function importesDe(Pedido $pedido): array
    {
        return [
            'subtotal_productos' => (string) $pedido->subtotal_productos,
            'total_base' => $pedido->total_base,
        ];
    }

    private function fecha(?CarbonInterface $fecha): ?string
    {
        return $fecha?->toIso8601String();
    }
}
