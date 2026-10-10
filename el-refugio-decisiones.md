# El Refugio — Especificación final de diseño del sistema

## 1. Propósito del documento

Este documento consolida las decisiones funcionales, técnicas y de arquitectura definidas para **El Refugio**.

Su objetivo es servir como **documento rector para Claude**, que será quien implemente el sistema.

La intención es que Claude pueda desarrollar sobre una base clara y consistente, respetando las decisiones ya tomadas y evitando introducir patrones, capas o tecnologías que contradigan este diseño.

---

# 2. Objetivo general

Construir un **webshop/ecommerce** para **El Refugio**, una forrajería.

Rubros iniciales:

- Muebles para jardín
- Artículos de pesca
- Kayaks

El público objetivo estará compuesto, en buena medida, por usuarios con poca experiencia informática.

Por lo tanto, la aplicación deberá priorizar:

- navegación simple;
- pocos pasos;
- acciones claras;
- reducción de fricción;
- mensajes comprensibles;
- checkout sencillo.

Las decisiones visuales y de UX se trabajarán aparte con Claude, usando:

- la skill **impeccable** para dar forma, auditar y pulir la interfaz;
- la skill **emil-design-eng**, con los criterios de Emil Kowalski sobre detalle de componentes, animaciones y pulido;
- las herramientas de diseño de Anthropic.

El objetivo es evitar una tienda genérica con aspecto de plantilla: la identidad visual debe sentirse propia de El Refugio sin perder la simplicidad que necesita su público.

---

# 3. Stack tecnológico confirmado

## Backend

- PHP
- Laravel

## Frontend

- Blade
- Tailwind CSS
- HTMX para interactividad puntual

## Base de datos

- MySQL para desarrollo local, pruebas y producción.
- Las pruebas deben conectarse a una base MySQL exclusiva, separada de la base de desarrollo.

## Enfoque

Aplicación Laravel tradicional.

No se utilizará React, Vue ni una SPA salvo que aparezca una necesidad concreta no prevista.

---

# 4. Arquitectura obligatoria

La arquitectura del proyecto será la arquitectura Laravel tradicional utilizada por el mantenedor del sistema:

```text
Controller
    ↓
Form Request
    ↓
Service
    ↓
Model / Eloquent
```

## Reglas

- Los Controllers deben ocuparse de HTTP, coordinación y respuestas.
- Los Form Requests deben validar entrada y autorización cuando corresponda.
- Los Services deben contener la lógica de negocio.
- Eloquent se utilizará directamente desde los Services cuando sea suficiente.
- No introducir DDD, Actions, Use Cases, Repositories, Clean Architecture u otras capas por defecto.
- No crear abstracciones si no existe una necesidad concreta.

La organización interna podrá usar carpetas y subcarpetas por módulo o funcionalidad.

La estructura exacta de carpetas será definida por Claude respetando esta arquitectura.

---

# 5. Convención de idioma

Todo el código propio del dominio debe estar nombrado en **español**.

Esto incluye:

- clases;
- Controllers;
- Services;
- Form Requests;
- métodos;
- variables;
- rutas funcionales;
- tablas;
- columnas;
- enums;
- nombres de módulos;
- nombres de acciones.

Ejemplos:

```php
ProductoController
ProductoService
CrearProductoRequest
ActualizarProductoRequest

$producto
$variante
$pedido
$cliente

crear()
actualizar()
eliminar()
habilitarPago()
coordinarEnvio()
calcularPrecio()
```

Excepciones:

- convenciones propias de Laravel/PHP;
- campos requeridos por librerías;
- campos requeridos por Mercado Pago u otras integraciones;
- convenciones estándar como `created_at`, `updated_at`, etc.

---

# 6. Roles del sistema

Solo existirán dos roles:

```text
administrador
cliente
```

## Administrador

Puede acceder al panel administrativo y gestionar el sistema.

## Cliente

Puede:

- registrarse;
- iniciar sesión;
- realizar pedidos;
- ver su cuenta;
- consultar sus pedidos.

No se implementará sistema granular de permisos en V1.

No se utilizará Spatie Permissions salvo necesidad futura.

---

# 7. Usuarios y autenticación

Para realizar un pedido será obligatorio tener una cuenta.

Datos principales:

- nombre;
- apellido;
- DNI;
- email;
- teléfono / WhatsApp;
- contraseña.

## Reglas

- Email obligatorio.
- DNI obligatorio y único por usuario.
- Teléfono obligatorio.
- No habrá validación automática del teléfono mediante WhatsApp API en V1.
- La validación práctica del número se realizará durante la coordinación del pedido.
- El usuario puede registrarse, iniciar sesión y usar el carrito sin verificar su email.
- Para confirmar el checkout y crear el pedido, el email debe estar verificado. Esto permite usarlo como canal alternativo si el teléfono es incorrecto.
- El cliente puede modificar su email, teléfono y DNI desde su cuenta. El DNI nuevo debe seguir siendo único.
- Si modifica el email, deberá verificar la nueva dirección antes de confirmar otro checkout.
- El registro público solo crea cuentas de cliente. Un administrador puede crear otras cuentas de administrador desde el panel.
- Un usuario desactivado no podrá iniciar sesión.
- Los pedidos históricos deben conservarse aunque un usuario sea desactivado.

---

# 8. Productos

La entidad `Producto` representa el producto comercial.

Conceptualmente:

```text
Producto
├── nombre
├── slug
├── descripción
├── activo
├── disponible
├── categorías
├── imágenes generales
└── variantes
```

## Reglas

- Un producto puede pertenecer a varias categorías.
- El producto no tendrá precio directamente.
- Todo producto tendrá al menos una variante interna.
- El `slug` del producto queda fijo después de crearlo: cambiar el nombre no modifica el enlace y el administrador no lo edita en V1.
- Los productos nuevos se crean inactivos y no disponibles; permanecen ocultos hasta que el administrador los active.
- Un producto inactivo puede guardarse sin categoría ni imagen mientras se completa.
- Para activar un producto, debe tener al menos una categoría activa asignada, una imagen general principal y al menos una variante activa.
- Se rechazan los cambios que dejarían a un producto activo sin su última categoría activa, sin imagen general principal o sin ninguna variante activa; no se lo desactiva automáticamente.
- Un producto puede estar activo pero no disponible.
- Un producto desactivado no debe aparecer públicamente.
- Un producto agotado/no disponible no debe aparecer en la home.
- Un producto agotado puede seguir apareciendo en categorías/búsqueda, marcado como agotado.
- Un producto agotado no puede comprarse.

---

# 9. Variantes de producto

La única variante confirmada en V1 es el **color**.

No se implementará todavía un sistema genérico de atributos.

Conceptualmente:

```text
VarianteProducto
├── producto_id
├── color nullable
├── etiqueta descriptiva nullable
├── sku
├── precio_base
├── activo
├── disponible
└── imágenes propias
```

## Reglas

- Productos sin color también tendrán una variante interna.
- `color` puede ser `null` y, cuando se informa, es texto libre; no existe un catálogo de colores en V1.
- Para detectar colores repetidos, se ignoran las diferencias entre mayúsculas y minúsculas y los espacios al principio o al final.
- Una variante sin color puede coexistir con variantes de color dentro del mismo producto.
- El color no debe ser único dentro de un producto: pueden existir varias variantes del mismo color o varias sin color, diferenciadas por su SKU.
- La etiqueta descriptiva es obligatoria para todas las variantes de un producto que compartan color, incluido `null`, aunque alguna esté inactiva; en los demás casos es opcional.
- Las etiquetas descriptivas informadas deben ser distintas dentro de un mismo producto.
- Cada variante tendrá SKU único en toda la tienda.
- El SKU de una variante desactivada no se reutiliza en otra variante.
- Una variante nueva se crea inactiva y no disponible; el administrador debe activar y confirmar su disponibilidad expresamente.
- Cada variante tendrá su propio precio base, siempre mayor a $0 y con hasta dos decimales.
- Un producto inactivo puede guardarse con varias variantes inactivas y sin imágenes.
- Una variante inactiva puede guardarse sin imagen, pero no puede activarse hasta tener una imagen asignada.
- Una variante puede activarse mientras su producto está inactivo; el producto permanece oculto hasta que se active expresamente.
- Si solo hay una variante activa, puede utilizar la imagen general aunque existan otras variantes inactivas. Al activar una segunda variante, ambas deben tener una imagen asignada a cada variante.
- Se rechaza retirar la última imagen que utiliza una variante activa; no se la desactiva automáticamente.
- Una variante puede tener varias imágenes y, en ese caso, debe tener una imagen principal.
- Una variante puede estar activa pero no disponible.
- Una variante no disponible sigue visible en el producto, marcada como agotada.
- Una variante no disponible no puede agregarse al carrito.

---

# 10. Disponibilidad

No habrá gestión de stock numérico en V1.

No se implementará:

- cantidad disponible;
- descuento automático de stock;
- reserva;
- alerta de bajo stock;
- movimientos de inventario.

La disponibilidad será manual.

Se diferenciarán dos conceptos:

```text
activo
disponible
```

Ejemplo:

```text
activo = false
→ oculto / dado de baja

activo = true
disponible = false
→ visible como agotado cuando corresponda
```

La compra deberá validarse en backend.

No alcanza con deshabilitar botones en frontend.

---

# 11. Categorías

Los productos tendrán relación N:N con categorías.

Conceptualmente:

```text
productos
categorias
categoria_producto
```

Las categorías normales podrán:

- crearse;
- editarse;
- activarse/desactivarse;
- ordenarse;
- asignarse a múltiples productos;
- tener una imagen opcional, que se usa en la grilla de categorías de la home;
- marcarse como destacadas para aparecer en la home.

No se puede desactivar una categoría si con ello algún producto activo quedaría sin ninguna categoría activa.

Las categorías son totalmente personalizables. Los rubros principales al inicio son muebles y sillones de jardín, y kayaks y pesca, pero el administrador puede crear las que necesite.

---

# 12. Categoría especial “Ofertas”

`Ofertas` será la única categoría especial del sistema.

Debe existir como categoría real en la base de datos, pero identificada mediante una clave estable de sistema.

Ejemplo conceptual:

```text
nombre: Ofertas
slug: ofertas
clave_sistema: ofertas
```

## Reglas

- Se crea inicialmente por seeder.
- No debe depender de un ID fijo.
- No puede eliminarse.
- Tendrá un switch para activar/desactivar.
- Tendrá tratamiento especial en frontend.
- Si se desactiva, desaparece su tratamiento especial del frontend.
- Los productos asociados no se desactivan por desactivar la categoría.
- Un producto puede pertenecer a Ofertas y a otras categorías al mismo tiempo.

---

# 13. Imágenes de producto

Se dará libertad para múltiples imágenes.

Un producto puede tener:

- imágenes generales;
- múltiples imágenes por variante/color.

Modelo conceptual:

```text
imagenes_producto
- id
- producto_id
- variante_producto_id nullable
- ruta
- ruta_miniatura
- orden
- es_principal
```

## Reglas

- Si `variante_producto_id` es `null`, la imagen es general.
- Si una variante tiene imágenes propias, podrán mostrarse al seleccionar esa variante.
- Si solo hay una variante activa, la imagen general puede utilizarse también para esa variante aunque existan otras inactivas.
- El mismo archivo de imagen puede asignarse como imagen general y a una variante, o a varias variantes, sin subirlo nuevamente.
- Debe existir orden manual.
- Debe existir imagen principal.
- La base de datos guarda rutas, no binarios.

---

# 14. Almacenamiento de imágenes

La V1 será productiva/pública, por lo que se utilizará almacenamiento cloud desde producción.

## Reglas

- Usar Laravel Storage.
- Proveedor preferentemente S3-compatible.
- Configuración por `.env`.
- No acoplar lógica a un proveedor específico.

## Procesamiento

Al subir imágenes:

```text
subir
→ validar
→ redimensionar
→ comprimir
→ convertir a WebP
→ generar miniatura/listado
→ generar versión de detalle
→ subir al cloud
→ eliminar original pesado
```

No se conservará el original pesado.

El procesamiento se realizará mediante Jobs/colas para evitar bloquear el panel administrativo.

---

# 15. Estrategia de precios

El sistema tendrá tres niveles conceptuales:

```text
precio_base
    ↓
ajuste_ecommerce
    ↓
precio_publicado de los productos
    ↓
subtotal_productos + costo_envio = total_base del pedido
    ↓
compensación del porcentaje de la modalidad/cuotas elegida
    ↓
importe_final_de_pago
```

---

# 16. Precio base

`precio_base` pertenece a la variante.

Representa el valor comercial base definido por el negocio.

El producto no tendrá un precio separado.

---

# 17. Ajuste e-commerce

El sitio tendrá un ajuste general para cubrir el costo normal del canal online.

Inicialmente se habló de aproximadamente 9%, pero el valor será **configurable desde administración**.

No estará hardcodeado.

Ejemplo conceptual:

```text
precio_base = 100.000
ajuste_ecommerce = 9%
precio_publicado = 109.000
```

La configuración podrá cambiar en el futuro sin modificar manualmente todos los productos.

---

# 18. Precio financiado / cuotas

El cliente puede tener un costo adicional al elegir cuotas.

El negocio no quiere absorber pérdidas grandes por financiación.

Los porcentajes por modalidad/cuotas serán configurables por el dueño.

Ejemplo:

```text
Crédito 1 pago     → regla configurada
Crédito 3 cuotas   → regla configurada
Crédito 6 cuotas   → regla configurada
```

Los números concretos se administrarán desde el panel.

## Regla de cálculo

El porcentaje configurado para la modalidad/cuotas representa una deducción que se compensa para preservar el total que debe recibir el negocio. No se suma el porcentaje directamente.

La base del cálculo es el **total del pedido**, compuesto por el subtotal de productos a precio publicado más el costo de envío acordado por WhatsApp. El envío forma parte del mismo cobro y no se cobra por separado.

Conceptualmente:

```text
total_base = subtotal_productos + costo_envio
importe_a_cobrar = total_base / (1 - porcentaje)
```

Ejemplo:

```text
total_base: 100.000 (productos y envío incluidos)
porcentaje configurado: 20%

100.000 / 0,80 = 125.000
```

El Service de pricing será responsable del cálculo.

El importe a cobrar se redondea **hacia arriba** al centavo, para que el negocio nunca reciba menos que `total_base`.

---

# 19. Reglas de pricing de pagos

Las reglas deben ser editables desde administración.

Modelo conceptual:

```text
reglas_precio_pago
- id
- tipo_medio_pago
- cuotas
- porcentaje
- activo
```

## Reglas

- No hardcodear porcentajes.
- Una combinación no configurada/no activa no debe procesarse silenciosamente como 0%.
- Guardar en cada intento de pago el porcentaje efectivamente utilizado.
- Las reglas actuales no deben alterar pagos históricos.
- El sistema debe permitir desactivar modalidades/cuotas que el negocio no quiera aceptar.
- Cada combinación de `tipo_medio_pago` y `cuotas` es única.
- El porcentaje debe ser mayor o igual a 0 y menor a 100.

## Medios de pago aceptados en V1

```text
credito            → tarjeta de crédito, en 1 o más cuotas
debito             → tarjeta de débito, 1 pago
dinero_en_cuenta   → saldo de la cuenta de Mercado Pago, 1 pago
```

No se acepta efectivo (Rapipago/Pago Fácil) en V1.

Una modalidad solo se ofrece al cliente si tiene una regla activa. Las reglas iniciales se crean inactivas: el dueño debe cargar los porcentajes antes de la primera venta.

---

# 20. Actualizaciones masivas de precios

Es un requisito prioritario.

El administrador debe poder:

- aumentar X%;
- disminuir X%;
- establecer importe fijo;
- modificar una variante;
- modificar un producto completo;
- modificar productos seleccionados;
- modificar por categoría;
- modificar todos los productos.

Las operaciones deben modificar `precio_base`.

## Reglas

Antes de aplicar una actualización masiva:

- mostrar vista previa;
- indicar valores anteriores y nuevos;
- mostrar cantidad de registros afectados.

La operación debe ejecutarse dentro de una transacción.

Debe existir historial/auditoría de cambios masivos.

- La actualización alcanza también a las variantes inactivas incluidas en el filtro.
- Si algún precio resultante quedara en $0 o menos, se rechaza el lote completo y no se modifica nada.
- Se aplica exactamente lo que se previsualizó: si los precios o el conjunto afectado cambiaron desde la vista previa, se rechaza y hay que volver a previsualizar.

---

# 21. Carrito

El carrito trabajará siempre con variantes.

Conceptualmente:

```text
item_carrito
- variante_producto_id
- cantidad
```

Para V1 puede mantenerse en sesión.

## Reglas

- Cantidad mínima: 1. Cantidad máxima: 99 por línea.
- Agregar una variante que ya está en el carrito suma la cantidad en la misma línea.
- El carrito puede usarse sin iniciar sesión. Al iniciar sesión o registrarse, el carrito se conserva.
- La cuenta y el email verificado se exigen recién al confirmar el checkout.
- Revalidar precio y disponibilidad antes de confirmar checkout.
- El carrito no es fuente definitiva de precio.
- Si una variante deja de estar disponible, no se elimina silenciosamente.
- Debe mostrarse como agotada/no disponible y bloquear la continuación hasta resolverlo.
- Una variante o producto desactivado se trata igual que uno agotado: se muestra como no disponible y bloquea la continuación.

---

# 22. Checkout

El checkout **no realiza el pago inmediatamente**.

Su función es crear el pedido para iniciar la coordinación del envío.

La confirmación final del checkout requiere que el cliente tenga el email verificado.

Flujo:

```text
Carrito
→ validar productos / variantes / precios
→ checkout
→ datos del comprador
→ datos de envío
→ resumen
→ confirmar pedido
→ pedido creado
→ envío pendiente de coordinación
→ pago deshabilitado
```

---

# 23. Datos del checkout

Datos principales:

## Cliente

- nombre;
- apellido;
- DNI;
- email;
- teléfono / WhatsApp.

## Envío

- provincia;
- localidad;
- código postal;
- calle/dirección;
- número;
- piso/departamento opcional;
- referencias/observaciones opcionales.

Los datos deben guardarse como snapshot dentro del pedido.

Cambios posteriores en el perfil no deben modificar pedidos históricos.

---

# 24. Envíos

Se realizarán envíos a todo el país mediante **expreso**.

El negocio no conoce el costo del envío al momento de crear el pedido.

Por lo tanto, el envío será **a coordinar**.

Una vez acordado, el costo del envío se incorpora al total del pedido y se cobra junto con los productos en un único pago.

La estrategia elegida es:

```text
carrito
→ checkout
→ pedido
→ coordinación por WhatsApp
→ se obtiene costo real del expreso
→ administrador carga el costo
→ cliente acepta
→ administrador habilita pago
→ se envía link de pago
```

El cliente no paga antes de conocer el costo total.

---

# 25. Coordinación por WhatsApp

No se integrará WhatsApp Business API en V1.

Se utilizarán enlaces/deep-links de WhatsApp con mensajes prearmados.

Ejemplo de acción:

```text
[ Coordinar envío por WhatsApp ]
```

El botón abre WhatsApp con un mensaje ya preparado para que el administrador lo revise y envíe.

Los enlaces usan el formato `https://wa.me/<número>?text=<mensaje>`. El teléfono guardado se normaliza en lo posible al formato internacional argentino (`549` + código de área + número, sin `0` ni `15`). Si no se puede normalizar, no se rechaza: el administrador ve el número tal como lo cargó el cliente.

También existirá:

```text
[ Enviar link de pago por WhatsApp ]
```

cuando el pago esté habilitado.

---

# 26. Estados de contacto

El contacto debe tener estado independiente del pedido.

Ejemplo:

```text
pendiente
contactado
respondio
no_contestado
```

Si el número es incorrecto o el cliente no responde, el administrador podrá marcar el pedido como `no_contestado`.

---

# 27. Email como canal alternativo

No habrá API de WhatsApp en V1.

Si no se puede contactar al cliente por WhatsApp, podrá enviarse email.

Emails automáticos:

- verificación de email;
- recuperación de contraseña;
- pedido creado;
- pago aprobado.

Emails manuales desde administración:

- no pudimos contactarte por WhatsApp;
- recordatorio para continuar el pedido;
- pedido despachado.

## Pedido despachado

No debe enviarse automáticamente.

El envío se realiza mediante expreso y la gestión es manual.

Debe existir un botón administrativo:

```text
[ Enviar email de pedido despachado ]
```

El administrador decide cuándo enviarlo.

Los emails deben enviarse mediante Jobs/colas.

---

# 28. Pedidos

El pedido se crea antes del pago.

Estados separados:

```text
estado_pedido
estado_contacto
estado_pago
estado_envio
```

No utilizar un único estado combinando todos los conceptos.

---

# 29. Estados sugeridos

## Pedido

```text
pendiente_coordinacion
confirmado
cancelado
```

## Contacto

```text
pendiente
contactado
respondio
no_contestado
```

## Pago

```text
deshabilitado
habilitado
pendiente
pagado
rechazado
cancelado
```

`estado_pago` del pedido usa `deshabilitado`, `habilitado`, `pendiente`, `pagado` y `cancelado`. Un intento rechazado no deja el pedido en `rechazado`: vuelve a `habilitado` para que el cliente pueda reintentar.

Cada intento de pago (tabla `pagos`) tiene su propio estado:

```text
creado
pendiente
pagado
rechazado
cancelado
requiere_revision
```

## Envío

```text
pendiente_coordinacion
cotizado
aceptado
preparado
despachado
entregado
```

Claude puede ajustar nombres exactos manteniendo el significado y utilizando español.

---

# 30. Flujo principal del pedido

```text
Pedido creado
→ pendiente de coordinación

Administrador contacta por WhatsApp
→ contacto = contactado

Cliente responde
→ contacto = respondio

Administrador obtiene costo del expreso
→ envío = cotizado

Cliente acepta por WhatsApp
→ el administrador lo registra
→ envío = aceptado

Administrador habilita pago
→ se genera la orden de pago y su link
→ pago = habilitado

Cliente paga
→ webhook confirma pago
→ pago = pagado
→ pedido = confirmado

Negocio prepara
→ envío = preparado

Negocio despacha
→ envío = despachado

Entrega
→ envío = entregado
```

---

# 31. Cancelación

No habrá cancelación automática.

Antes del pago, solo un administrador puede cancelar manualmente un pedido.

Debe guardarse:

- fecha;
- administrador;
- motivo.

Ejemplos de motivo:

- cliente desistió;
- no respondió;
- problema con pago;
- problema logístico;
- otro.

El motivo `reemplazado` lo asigna el sistema al reemplazar un pedido (sección 32); el administrador no lo elige manualmente.

No se puede cancelar un pedido mientras tenga un intento de pago `pendiente` en Mercado Pago.

Los inconvenientes posteriores a un pago confirmado se conversarán y resolverán por WhatsApp. La V1 no gestionará desde la página la cancelación de pedidos pagados ni las devoluciones de dinero.

---

# 32. Edición del pedido

Antes de generar la orden de pago, el administrador puede:

- agregar productos;
- quitar productos;
- cambiar variantes;
- cambiar cantidades;
- recalcular importes.

El costo del envío se acuerda previamente por WhatsApp. Al finalizar esa coordinación se genera y envía la orden de pago.

**Habilitar el pago y generar la orden de pago son el mismo acto**: crea el link de pago y bloquea la edición del pedido.

Los precios del pedido son un snapshot. Si cambian los precios base o el ajuste e-commerce, los pedidos abiertos no se recalculan solos: el administrador puede usar la acción "recalcular importes", disponible solo antes de habilitar el pago.

Si después de generar la orden de pago hay que modificar productos, cantidades, precios o envío, se cancela esa orden de pago y se genera un pedido nuevo. No se modifica el pedido vinculado a una orden de pago vigente.

Al reemplazar un pedido:

- el pedido original pasa a `cancelado` con motivo `reemplazado`;
- sus intentos de pago no pagados se cancelan y su link de pago deja de funcionar;
- el pedido nuevo copia los datos del cliente, el envío y los items, recibe un número nuevo y referencia al original en `pedido_origen_id`;
- el pedido nuevo nace con el pago `deshabilitado`, para que el administrador lo edite y vuelva a habilitar el pago.

Una vez que Mercado Pago confirme el pago:

```text
pedido bloqueado
```

No se podrán modificar:

- productos;
- cantidades;
- precios;
- importes.

El bloqueo debe estar aplicado en backend, no solo en UI.

Después del pago, el administrador sí puede corregir la dirección y las observaciones de envío, el estado de contacto y el avance del envío. Esas correcciones quedan auditadas.

---

# 33. Datos del pedido

Modelo conceptual:

```text
pedidos

id
numero
usuario_id
pedido_origen_id nullable
token_pago nullable

nombre_cliente
apellido_cliente
dni_cliente
email_cliente
telefono_cliente

provincia_envio
localidad_envio
codigo_postal_envio
direccion_envio
direccion_adicional nullable
observaciones_envio nullable

expreso nullable

subtotal_productos
costo_envio nullable
total_base nullable

estado_pedido
estado_contacto
estado_envio
estado_pago

contactado_en nullable
pago_habilitado_en nullable
pagado_en nullable
preparado_en nullable
despachado_en nullable
entregado_en nullable

cancelado_en nullable
cancelado_por nullable
motivo_cancelacion nullable

created_at
updated_at
```

`total_base` es la suma de `subtotal_productos` y `costo_envio` acordado. Es el importe objetivo usado para calcular el cobro según la modalidad/cuotas elegida.

- `numero` tiene el formato `ER-AAAA-000123` (año de creación e id con ceros a la izquierda). Es único y nunca se reutiliza.
- `pedido_origen_id` apunta al pedido reemplazado, cuando corresponde (sección 32).
- `token_pago` es un valor aleatorio, único e imposible de adivinar que identifica el link de pago (sección 35).

---

# 34. Items de pedido

Cada item debe guardar snapshot.

Modelo conceptual:

```text
items_pedido

id
pedido_id

producto_id
variante_producto_id

nombre_producto
color nullable
sku

precio_unitario
cantidad
subtotal

created_at
updated_at
```

El pedido histórico no debe depender del precio/nombre actual del producto.

---

# 35. Mercado Pago

Mercado Pago será el proveedor de pagos.

La integración debe contemplar:

- múltiples intentos de pago;
- cuotas;
- pricing configurable;
- idempotencia;
- webhook;
- validación server-side;
- bloqueo de pedido al confirmar pago.

## Integración elegida: Checkout Bricks

Se usa **Checkout Bricks** dentro de la tienda, con la API de pagos de Mercado Pago llamada desde el backend. Checkout Pro queda descartado porque solo permite limitar el máximo de cuotas: el cliente podría elegir menos cuotas y pagar un importe calculado para más.

Flujo:

```text
link de pago (/pago/{token_pago})
→ el cliente ve las modalidades con regla activa y el importe de cada una
→ elige modalidad y cuotas
→ el backend crea el intento de pago con el porcentaje y el importe fijados
→ el Brick se muestra con ese importe y solo esas cuotas
→ el backend valida que el medio y las cuotas recibidos coincidan con el intento
→ el backend crea el pago en Mercado Pago con clave de idempotencia
→ el webhook confirma el resultado
```

- Si el medio de pago o las cuotas recibidos no coinciden con el intento, se rechaza sin llamar a Mercado Pago.
- El pago con dinero en cuenta usa una preferencia restringida a ese medio y a 1 pago; el webhook verifica igualmente medio, cuotas e importe.
- La integración usa el cliente HTTP de Laravel, para poder simular Mercado Pago en los tests.

## Link de pago

- El link no exige iniciar sesión: el `token_pago` aleatorio funciona como credencial.
- La página muestra solo el número de pedido, el detalle de productos, el envío, el total y las modalidades de pago. No muestra DNI, email ni dirección.
- El link funciona solo mientras el pago del pedido esté `habilitado` o `pendiente`. Deja de funcionar cuando el pedido se paga, se cancela o se reemplaza.

## Configuración de la cuenta de Mercado Pago

El cálculo de la sección 18 supone que el negocio absorbe el costo de las cuotas y lo compensa en el precio. Antes de producción, verificar que la cuenta de Mercado Pago no le cobre además interés al comprador por las cuotas ofrecidas; si lo hiciera, el comprador pagaría el costo dos veces.

---

# 36. Pagos

Un pedido puede tener muchos intentos de pago.

Relación:

```text
Pedido 1 → N Pagos
```

Modelo conceptual:

```text
pagos

id
pedido_id

id_pago_mercado_pago nullable
id_orden_mercado_pago nullable
clave_idempotencia

tipo_medio_pago
cuotas

importe_base
porcentaje_aplicado
importe_cobrado

estado
detalle_estado nullable

pagado_en nullable

created_at
updated_at
```

`importe_base` guarda el total del pedido, incluido el envío; `importe_cobrado` guarda el importe final calculado para ese intento de pago.

---

# 37. Confirmación de pago

El pedido solo se considera pagado cuando el backend reciba/verifique una confirmación válida de Mercado Pago.

No se debe confiar en:

- redirección del navegador;
- parámetros enviados por frontend;
- una pantalla de “éxito”.

Flujo:

```text
cliente paga
→ Mercado Pago procesa
→ webhook
→ backend verifica operación
→ si pago aprobado
→ marcar pago como pagado
→ marcar pedido como confirmado
→ bloquear edición del pedido
```

- El webhook se valida con la firma de Mercado Pago. Después, el backend vuelve a consultar el pago en la API de Mercado Pago y verifica la referencia del pedido, el importe, la moneda, el medio y las cuotas.
- Recibir varias veces la misma notificación no repite efectos.
- Un pago aprobado nunca se rechaza ni se ignora. Si llega para un pedido cancelado o reemplazado, o si el pedido ya tenía otro pago aprobado, el intento queda en `requiere_revision`, el pedido no cambia y se avisa al administrador. La devolución, si corresponde, se hace manualmente desde Mercado Pago.

---

# 38. Idempotencia

Las operaciones de creación/procesamiento de pagos deben usar idempotencia.

Objetivo:

- evitar cobros duplicados;
- tolerar doble click;
- tolerar reintentos;
- tolerar timeout/red de forma segura.

---

# 39. Datos sensibles

Nunca almacenar:

- CVV;
- número completo de tarjeta;
- secretos;
- tokens sensibles no necesarios;
- contraseñas sin hash.

Las credenciales deben vivir en variables de entorno.

---

# 40. Configuraciones globales

Se podrá utilizar una tabla genérica para configuración.

Modelo conceptual:

```text
configuraciones

id
clave
valor
tipo

created_at
updated_at
```

Ejemplos:

```text
ajuste_ecommerce
```

La configuración económica no debe quedar hardcodeada.

---

# 41. Auditoría

Debe existir auditoría para acciones sensibles.

Modelo conceptual:

```text
registros_auditoria

id
usuario_id
accion
entidad
entidad_id
datos_anteriores
datos_nuevos
ip
created_at
```

Ejemplos:

- cambio de precio;
- cambio masivo de precios;
- cambio del ajuste e-commerce;
- cambio de reglas de cuotas;
- modificación de pedido;
- habilitación de pago;
- carga/cambio del costo de envío;
- cancelación;
- cambios sensibles de producto.

Los registros de auditoría no deben poder editarse/eliminarse desde el panel.

---

# 42. Seguridad

Reglas generales:

- autenticación Laravel estándar;
- contraseñas hasheadas;
- CSRF;
- validaciones mediante Form Requests;
- rate limiting en login/registro/recuperación;
- middleware para rol administrador;
- HTTPS obligatorio en producción;
- secretos en `.env`;
- validación backend para disponibilidad/precios;
- verificación de webhooks;
- idempotencia de pagos;
- logs técnicos sin datos sensibles;
- backups automáticos de base de datos.

---

# 43. Emails y Jobs

Los emails y procesamiento de imágenes deben usar colas.

Objetivos:

- evitar bloquear requests;
- permitir reintentos;
- registrar fallos;
- mejorar estabilidad.

Los Jobs fallidos deben poder reintentarse.

---

# 44. Modelo de datos consolidado

Tablas principales previstas:

| Tabla | Responsabilidad |
|---|---|
| `usuarios` | Clientes y administradores |
| `categorias` | Categorías dinámicas + Ofertas |
| `categoria_producto` | Relación N:N |
| `productos` | Producto comercial |
| `variantes_producto` | Color, SKU, precio base, disponibilidad |
| `imagenes_producto` | Imágenes generales o por variante |
| `pedidos` | Pedido, cliente, envío, estados |
| `items_pedido` | Snapshot de productos |
| `pagos` | Intentos de pago |
| `reglas_precio_pago` | Porcentajes por modalidad/cuotas |
| `configuraciones` | Ajustes globales |
| `historial_actualizaciones_precios` | Cambios masivos |
| `registros_auditoria` | Auditoría administrativa |

---

# 45. Relaciones principales

```text
Usuario
  1
  │
  N
Pedido
  1
  │
  ├──────── N ItemPedido
  │
  └──────── N Pago


Categoria
  N
  │
  │ categoria_producto
  │
  N
Producto
  1
  │
  N
VarianteProducto
  1
  │
  N
ImagenProducto
```

---

# 46. Panel administrativo — alcance funcional

El diseño visual se trabajará con las skills y herramientas de diseño indicadas en la sección 2.

Funcionalmente el panel deberá permitir gestionar:

- productos;
- variantes;
- categorías;
- categoría Ofertas;
- imágenes;
- precios;
- actualizaciones masivas;
- pedidos;
- estados de contacto;
- datos de envío;
- costos de envío;
- pagos;
- reglas de pricing;
- ajuste e-commerce;
- usuarios;
- auditoría;
- contenido editable de la home y datos de contacto.

## Contenido editable de la home

El administrador puede modificar desde el panel, sin tocar código:

- **Portada:** una sola, sin carrusel. Tiene una imagen grande, un título, un subtítulo opcional y un botón opcional con texto y destino. Si no hay portada configurada, la home se muestra sin ella.
- **Beneficios:** una lista de frases cortas con ícono, por ejemplo "Envíos a todo el país" o "Coordinamos por WhatsApp". Se pueden agregar, editar, ordenar, activar y desactivar. El ícono se elige de un conjunto fijo definido por el diseño.
- **Categorías con foto:** la grilla de categorías de la home muestra las categorías activas marcadas como destacadas, en su orden, con su imagen. Una categoría sin imagen se muestra con un fondo neutro.
- **Datos de contacto:** WhatsApp del negocio (botón flotante "Hablá con nosotros"), email, dirección, horarios y redes sociales. Se guardan en `configuraciones`.

Las imágenes de la portada y de las categorías se procesan igual que las de productos: en cola, convertidas a WebP y sin conservar el original.

Los cambios de contenido quedan auditados.

---

# 47. Reglas críticas que Claude debe respetar

1. Arquitectura Controller → Form Request → Service → Model/Eloquent.
2. Código propio del dominio en español.
3. No introducir DDD/Repositories/Actions/Use Cases por defecto.
4. No hardcodear precios, porcentajes o IDs funcionales.
5. Todo producto tiene al menos una variante.
6. El precio vive en la variante.
7. El ajuste e-commerce es configurable.
8. Los porcentajes de cuotas son configurables.
9. El envío se coordina antes de habilitar el pago.
10. No usar WhatsApp API en V1.
11. El contacto por WhatsApp será mediante enlaces con mensajes prearmados.
12. El pedido se crea antes del pago.
13. Un pedido puede tener múltiples intentos de pago.
14. Solo una confirmación válida de Mercado Pago marca el pedido como pagado.
15. Una vez pagado, el pedido queda bloqueado.
16. Solo el administrador puede cancelar pedidos antes del pago.
17. No existe cancelación automática.
18. Los pedidos históricos conservan snapshots.
19. Las imágenes se almacenan en cloud.
20. Las imágenes se optimizan mediante Jobs y no se conservan originales pesados.
21. Email de pedido despachado es manual.
22. Acciones sensibles deben quedar auditadas.
23. No almacenar datos sensibles de tarjetas.
24. Las reglas de negocio deben validarse también en backend.
25. Si se necesita modificar algo después de generar la orden de pago, se cancela esa orden y se genera un pedido nuevo.
26. La V1 no gestiona cancelaciones ni devoluciones de pedidos pagados dentro de la página; esos casos se resuelven por WhatsApp.
27. El porcentaje de la modalidad/cuotas se compensa sobre el total del pedido, incluido el envío; productos y envío se cobran juntos.
28. El DNI es único por usuario. El email se verifica antes de confirmar el checkout y crear el pedido, sin bloquear el registro, el inicio de sesión ni el carrito.
29. El cliente puede modificar su DNI, email y teléfono; cambiar el email exige verificar la nueva dirección. Solo un administrador puede crear otras cuentas de administrador.

---

# 48. Prioridad sugerida de implementación

Las primeras fases de implementación serán **solo de backend**: modelo de datos, reglas de negocio, validaciones, autorización, rutas/controladores, integraciones y pruebas sobre MySQL. Las vistas Blade, el panel administrativo y el diseño de la tienda se implementarán en fases posteriores. Los endpoints administrativos podrán quedar preparados antes de construir sus pantallas.

Productos y variantes pueden implementarse primero como borradores inactivos. La gestión de categorías y la carga/procesamiento de imágenes deben estar disponibles antes de activar productos, porque la activación exige una categoría activa, una imagen general principal y al menos una variante activa con imagen.

| Prioridad | Módulo |
|---|---|
| Alta | Autenticación y roles |
| Alta | Productos y variantes |
| Alta | Categorías y Ofertas |
| Alta | Imágenes de producto y procesamiento en cola |
| Alta | Precios y ajuste e-commerce |
| Alta | Carrito |
| Alta | Checkout |
| Alta | Pedidos |
| Alta | Coordinación de envío |
| Alta | Mercado Pago |
| Alta | Reglas de cuotas |
| Alta | Webhooks e idempotencia |
| Media | Emails |
| Media | Actualizaciones masivas de precios |
| Media | Auditoría |
| Media | Historial de pedidos de cliente |
| Baja | Mejoras futuras de stock |
| Baja | WhatsApp Business API |

## Orden acordado para lo que falta

Ya están implementados: autenticación y roles, productos y variantes, categorías y Ofertas, imágenes, ajuste e-commerce y catálogo. El resto del backend se completa en este orden, antes de construir las pantallas:

1. Auditoría (primero, para que los módulos siguientes la usen; se agrega también a los módulos existentes).
2. Reglas de precio de pago y cálculo de la compensación.
3. Carrito.
4. Checkout, pedidos e historial del cliente.
5. Coordinación de envío y WhatsApp.
6. Mercado Pago, webhooks e idempotencia.
7. Edición y reemplazo de pedidos.
8. Emails.
9. Actualizaciones masivas de precios.
10. Contenido editable de la home y datos de contacto (sección 46).

La dirección visual se define en paralelo. Las pantallas Blade se construyen después, sobre el backend terminado.

---

# 49. Pendientes técnicos permitidos para Claude

Estas decisiones pueden resolverse durante implementación siempre que respeten este documento:

- estructura exacta de carpetas;
- nombres finales de algunos estados/enums;
- proveedor cloud S3-compatible;
- proveedor de email transaccional;
- implementación concreta de colas;
- formato exacto del historial de auditoría.

Ya resueltos: la integración con Mercado Pago es Checkout Bricks y el link de pago usa un token aleatorio sin login (sección 35).

Claude no debe modificar las reglas de negocio aquí definidas para resolver estos pendientes.

---

# 50. Fuera de alcance de V1

No implementar:

- stock numérico;
- reserva de stock;
- alertas de inventario;
- movimientos de inventario;
- atributos genéricos más allá de color;
- API oficial de WhatsApp;
- permisos granulares por empleado;
- SPA;
- React/Vue;
- cancelación automática de pedidos;
- pago en efectivo (Rapipago/Pago Fácil);
- facturación electrónica (ARCA/AFIP): el negocio factura con su sistema actual.

---

# 51. Resultado esperado

La V1 debe permitir que:

```text
cliente
→ crea cuenta
→ navega productos
→ selecciona variante
→ agrega al carrito
→ completa checkout
→ genera pedido

administrador
→ contacta por WhatsApp
→ coordina expreso
→ carga costo de envío
→ modifica pedido si hace falta
→ habilita pago
→ envía link de pago

cliente
→ elige modalidad de pago
→ sistema aplica pricing correspondiente
→ Mercado Pago procesa

backend
→ recibe webhook
→ verifica pago
→ bloquea pedido

administrador
→ prepara
→ despacha
→ envía email manual de despacho
→ marca entrega
```

Ese es el flujo funcional principal que Claude debe preservar.

---

# 52. Principio rector

El sistema debe ser:

- simple de mantener;
- claro para Claude;
- coherente con Laravel tradicional;
- fácil de operar por un comercio pequeño;
- seguro respecto de pagos;
- flexible respecto de precios;
- trazable respecto de cambios administrativos;
- preparado para crecer sin sobrearquitectura.
