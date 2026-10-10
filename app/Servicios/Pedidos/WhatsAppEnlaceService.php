<?php

namespace App\Servicios\Pedidos;

use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Models\Pedido;
use Illuminate\Validation\ValidationException;

class WhatsAppEnlaceService
{
    private const string PREFIJO_WHATSAPP = 'https://wa.me/';

    /**
     * Normaliza un teléfono argentino al formato internacional de wa.me (549 + código de área + número).
     * Devuelve null cuando no puede normalizarse.
     */
    public function normalizarTelefono(string $telefono): ?string
    {
        $digitos = (string) preg_replace('/\D+/', '', $telefono);

        if (str_starts_with($digitos, '00')) {
            $digitos = substr($digitos, 2);
        }

        if (str_starts_with($digitos, '549') && strlen($digitos) === 13) {
            return $digitos;
        }

        if (str_starts_with($digitos, '54') && strlen($digitos) === 12) {
            return '549'.substr($digitos, 2);
        }

        if (str_starts_with($digitos, '0')) {
            $digitos = substr($digitos, 1);
        }

        if (strlen($digitos) === 12) {
            foreach ([2, 3, 4] as $largoCodigoArea) {
                if (substr($digitos, $largoCodigoArea, 2) === '15') {
                    $digitos = substr($digitos, 0, $largoCodigoArea).substr($digitos, $largoCodigoArea + 2);

                    break;
                }
            }
        }

        return strlen($digitos) === 10 ? '549'.$digitos : null;
    }

    /**
     * @return array{coordinar_envio: ?array<string, mixed>, link_pago: ?array<string, mixed>}
     */
    public function enlacesDelPedido(Pedido $pedido): array
    {
        return [
            'coordinar_envio' => $pedido->estado_pedido === EstadoPedido::Cancelado ? null : $this->enlaceCoordinarEnvio($pedido),
            'link_pago' => $this->puedeEnviarLinkPago($pedido) ? $this->enlacePago($pedido) : null,
        ];
    }

    /**
     * @return array{url: ?string, telefono: string, telefono_normalizado: ?string, mensaje: string}
     *
     * @throws ValidationException
     */
    public function enlaceCoordinarEnvio(Pedido $pedido): array
    {
        if ($pedido->estado_pedido === EstadoPedido::Cancelado) {
            throw ValidationException::withMessages(['pedido' => 'El pedido está cancelado. No se puede coordinar el envío.']);
        }

        $mensaje = sprintf(
            'Hola %s, te escribimos de El Refugio por tu pedido %s. Queremos coordinar el envío a %s, %s. ¿Tenés algún expreso de preferencia?',
            $pedido->nombre_cliente,
            $pedido->numero,
            $pedido->localidad_envio,
            $pedido->provincia_envio->nombre(),
        );

        return $this->enlace($pedido->telefono_cliente, $mensaje);
    }

    /**
     * @return array{url: ?string, telefono: string, telefono_normalizado: ?string, mensaje: string}
     *
     * @throws ValidationException
     */
    public function enlacePago(Pedido $pedido): array
    {
        if (! $this->puedeEnviarLinkPago($pedido)) {
            throw ValidationException::withMessages([
                'pago' => 'Todavía no hay un link de pago para este pedido. Habilitá el pago primero.',
            ]);
        }

        $mensaje = sprintf(
            'Hola %s, ya podés pagar tu pedido %s. Total con envío: $%s. Pagá desde este link: %s',
            $pedido->nombre_cliente,
            $pedido->numero,
            $this->formatearImporte((string) $pedido->total_base),
            url('/pago/'.$pedido->token_pago),
        );

        return $this->enlace($pedido->telefono_cliente, $mensaje);
    }

    public function puedeEnviarLinkPago(Pedido $pedido): bool
    {
        return in_array($pedido->estado_pago, [EstadoPago::Habilitado, EstadoPago::Pendiente], true)
            && $pedido->token_pago !== null;
    }

    /** @return array{url: ?string, telefono: string, telefono_normalizado: ?string, mensaje: string} */
    private function enlace(string $telefono, string $mensaje): array
    {
        $normalizado = $this->normalizarTelefono($telefono);

        return [
            'url' => $normalizado === null
                ? null
                : self::PREFIJO_WHATSAPP.$normalizado.'?text='.rawurlencode($mensaje),
            'telefono' => $telefono,
            'telefono_normalizado' => $normalizado,
            'mensaje' => $mensaje,
        ];
    }

    /**
     * Separa miles con punto y decimales con coma, sin pasar el importe por flotantes.
     */
    private function formatearImporte(string $importe): string
    {
        [$entero, $decimales] = array_pad(explode('.', $importe, 2), 2, '00');

        return preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $entero).','.str_pad($decimales, 2, '0');
    }
}
