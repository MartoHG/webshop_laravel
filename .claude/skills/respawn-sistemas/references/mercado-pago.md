# Mercado Pago

> Referencia de `respawn-sistemas`: cómo cobra una tienda de la casa. Las
> reglas verificables están en `checklist.md`. Antes de implementar, se
> contrasta este flujo con la documentación oficial de Mercado Pago para
> desarrolladores.

## Contenido

1. Piezas
2. Flujo de un cobro
3. El webhook
4. Firma del aviso
5. Aplicar un pago
6. Conciliación
7. Estados
8. Configuración
9. Pruebas

## 1. Piezas

| Pieza                                  | Qué hace                                                                    |
| -------------------------------------- | --------------------------------------------------------------------------- |
| `Pedido`                               | Lo que el visitante compra, con la copia de cada ítem y su total            |
| `Pago`                                 | Un intento de cobro de un pedido. Un pedido puede tener varios              |
| `MercadoPagoCliente`                   | Único lugar que habla con la API: crear, consultar y buscar pagos           |
| `VerificadorFirmaMercadoPago`          | Comprueba que un aviso viene de Mercado Pago                                |
| Controller del webhook                 | Recibe el aviso, valida la firma y despacha el job                          |
| `ProcesarNotificacionMercadoPago`      | Job que consulta el pago a la API y lo manda a aplicar                      |
| `PagoService`                          | Reglas del cobro: iniciar un intento y aplicar su resultado                 |
| Tarea `pagos:conciliar`                | Recupera los pagos pendientes cuyo aviso no llegó                           |

- La integración usa el cliente HTTP de Laravel. No hace falta un paquete.
- Todo el código que nombra a Mercado Pago vive en `app/Servicios/Pagos/`, en
  el job y en el Controller del webhook.

## 2. Flujo de un cobro

1. **El visitante confirma el carrito.** El servidor vuelve a leer precios y
   stock, crea el `Pedido` con la copia de cada ítem y calcula el total.
2. **Se inicia un intento.** `PagoService` crea un `Pago` pendiente con su
   clave de idempotencia y pide el cobro a Mercado Pago con esa clave, la
   referencia externa y la dirección del webhook.
3. **El visitante paga** en Mercado Pago y vuelve al sitio. La página de
   regreso muestra el estado que tiene el pedido en la base; no confía en los
   parámetros de la dirección.
4. **Llega el aviso.** El webhook valida la firma, responde y despacha el job.
5. **El job consulta el pago** a la API y `PagoService` aplica el resultado.

- La referencia externa une el pedido con el intento, por ejemplo
  `{número de pedido}:{id del pago}`, y permite encontrar el `Pago` desde
  cualquier aviso.
- La clave de idempotencia es un identificador único por intento. Reintentar
  el mismo pedido de cobro con la misma clave no cobra dos veces.

## 3. El webhook

```php
public function recibir(Request $request, VerificadorFirmaMercadoPago $verificador): JsonResponse
{
    if (! $verificador->esValida($request)) {
        return response()->json(['mensaje' => 'Firma inválida.'], 401);
    }

    if (($request->query('type') ?? $request->input('type')) !== 'payment') {
        return response()->json(['mensaje' => 'Notificación ignorada.']);
    }

    $idPago = $verificador->idDelDato($request);

    if ($idPago !== null) {
        ProcesarNotificacionMercadoPago::dispatch($idPago);
    }

    return response()->json(['mensaje' => 'Notificación recibida.']);
}
```

- La ruta vive en su propio archivo, fuera del grupo autenticado y sin CSRF,
  con un límite de tasa.
- Responde `200` enseguida. Mercado Pago espera la respuesta unos segundos y,
  si no llega, reintenta más tarde.
- Un tipo de aviso que el sistema no usa se responde con `200` y se ignora:
  un error haría que lo reenvíen.
- Es la única ruta del sistema que responde JSON.

## 4. Firma del aviso

Mercado Pago firma cada aviso con la clave secreta del webhook.

| Dato          | De dónde sale                                                    |
| ------------- | ---------------------------------------------------------------- |
| `ts` y `v1`   | De la cabecera `x-signature`, con forma `ts=…,v1=…`              |
| `request-id`  | De la cabecera `x-request-id`                                    |
| `id`          | Del parámetro `data.id` de la dirección, pasado a minúsculas     |

1. Armar el manifiesto: `id:{id};request-id:{request-id};ts:{ts};`.
2. Calcular el HMAC con SHA-256 del manifiesto, con la clave secreta.
3. Comparar el resultado con `v1` usando `hash_equals`.

- Si falta `id`, su parte se quita del manifiesto.
- PHP convierte el punto de `data.id` en guion bajo al leer los parámetros:
  el valor se lee de la cadena de consulta cruda.
- Sin clave secreta configurada, toda firma es inválida.

## 5. Aplicar un pago

`PagoService::aplicarPagoMercadoPago(array $pagoMp)` es el único punto por el
que cambia el estado de un pago, lo llame el job o la conciliación.

1. **Ubicar** el `Pago` por la referencia externa. Si no existe, registrar el
   caso y salir.
2. **Abrir una transacción** y bloquear el pedido para escritura.
3. **Comparar el importe** de Mercado Pago con el del `Pago`, sin punto
   flotante. Si difieren, el pago queda en revisión.
4. **Aplicar según el estado** de Mercado Pago, con la tabla de estados.
5. **Avisar** al cliente del negocio y al comprador cuando el pedido cambia.

**Idempotencia:** antes de cambiar nada, se compara el estado que llega con
el que ya está guardado. Si el pago ya está aprobado, un segundo aviso de
aprobación no descuenta stock, no manda correos y no cambia el pedido.

- El stock se descuenta en la misma transacción en que el pago se aprueba.
- El job reintenta con espera creciente ante un error de red o del servidor
  de Mercado Pago. Ante un rechazo definitivo de la consulta, no reintenta.

## 6. Conciliación

Un aviso se puede perder: el servidor estaba caído, la cola frenada o la
firma mal configurada. La conciliación es la red.

- Una tarea programada corre cada pocos minutos.
- Toma los pagos pendientes desde hace más de diez minutos.
- Consulta cada uno a la API, por su identificador o por su referencia
  externa, y aplica el resultado por el mismo punto que el job.
- Un intento que nunca llegó a existir en Mercado Pago se cancela pasado un
  plazo mayor.
- Cada corrida informa cuántos concilió, cuántos canceló y cuántos fallaron.

## 7. Estados

| Estado en Mercado Pago         | Estado del pago en el sistema | Efecto en el pedido                         |
| ------------------------------ | ----------------------------- | ------------------------------------------- |
| `approved`                     | Aprobado                      | Pagado; descuenta stock y avisa             |
| `pending`, `in_process`        | Pendiente                     | Sigue esperando                             |
| `rejected`, `cancelled`        | Rechazado                     | Se puede volver a intentar                  |
| `refunded`, `charged_back`     | En revisión                   | Avisa al administrador; no se resuelve solo |
| Importe distinto al del pedido | En revisión                   | Avisa al administrador                      |

- Los estados del sistema son un Enum. El panel muestra su etiqueta en
  palabras del negocio.
- Un pago en revisión lo resuelve una persona desde el panel, con una nota.

## 8. Configuración

| Variable                       | Para qué                                              |
| ------------------------------ | ----------------------------------------------------- |
| `MERCADOPAGO_ACCESS_TOKEN`     | Credencial privada para hablar con la API             |
| `MERCADOPAGO_PUBLIC_KEY`       | Credencial pública, para el navegador                 |
| `MERCADOPAGO_WEBHOOK_SECRET`   | Clave secreta con la que se valida la firma           |

- Viven en el entorno y se leen desde `config/mercadopago.php`.
- Las credenciales de prueba y las de producción son juegos distintos: cada
  entorno usa solo el suyo.
- La cuenta de Mercado Pago es del cliente. A nombre de quién está figura en
  los datos de entrega.
- La dirección del webhook se carga en el panel de Mercado Pago, con el
  dominio definitivo y HTTPS.

## 9. Pruebas

Las pruebas no llaman a Mercado Pago: simulan sus respuestas.

| Caso                                                        | Resultado esperado                                    |
| ----------------------------------------------------------- | ----------------------------------------------------- |
| Aviso con firma válida de un pago aprobado                  | Pedido pagado, stock descontado una vez               |
| El mismo aviso tres veces                                   | El mismo estado final que con uno                     |
| Aviso de pendiente que llega después del de aprobado        | El pedido sigue pagado                                |
| Aviso con firma inválida                                    | 401 y ningún cambio                                   |
| Pago aprobado con un importe distinto al del pedido         | Pago en revisión, pedido sin marcar como pagado       |
| Pago rechazado                                              | El pedido admite un intento nuevo                     |
| Aviso que nunca llega                                       | La conciliación aplica el resultado                   |
| La API responde con error del servidor                      | El job reintenta; el pedido no cambia                 |

Antes de entregar se hace además una compra real de punta a punta con las
credenciales de producción y un importe mínimo, y se la reembolsa.
