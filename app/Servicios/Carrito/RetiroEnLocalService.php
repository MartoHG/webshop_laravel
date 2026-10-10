<?php

namespace App\Servicios\Carrito;

use App\Servicios\Contenido\ContactoService;
use App\Servicios\Pedidos\WhatsAppEnlaceService;
use App\Servicios\Precios\FormatoMoneda;
use Illuminate\Validation\ValidationException;

class RetiroEnLocalService
{
    public function __construct(
        private CarritoService $carrito,
        private ContactoService $contacto,
        private WhatsAppEnlaceService $whatsapp,
    ) {}

    /**
     * Arma el enlace de WhatsApp para retirar en el local. Solo incluye las líneas disponibles del carrito.
     *
     * @return array{url: string, mensaje: string}
     *
     * @throws ValidationException
     */
    public function enlace(): array
    {
        $resumen = $this->carrito->obtener();
        $disponibles = array_values(array_filter($resumen['items'], fn (array $item): bool => $item['disponible']));

        if ($disponibles === []) {
            throw ValidationException::withMessages(['carrito' => 'Tu carrito no tiene productos disponibles.']);
        }

        $mensaje = $this->mensaje($disponibles, $resumen['subtotal']);
        $telefono = $this->contacto->whatsappDelNegocio();
        $url = $telefono === null ? null : $this->whatsapp->urlParaTelefono($telefono, $mensaje);

        if ($url === null) {
            throw ValidationException::withMessages(['whatsapp' => 'El negocio todavía no cargó su WhatsApp.']);
        }

        return ['url' => $url, 'mensaje' => $mensaje];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function mensaje(array $items, string $subtotal): string
    {
        $lineas = array_map(
            fn (array $item): string => sprintf(
                '• %d × %s%s – %s',
                $item['cantidad'],
                $item['nombre_producto'],
                $this->detalle($item),
                FormatoMoneda::formatear((string) $item['subtotal']),
            ),
            $items,
        );

        return implode("\n", [
            'Hola El Refugio, quiero retirar en el local:',
            ...$lineas,
            'Total: '.FormatoMoneda::formatear($subtotal),
            '¿Cuándo puedo pasar?',
        ]);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function detalle(array $item): string
    {
        $atributos = array_filter(
            [$item['color'], $item['etiqueta']],
            fn (mixed $valor): bool => is_string($valor) && $valor !== '',
        );

        return $atributos === [] ? '' : ' ('.implode(', ', $atributos).')';
    }
}
