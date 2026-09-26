# El Refugio Verano Edition — Especificación final de diseño del sistema

## 1. Propósito del documento

Este documento consolida las decisiones funcionales, técnicas y de arquitectura definidas para **El Refugio Verano Edition**.

Su objetivo es servir como **documento rector para Codex**, que será quien implemente el sistema.

La intención es que Codex pueda desarrollar sobre una base clara y consistente, respetando las decisiones ya tomadas y evitando introducir patrones, capas o tecnologías que contradigan este diseño.

---

# 2. Objetivo general

Construir un **webshop/ecommerce** para una forrajería, presentado inicialmente como **“El Refugio Verano Edition”** o nombre similar.

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

Las decisiones visuales y de UX serán definidas aparte por **DesignerGPT**.

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

La estructura exacta de carpetas será definida por Codex respetando esta arquitectura.

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
- asignarse a múltiples productos.

No se puede desactivar una categoría si con ello algún producto activo quedaría sin ninguna categoría activa.

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

- Cantidad mínima: 1.
- Revalidar precio y disponibilidad antes de confirmar checkout.
- El carrito no es fuente definitiva de precio.
- Si una variante deja de estar disponible, no se elimina silenciosamente.
- Debe mostrarse como agotada/no disponible y bloquear la continuación hasta resolverlo.

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

## Envío

```text
pendiente_coordinacion
cotizado
aceptado
preparado
despachado
entregado
```

Codex puede ajustar nombres exactos manteniendo el significado y utilizando español.

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

Cliente acepta
→ envío = aceptado

Administrador habilita pago
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

Si después de generar la orden de pago hay que modificar productos, cantidades, precios o envío, se cancela esa orden de pago y se genera un pedido nuevo. No se modifica el pedido vinculado a una orden de pago vigente.

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

---

# 33. Datos del pedido

Modelo conceptual:

```text
pedidos

id
numero
usuario_id

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

La integración exacta de Checkout Pro vs Checkout API/Bricks deberá resolverse durante la implementación técnica, priorizando la alternativa que permita conocer/controlar modalidad de pago y cuotas antes de cerrar el importe.

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

El diseño visual queda delegado a DesignerGPT.

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
- auditoría.

---

# 47. Reglas críticas que Codex debe respetar

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

---

# 49. Pendientes técnicos permitidos para Codex

Estas decisiones pueden resolverse durante implementación siempre que respeten este documento:

- estructura exacta de carpetas;
- nombres finales de algunos estados/enums;
- proveedor cloud S3-compatible;
- proveedor de email transaccional;
- implementación concreta de colas;
- decisión técnica final entre Checkout API / Bricks / modalidad equivalente de Mercado Pago;
- estrategia exacta de generación de URL segura de pago;
- formato exacto del historial de auditoría.

Codex no debe modificar las reglas de negocio aquí definidas para resolver estos pendientes.

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
- cancelación automática de pedidos.

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

Ese es el flujo funcional principal que Codex debe preservar.

---

# 52. Principio rector

El sistema debe ser:

- simple de mantener;
- claro para Codex;
- coherente con Laravel tradicional;
- fácil de operar por un comercio pequeño;
- seguro respecto de pagos;
- flexible respecto de precios;
- trazable respecto de cambios administrativos;
- preparado para crecer sin sobrearquitectura.
