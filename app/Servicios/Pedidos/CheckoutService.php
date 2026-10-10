<?php

namespace App\Servicios\Pedidos;

use App\Enums\EstadoContacto;
use App\Enums\EstadoEnvio;
use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Usuario;
use App\Models\VarianteProducto;
use App\Servicios\Carrito\CarritoService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    private const string MENSAJE_CARRITO_CAMBIADO = 'Los precios o la disponibilidad cambiaron. Revisá el resumen antes de confirmar.';

    private const string MENSAJE_EMAIL_NO_VERIFICADO = 'Verificá tu email para confirmar el pedido.';

    private const string MENSAJE_CHECKOUT_EN_CURSO = 'Ya estamos procesando tu pedido. Esperá unos segundos e intentá de nuevo.';

    private const int SEGUNDOS_BLOQUEO_CHECKOUT = 10;

    private const int SEGUNDOS_ESPERA_BLOQUEO_CHECKOUT = 5;

    private const int MINUTOS_REUTILIZACION_PEDIDO = 10;

    public function __construct(private CarritoService $carrito) {}

    /**
     * Resumen del carrito con los datos del cliente que se copiarán al pedido. No exige email verificado.
     *
     * @return array{carrito: array<string, mixed>, cliente: array{nombre: string, apellido: string, dni: string, email: string, telefono: string}, email_verificado: bool, huella: string}
     */
    public function resumen(Usuario $usuario): array
    {
        $carrito = $this->carrito->obtener();

        return [
            'carrito' => $carrito,
            'cliente' => $this->datosDelCliente($usuario),
            'email_verificado' => $usuario->hasVerifiedEmail(),
            'huella' => $this->huellaDe($carrito['items']),
        ];
    }

    /**
     * Convierte el carrito de la sesión en un pedido. La huella debe coincidir con la del resumen para
     * garantizar que el cliente confirma los mismos precios y cantidades que vio.
     *
     * Es idempotente por usuario: si ya existe un pedido no cancelado con la misma huella creado en los
     * últimos minutos (por ejemplo, por un doble envío), se devuelve ese pedido y no se crea otro.
     *
     * @param  array{provincia: string, localidad: string, codigo_postal: string, direccion: string, numero: string, direccion_adicional?: ?string, observaciones?: ?string}  $datosEnvio
     * @return array{pedido: Pedido, creado: bool}
     *
     * @throws ValidationException
     */
    public function confirmar(Usuario $usuario, array $datosEnvio, string $huella): array
    {
        try {
            $resultado = Cache::lock('checkout:'.$usuario->getKey(), self::SEGUNDOS_BLOQUEO_CHECKOUT)
                ->block(self::SEGUNDOS_ESPERA_BLOQUEO_CHECKOUT, fn (): array => DB::transaction(
                    fn (): array => $this->confirmarBloqueado($usuario, $datosEnvio, $huella),
                ));
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['carrito' => self::MENSAJE_CHECKOUT_EN_CURSO]);
        }

        $this->carrito->vaciar();

        return $resultado;
    }

    /** @return array{pedido: Pedido, creado: bool} */
    private function confirmarBloqueado(Usuario $usuario, array $datosEnvio, string $huella): array
    {
        $usuario = Usuario::query()->lockForUpdate()->findOrFail($usuario->getKey());
        $this->exigirPuedeConfirmar($usuario);

        $variantes = $this->bloquearVariantes($this->carrito->idsDeVariantes());

        $existente = $this->pedidoReciente($usuario, $huella);

        if ($existente !== null) {
            return ['pedido' => $existente->load('items'), 'creado' => false];
        }

        $carrito = $this->carrito->validarParaCheckout();

        if (! hash_equals($huella, $this->huellaDe($carrito['items']))) {
            throw ValidationException::withMessages(['carrito' => self::MENSAJE_CARRITO_CAMBIADO]);
        }

        $pedido = Pedido::query()->create([
            'usuario_id' => $usuario->getKey(),
            'nombre_cliente' => $usuario->nombre,
            'apellido_cliente' => $usuario->apellido,
            'dni_cliente' => $usuario->dni,
            'email_cliente' => $usuario->email,
            'telefono_cliente' => $usuario->telefono,
            'provincia_envio' => $datosEnvio['provincia'],
            'localidad_envio' => $datosEnvio['localidad'],
            'codigo_postal_envio' => $datosEnvio['codigo_postal'],
            'direccion_envio' => $datosEnvio['direccion'],
            'numero_envio' => $datosEnvio['numero'],
            'direccion_adicional' => $datosEnvio['direccion_adicional'] ?? null,
            'observaciones_envio' => $datosEnvio['observaciones'] ?? null,
            'subtotal_productos' => $carrito['subtotal'],
            'costo_envio' => null,
            'total_base' => null,
            'estado_pedido' => EstadoPedido::PendienteCoordinacion,
            'estado_contacto' => EstadoContacto::Pendiente,
            'estado_envio' => EstadoEnvio::PendienteCoordinacion,
            'estado_pago' => EstadoPago::Deshabilitado,
            'huella_checkout' => $huella,
        ]);

        $pedido->numero = sprintf('ER-%s-%06d', $pedido->created_at->year, $pedido->getKey());
        $pedido->save();

        foreach ($carrito['items'] as $item) {
            $pedido->items()->create([
                'producto_id' => $item['producto_id'],
                'variante_producto_id' => $item['variante_producto_id'],
                'nombre_producto' => $item['nombre_producto'],
                'color' => $item['color'],
                'etiqueta' => $item['etiqueta'],
                'sku' => $variantes->get($item['variante_producto_id'])->sku,
                'precio_unitario' => $item['precio_unitario'],
                'cantidad' => $item['cantidad'],
                'subtotal' => $item['subtotal'],
            ]);
        }

        return ['pedido' => $pedido->load('items'), 'creado' => true];
    }

    /**
     * Busca un pedido del mismo usuario con la misma huella, no cancelado y creado en la ventana de reutilización.
     * Se ejecuta después de bloquear usuario y variantes, así la lectura no fija una instantánea anterior al bloqueo.
     */
    private function pedidoReciente(Usuario $usuario, string $huella): ?Pedido
    {
        return Pedido::query()
            ->where('usuario_id', $usuario->getKey())
            ->where('huella_checkout', $huella)
            ->where('estado_pedido', '!=', EstadoPedido::Cancelado->value)
            ->where('created_at', '>=', now()->subMinutes(self::MINUTOS_REUTILIZACION_PEDIDO))
            ->latest('id')
            ->first();
    }

    private function exigirPuedeConfirmar(Usuario $usuario): void
    {
        if (! $usuario->activo) {
            throw ValidationException::withMessages(['usuario' => 'La cuenta está desactivada.']);
        }

        if (! $usuario->hasVerifiedEmail()) {
            throw ValidationException::withMessages(['email' => self::MENSAJE_EMAIL_NO_VERIFICADO]);
        }
    }

    /**
     * Bloquea primero las variantes y después sus productos, siempre en orden de id para evitar deadlocks.
     * Debe ejecutarse antes de cualquier lectura no bloqueante de la transacción: con REPEATABLE READ,
     * una lectura previa fijaría una instantánea anterior al bloqueo y los precios quedarían desactualizados.
     *
     * @param  list<int>  $ids
     * @return Collection<int, VarianteProducto>
     */
    private function bloquearVariantes(array $ids): Collection
    {
        $variantes = VarianteProducto::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $productosIds = $variantes->pluck('producto_id')->unique()->sort()->values()->all();

        Producto::query()
            ->whereIn('id', $productosIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id']);

        return $variantes;
    }

    /**
     * Huella de las líneas del carrito (variante, cantidad y precio unitario). Las líneas se ordenan por
     * variante para que el orden en que se agregaron los productos no altere la huella.
     *
     * @param  list<array<string, mixed>>  $items
     */
    private function huellaDe(array $items): string
    {
        $lineas = collect($items)
            ->map(fn (array $item): array => [
                (int) $item['variante_producto_id'],
                (int) $item['cantidad'],
                $item['precio_unitario'],
            ])
            ->sortBy(fn (array $linea): int => $linea[0])
            ->values()
            ->all();

        return hash('sha256', json_encode($lineas, JSON_THROW_ON_ERROR));
    }

    /** @return array{nombre: string, apellido: string, dni: string, email: string, telefono: string} */
    private function datosDelCliente(Usuario $usuario): array
    {
        return [
            'nombre' => $usuario->nombre,
            'apellido' => $usuario->apellido,
            'dni' => $usuario->dni,
            'email' => $usuario->email,
            'telefono' => $usuario->telefono,
        ];
    }
}
