# Brief · El Refugio

## El negocio

- **Qué es:** una forrajería con local en Olavarría que también vende
  alimento para mascotas y artículos de bazar. La tienda online vende solo
  muebles de jardín, sobre todo de plástico de buena calidad: sillas,
  sillones, mesas, juegos de living y reposeras. Gama media: productos lindos
  y accesibles, no de lujo. Todo es oferta de verano.
- **Dónde:** local en Pringles 2136, Olavarría. La tienda vende a todo el
  país, con envío por expreso.
- **Qué lo distingue:** es un comercio real del interior, con local físico y
  trato directo. Cada pedido se conversa por WhatsApp antes de cobrar, el
  cliente no paga nada hasta conocer el total con envío y puede retirar en el
  local de Olavarría.
- **Cómo consigue clientes hoy:** Instagram (@elrefugio.olav, «Forrajería El
  Refugio»), Facebook y WhatsApp. En Instagram publica carteles de oferta
  (texto blanco en cajas negras, precios grandes) y venta por cantidad
  («x 4 unidades»).

## El visitante

- **Quién entra a la web:** compradores de todo el país, de distintas edades
  y muchos con poca experiencia comprando online. Entran desde el celular (a
  menudo por un link de Instagram, Facebook o WhatsApp) y desde la
  computadora. Los clientes de Olavarría y la zona pueden preferir retirar en
  el local.
- **Qué quiere saber:** qué hay, cuánto cuesta y cómo seguir. Busca muebles
  de jardín para el verano.
- **Qué lo frena:** los pasos de más y los textos que no entiende: tiene poca
  experiencia comprando online.

## La acción objetivo

- **Qué tiene que hacer el visitante:** crear un pedido sin pagar todavía.
  El negocio coordina el envío por WhatsApp, carga el costo y recién entonces
  habilita el pago con Mercado Pago.
- **Canal:** carrito y checkout con cuenta y email verificado; después,
  WhatsApp del negocio ([FALTA: número de WhatsApp y horarios del negocio]) y
  el link de pago de Mercado Pago, que no pide login. El retiro en el local
  se pide con un botón del carrito que abre WhatsApp con el pedido prearmado
  y no crea pedido en el sistema.

## Material real

| Material               | Hay | Detalle                                                                                   |
| ---------------------- | --- | ----------------------------------------------------------------------------------------- |
| Logo                   | No  | [FALTA: logo]                                                                             |
| Fotos propias          | Sí  | Fotos de celular de Instagram, sin producción: productos en el local (piso de cemento, cajas Mascardi), en el pasto o junto a una pileta. Para la tienda: [FALTA: fotos reales de los productos] |
| Textos                 | No  | [FALTA: textos finales de portada y beneficios]                                           |
| Productos o servicios  | Sí  | Sillas y sillones de plástico Mascardi (Kansas, Aruba, sillón Máster, Gran California tipo Adirondack), sillones símil ratán en grafito, juegos de living de jardín y reposeras de plástico rotomoldeado en blanco y en colores (naranja, verde, amarillo). Sin precios relevados |
| Redes y reseñas reales | Sí  | Instagram @elrefugio.olav. No hay testimonios, cifras de ventas, cantidad de productos, plazos de entrega ni garantías confirmadas: no inventarlos |

Lo que falta se anota en `.ai/pendientes-cliente.md`.

## Quién actualiza el contenido

El administrador (el dueño o un empleado de El Refugio), desde el panel y sin
tocar código. No es un perfil técnico. El dueño usa el panel desde el
celular y tiene pocos pedidos por día.

| Qué cambia                                         | Quién lo cambia | Cada cuánto  | Desde dónde |
| -------------------------------------------------- | --------------- | ------------ | ----------- |
| Precios, también en masa                           | Administrador   | Seguido      | Panel, celular |
| Productos, colores, fotos y categorías             | Administrador   | Seguido      | Panel, celular |
| Portada, beneficios, contacto y horarios           | Administrador   | Seguido      | Panel, celular |
| Pedidos: contacto, envío y pago por WhatsApp       | Administrador   | Pocos por día | Panel, celular |

## Paquete y alcance

- **Paquete:** sin encuadrar todavía en un paquete de la casa. Se hace con
  `respawn-kickoff`.
- **Incluye:** la tienda pública (home, catálogo con búsqueda y filtros,
  producto, carrito, checkout, cuenta y pedidos, link de pago) y el panel de
  administración con el alcance de la sección 46 de
  `el-refugio-decisiones.md`: productos, variantes, categorías y Ofertas,
  imágenes, precios y actualizaciones masivas, pedidos, envíos, pagos,
  reglas de cuotas, usuarios, auditoría y contenido de la home.
- **No incluye:** lo que lista la sección 50 de `el-refugio-decisiones.md`:
  stock numérico y su reserva, alertas y movimientos de inventario,
  atributos más allá del color, API oficial de WhatsApp, permisos por
  empleado, SPA, React o Vue, cancelación automática de pedidos, pago en
  efectivo y facturación electrónica.
- **Dominio y hosting:** aparte del paquete. Sin decidir: ver
  `.ai/entrega.md`.
- **Mantenimiento:** sin relevar.
- **Rondas de cambios:** sin relevar.
- **Obligaciones:** cobra en línea con Mercado Pago y guarda datos de
  personas (cuentas de clientes y direcciones de envío). Sin suscripciones.

## Plazos

- **Entrega acordada:** sin relevar.
- **Depende de:** el material y los accesos de `.ai/pendientes-cliente.md`.

## Estimación

Sin estimación registrada: el backend de la V1 ya está construido.

## Marca

- **Nombre:** El Refugio (no «El Refugio Verano Edition»).
- **Referencia que le gusta al dueño:** tolder.com.ar/muebles-de-jardin. Es
  una referencia de estilo, no una marca a copiar.
- **Lo primero que tiene que transmitir la home:** que hay cosas lindas para
  disfrutar el verano.
- **Lo que no debe ser:** lujoso o frío (que intimide), ni una plantilla
  genérica de tienda online.
- La web tiene que verse bien con las fotos de celular que carga el negocio,
  no solo con fotos de estudio.

## Principios del producto

1. **Nadie se pierde:** cada pantalla tiene una acción principal obvia y
   textos en lenguaje llano.
2. **Sin sorpresas con la plata:** precios claros, agotados visibles, y
   nunca se cobra antes de acordar el total con envío.
3. **WhatsApp como puente humano:** la web lleva al cliente a hablar con el
   negocio cuando hace falta (coordinar envío, retiro en el local, dudas).
4. **El dueño opera solo:** todo lo que cambia seguido se edita desde el
   panel, sin código.

## Accesibilidad

- Público con poca experiencia digital y de distintas edades: letra cómoda,
  alto contraste, áreas táctiles grandes, etiquetas visibles en los
  formularios y mensajes de error que digan cómo resolver el problema.
- Uso parejo entre celular y computadora: todo debe funcionar bien en ambos.
- Objetivo: WCAG 2.2 AA.

Las reglas de negocio completas (variantes, disponibilidad, precios, cuotas,
envíos, pedidos y pagos) están en `el-refugio-decisiones.md`.
