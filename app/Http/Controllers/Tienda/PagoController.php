<?php

namespace App\Http\Controllers\Tienda;

use App\Enums\EstadoIntentoPago;
use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Enums\TipoMedioPago;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tienda\IniciarIntentoPagoRequest;
use App\Http\Requests\Tienda\ProcesarPagoTarjetaRequest;
use App\Models\Pago;
use App\Models\Pedido;
use App\Servicios\Contenido\ContactoService;
use App\Servicios\Pagos\PagoService;
use App\Servicios\Precios\FormatoMoneda;
use App\View\EstadoVisiblePago;
use App\View\OpcionPagoVisible;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Cada ruta sirve JSON cuando la solicitud lo pide. Si no, sirve la página de pago o, con htmx, solo el fragmento.
 */
class PagoController extends Controller
{
    private const string MENSAJE_LINK_INACTIVO = 'Este link de pago ya no está activo. Recargá la página para ver cómo está tu pedido.';

    public function mostrar(Request $request, string $token, PagoService $servicio, ContactoService $contacto): JsonResponse|View|Response
    {
        if ($request->expectsJson()) {
            return response()->json($servicio->resumen($servicio->pedidoPorToken($token)));
        }

        $idPagoMercadoPago = $this->idPagoDelRegreso($request);
        $buscarPedido = $idPagoMercadoPago === null
            ? fn (): Pedido => $servicio->pedidoPorToken($token)
            : fn (): Pedido => $servicio->pedidoDelToken($token);
        $pedido = $this->pedidoDelLink($request, $buscarPedido, $contacto);

        if (! $pedido instanceof Pedido) {
            return $pedido;
        }

        $pagoDelRegreso = null;

        if ($idPagoMercadoPago !== null) {
            $pagoDelRegreso = $servicio->aplicarRegresoDeMercadoPago($pedido, $idPagoMercadoPago, $this->estadoDelRegreso($request));
            $pedido->refresh();

            $pagado = $pedido->estado_pago === EstadoPago::Pagado && $pedido->estado_pedido !== EstadoPedido::Cancelado;

            if (! $pagado && ! $servicio->linkActivo($pedido)) {
                return $this->respuestaLinkInactivo($request, $contacto);
            }
        }

        return view('tienda.pago', $this->datosDeLaPagina(
            $token,
            $pedido,
            $servicio,
            $contacto,
            $pagoDelRegreso?->estado === EstadoIntentoPago::Rechazado,
        ));
    }

    public function iniciarIntento(string $token, IniciarIntentoPagoRequest $request, PagoService $servicio, ContactoService $contacto): JsonResponse|RedirectResponse|View|Response
    {
        $pedido = $this->pedidoDelLink($request, fn (): Pedido => $servicio->pedidoPorToken($token), $contacto);

        if (! $pedido instanceof Pedido) {
            return $pedido;
        }

        try {
            $intento = $servicio->iniciarIntento($pedido, (int) $request->validated('regla_precio_pago_id'));
        } catch (ValidationException $excepcion) {
            if ($request->expectsJson()) {
                throw $excepcion;
            }

            $mensaje = $this->primerMensaje($excepcion);

            if ($this->esHtmx($request)) {
                return response()->view('tienda.partials.pago-error', ['mensaje' => $mensaje], 422);
            }

            return redirect()->route('pago.mostrar', $token)->with('error', $mensaje);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'mensaje' => 'Elegiste la modalidad de pago. Completá los datos para pagar.',
                'intento' => $intento,
            ], 201);
        }

        if ($this->esHtmx($request)) {
            return view('tienda.partials.pago-brick', ['brick' => $this->datosDelBrick($token, $intento)]);
        }

        return redirect()->route('pago.mostrar', $token);
    }

    public function pagarConTarjeta(string $token, Pago $pago, ProcesarPagoTarjetaRequest $request, PagoService $servicio): JsonResponse
    {
        return response()->json(
            $servicio->procesarConTarjeta($servicio->pedidoPorToken($token), $pago, $request->validated()),
        );
    }

    /**
     * Sin htmx devuelve el estado en JSON. Con htmx devuelve el bloque de resultado. Mientras el pago está pendiente,
     * ese bloque se vuelve a pedir cada 5 segundos; si sigue igual, la respuesta es 204 y no reemplaza nada.
     */
    public function estado(Request $request, string $token, PagoService $servicio, ContactoService $contacto): JsonResponse|View|Response
    {
        if (! $this->esHtmx($request)) {
            return response()->json($servicio->estado($servicio->pedidoDelToken($token)));
        }

        $pedido = $this->pedidoDelLink($request, fn (): Pedido => $servicio->pedidoDelToken($token), $contacto);

        if (! $pedido instanceof Pedido) {
            return $pedido;
        }

        $estado = $servicio->estado($pedido);
        $resultado = $this->datosDelResultado($token, $pedido, $estado['estado_pago'], $estado['intento_vigente'], $servicio, $contacto);

        if ($resultado['clave'] === 'pendiente' && $request->hasHeader('X-Sondeo')) {
            return response()->noContent();
        }

        return view('tienda.partials.pago-estado', $resultado);
    }

    private function idPagoDelRegreso(Request $request): ?string
    {
        $id = $request->query('payment_id');

        if ($id === null || $id === '') {
            $id = $request->query('collection_id');
        }

        return is_string($id) && strlen($id) <= 20 && ctype_digit($id) ? $id : null;
    }

    private function estadoDelRegreso(Request $request): ?string
    {
        $estado = $request->query('status');

        if (! is_string($estado) || $estado === '') {
            $estado = $request->query('collection_status');
        }

        return is_string($estado) ? $estado : null;
    }

    /**
     * @return array{
     *     token: string, pedido: Pedido, resumen: array<string, mixed>, admitePago: bool,
     *     opciones: list<array{regla_id: int, tipo_medio_pago: string, cuotas: int, importe: string}>, seleccionada: ?int,
     *     brick: ?array<string, mixed>, resultado: ?array<string, mixed>, whatsappUrl: ?string, mercadoPagoConfigurado: bool
     * }
     */
    private function datosDeLaPagina(string $token, Pedido $pedido, PagoService $servicio, ContactoService $contacto, bool $regresoRechazado): array
    {
        $resumen = $servicio->resumen($pedido);
        $pagoVigente = $servicio->pagoVigente($pedido);
        $preferenciaVencida = $pagoVigente !== null && $servicio->preferenciaVencida($pagoVigente);
        $intentoCreado = $pagoVigente !== null && $pagoVigente->estado === EstadoIntentoPago::Creado && ! $preferenciaVencida;
        $admitePago = $pedido->estado_pago !== EstadoPago::Pagado;
        $muestraResultado = ! $admitePago
            || $pedido->estado_pago === EstadoPago::Pendiente
            || ($regresoRechazado && $pedido->estado_pago === EstadoPago::Habilitado && $pagoVigente === null);

        return [
            'token' => $token,
            'pedido' => $pedido,
            'resumen' => $resumen,
            'admitePago' => $admitePago,
            'opciones' => $this->ordenDeLaPagina($resumen['opciones']),
            'seleccionada' => $admitePago && ! $preferenciaVencida
                ? $this->opcionDelIntento($resumen['opciones'], $resumen['intento_vigente'])
                : null,
            'brick' => $admitePago && $intentoCreado ? $this->datosDelBrick($token, $servicio->configuracionDelIntento($pagoVigente)) : null,
            'resultado' => $muestraResultado
                ? $this->datosDelResultado($token, $pedido, $resumen['estado_pago'], $resumen['intento_vigente'], $servicio, $contacto)
                : null,
            'whatsappUrl' => $contacto->publico()['whatsapp_url'],
            'mercadoPagoConfigurado' => $this->clavePublica() !== null,
        ];
    }

    /**
     * @param  ?array{estado: string}  $intentoVigente
     * @return array<string, mixed>
     */
    private function datosDelResultado(string $token, Pedido $pedido, string $estadoPago, ?array $intentoVigente, PagoService $servicio, ContactoService $contacto): array
    {
        $visible = EstadoVisiblePago::paraPago($estadoPago, $intentoVigente);

        return [
            ...$visible,
            'motivo' => $visible['clave'] === 'rechazado'
                ? EstadoVisiblePago::motivoDeRechazo($servicio->motivoDelUltimoRechazo($pedido))
                : null,
            'urlEstado' => $visible['clave'] === 'pendiente' ? route('pago.estado', $token) : null,
            'urlPagina' => route('pago.mostrar', $token),
            'verPedido' => $this->verPedidoDelCliente($pedido),
            'whatsappUrl' => $contacto->publico()['whatsapp_url'],
        ];
    }

    /**
     * @param  array{pago_id: int, importe: string, tipo_medio_pago: string, cuotas: int, public_key: ?string, preference_id: ?string}  $intento
     * @return array<string, mixed>
     */
    private function datosDelBrick(string $token, array $intento): array
    {
        $tipo = $intento['tipo_medio_pago'];
        $cuotas = $intento['cuotas'];

        return [
            'public_key' => $this->clavePublica(),
            'importe' => $intento['importe'],
            'importe_formateado' => FormatoMoneda::formatear($intento['importe']),
            'tipo' => $tipo,
            'cuotas' => $cuotas,
            'pago_id' => $intento['pago_id'],
            'preference_id' => $intento['preference_id'],
            'detalle_cuotas' => $cuotas > 1 ? 'en '.OpcionPagoVisible::detalle($cuotas, $intento['importe']) : null,
            'url_tarjeta' => $tipo === TipoMedioPago::DineroEnCuenta->value
                ? null
                : route('pago.intentos.tarjeta', [$token, $intento['pago_id']]),
            'url_estado' => route('pago.estado', $token),
        ];
    }

    /**
     * Orden de presentación: débito, dinero en cuenta y después el crédito de 1 pago y sus cuotas, de menos a más.
     *
     * @param  list<array{regla_id: int, tipo_medio_pago: string, cuotas: int, importe: string}>  $opciones
     * @return list<array{regla_id: int, tipo_medio_pago: string, cuotas: int, importe: string}>
     */
    private function ordenDeLaPagina(array $opciones): array
    {
        $orden = [
            TipoMedioPago::Debito->value => 0,
            TipoMedioPago::DineroEnCuenta->value => 1,
            TipoMedioPago::Credito->value => 2,
        ];

        return collect($opciones)
            ->sortBy(fn (array $opcion): array => [$orden[$opcion['tipo_medio_pago']] ?? 3, $opcion['cuotas']])
            ->values()
            ->all();
    }

    /**
     * La modalidad que el cliente ya eligió se reconoce por tipo y cuotas, porque el intento no guarda la regla.
     *
     * @param  list<array{regla_id: int, tipo_medio_pago: string, cuotas: int, importe: string}>  $opciones
     * @param  ?array{tipo_medio_pago: string, cuotas: int}  $intento
     */
    private function opcionDelIntento(array $opciones, ?array $intento): ?int
    {
        if ($intento === null) {
            return null;
        }

        foreach ($opciones as $opcion) {
            if ($opcion['tipo_medio_pago'] === $intento['tipo_medio_pago'] && $opcion['cuotas'] === $intento['cuotas']) {
                return $opcion['regla_id'];
            }
        }

        return null;
    }

    /**
     * @param  Closure(): Pedido  $buscar
     */
    private function pedidoDelLink(Request $request, Closure $buscar, ContactoService $contacto): Pedido|Response
    {
        try {
            return $buscar();
        } catch (NotFoundHttpException $excepcion) {
            if ($request->expectsJson()) {
                throw $excepcion;
            }

            return $this->respuestaLinkInactivo($request, $contacto);
        }
    }

    private function respuestaLinkInactivo(Request $request, ContactoService $contacto): Response
    {
        if ($this->esHtmx($request)) {
            return response()->view('tienda.partials.pago-error', ['mensaje' => self::MENSAJE_LINK_INACTIVO], 422);
        }

        return response()->view('tienda.pago-inactivo', [
            'whatsappUrl' => $contacto->publico()['whatsapp_url'],
        ], 404);
    }

    private function verPedidoDelCliente(Pedido $pedido): ?string
    {
        return auth()->check() && (int) auth()->id() === (int) $pedido->usuario_id
            ? route('cuenta.pedidos.mostrar', $pedido->numero)
            : null;
    }

    private function clavePublica(): ?string
    {
        $clave = trim((string) config('mercadopago.public_key'));

        return $clave === '' ? null : $clave;
    }

    private function primerMensaje(ValidationException $excepcion): string
    {
        return (string) (collect($excepcion->errors())->flatten()->first() ?? 'No pudimos procesar el pago. Intentá de nuevo.');
    }

    private function esHtmx(Request $request): bool
    {
        return $request->hasHeader('HX-Request');
    }
}
