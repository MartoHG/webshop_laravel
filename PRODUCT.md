# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- **Clientes:** compradores de todo el país, muchos con poca experiencia comprando online. Entran tanto desde el celular (a menudo desde un link de Instagram, Facebook o WhatsApp) como desde la computadora. Buscan muebles de jardín para el verano: sillas, sillones, mesas, juegos de living y reposeras. Necesitan entender rápido qué hay, cuánto cuesta y cómo seguir, sin pasos de más.
- **Clientes de Olavarría y la zona:** pueden preferir retirar en el local; lo coordinan por WhatsApp.
- **Administrador (dueño o empleado de El Refugio):** carga productos, fotos y precios; actualiza precios en masa; coordina envíos y pagos por WhatsApp desde el panel; edita el contenido de la home. No es un perfil técnico.

## Product Purpose

Tienda online de **El Refugio**, una forrajería con local en Olavarría, para vender a todo el país. El cliente arma el carrito y genera un pedido sin pagar todavía; el negocio coordina el envío por expreso por WhatsApp, carga el costo y recién entonces habilita el pago con Mercado Pago. El éxito es que alguien poco acostumbrado a comprar online llegue a crear un pedido sin trabarse, y que el dueño pueda operar la tienda solo desde el panel.

## Positioning

Un comercio real del interior con local físico y trato directo: cada pedido se conversa por WhatsApp antes de cobrar, el cliente no paga nada hasta conocer el costo total con envío, y puede retirar en el local de Olavarría.

## Operating Context

- Flujo de compra: catálogo → variante (color) → carrito → checkout con cuenta y email verificado → pedido creado → coordinación por WhatsApp → link de pago (Mercado Pago, sin login) → despacho por expreso.
- Retiro en el local: botón en el carrito que abre WhatsApp con el pedido prearmado; no crea pedido en el sistema.
- El panel de administración es la herramienta diaria del dueño: productos, variantes, imágenes, categorías, precios y reglas de cuotas, pedidos con botones de WhatsApp, usuarios, auditoría, y contenido de la home (portada, beneficios, categorías destacadas con foto, datos de contacto).
- Reglas de negocio completas y vinculantes: `el-refugio-decisiones.md`.

## Capabilities and Constraints

- Stack existente: Laravel 13, Blade, Tailwind CSS v4 y HTMX para interactividad puntual. Sin SPA, React ni Vue.
- Rubro único: muebles de jardín (sobre todo de plástico, de buena calidad): sillas, sillones, mesas, juegos de living y reposeras. Sin kayaks ni pesca. Todo es oferta de verano. Gama media: productos lindos y accesibles, no de lujo. Las categorías son personalizables; existe la categoría especial "Ofertas".
- La única variante de producto es el color (texto libre). Disponibilidad manual: un producto o variante puede estar visible pero agotado.
- Precios en pesos argentinos. El precio con cuotas se calcula en el link de pago, no en el catálogo.
- Medios de pago: tarjeta de crédito en cuotas, débito y dinero en cuenta de Mercado Pago. Sin efectivo ni facturación electrónica en la V1.
- Envío a todo el país por expreso, con costo a coordinar.
- Abierto: dominio, hosting y proveedor de emails.

## Brand Commitments

- Nombre: **El Refugio** (no "El Refugio Verano Edition").
- No hay logo, paleta ni tipografía definidos todavía.
- Referencia que le gusta al dueño: tolder.com.ar/muebles-de-jardin. Es una referencia de estilo, no una marca a copiar.
- Lo primero que tiene que transmitir la home: que hay cosas lindas para disfrutar el verano.
- Lo que no debe ser: lujoso o frío (que intimide), ni una plantilla genérica de tienda online.

## Evidence on Hand

- No hay logo todavía. El negocio va a cargar sus propias imágenes desde el panel; en el diseño se usan fotos de ejemplo, claramente provisorias.
- Fotos reales que el negocio publica en Instagram (@elrefugio.olav, "Forrajería El Refugio"): productos sacados en el local (piso de cemento, cajas Mascardi), en el pasto o junto a una pileta. Son fotos de celular, sin producción. La web tiene que verse bien con fotos así, no solo con fotos de estudio.
- Productos reales vistos: sillas y sillones de plástico Mascardi (Kansas, Aruba, sillón Máster, Gran California tipo Adirondack), sillones símil ratán en grafito, juegos de living de jardín y reposeras de plástico rotomoldeado en blanco y en colores (naranja, verde, amarillo).
- Hoy comunican en Instagram con carteles de oferta (texto blanco en cajas negras, precios grandes) y venta por cantidad ("x 4 unidades").
- Local: Pringles 2136, Olavarría. El local también vende alimento para mascotas y artículos de bazar, que no son foco de la tienda online.
- No hay testimonios, cifras de ventas, cantidad de productos, plazos de entrega ni garantías confirmadas: no inventarlos.

## Product Principles

1. **Nadie se pierde:** cada pantalla tiene una acción principal obvia y textos en lenguaje llano.
2. **Sin sorpresas con la plata:** precios claros, agotados visibles, y nunca se cobra antes de acordar el total con envío.
3. **WhatsApp como puente humano:** la web lleva al cliente a hablar con el negocio cuando hace falta (coordinar envío, retiro en el local, dudas).
4. **El dueño opera solo:** todo lo que cambia seguido (precios, fotos, portada, contacto) se edita desde el panel, sin código.

## Accessibility & Inclusion

- Público con poca experiencia digital y de distintas edades: letra cómoda, alto contraste, áreas táctiles grandes, etiquetas visibles en los formularios y mensajes de error que digan cómo resolver el problema.
- Uso parejo entre celular y computadora: todo debe funcionar bien en ambos.
- Objetivo: WCAG 2.2 AA.
