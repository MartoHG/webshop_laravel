# Decisiones · El Refugio

Lo que se decidió y por qué. Cada parte dice lo vigente: una decisión que
cambia reescribe su fila.

Las reglas de negocio y de arquitectura (productos, precios, pedidos, pagos,
envíos) están en `el-refugio-decisiones.md`. Este archivo guarda el concepto
y las decisiones de diseño, contenido y alcance.

## Concepto

**«La etiqueta»** (boceto aprobado el 2026-10-10): cada mueble llega con su
etiqueta. La tienda habla en etiquetas colgantes, precios grandes y sellos, y
rechaza la foto con texto blanco encima y la grilla de cards genérica. Sale
de cómo vende hoy el negocio: sus carteles de oferta en Instagram ponen el
precio grande y en primer plano, y su público, con poca experiencia
comprando online, necesita ver rápido qué hay y cuánto cuesta. El panel lleva
la misma idea en un registro sobrio: es una bandeja en la que cada pedido
muestra un solo próximo paso.

Referencias: bocetos en `diseno/boceto/` (ver `diseno/boceto/LEEME.md`) y
contratos de dirección en `.impeccable/surfaces/`.

## Excepciones al catálogo de slop

Un patrón del catálogo solo se usa si el concepto lo justifica. Sin fila acá,
el patrón no va.

Todavía no se pasó el catálogo de `respawn-design` sobre la tienda ni sobre
el panel. Las excepciones se registran en esa pasada.

## Registro

Temas: concepto, tipografía, paleta, espaciado, imágenes, movimiento, voz,
contenido, alcance, técnica, negocio.

| Fecha      | Tema       | Decisión | Por qué |
| ---------- | ---------- | -------- | ------- |
| 2026-10-10 | concepto   | Concepto «La etiqueta» para la tienda: etiquetas troqueladas con ojal y esquinas cortadas, sellos redondos para estados y precios grandes | Los carteles de oferta del negocio ponen el precio grande, y el visitante necesita entender rápido qué hay y cuánto cuesta |
| 2026-10-10 | paleta     | Blanco, grafito #26282B y tintas planas naranja #F2601F, celeste #19A7CE y amarillo #FFC20E, sin degradés | Son los colores de los productos reales (reposeras naranja y amarillas, sillones símil ratán grafito) y lo opuesto a lo lujoso o frío que el dueño no quiere |
| 2026-10-10 | tipografía | Archivo: condensada negra para precios y títulos, normal para el texto | El precio es el protagonista de la etiqueta, como en los carteles de oferta del negocio |
| 2026-10-10 | imágenes   | Foto de verano a sangre en la portada, sin carrusel y sin texto blanco sobre la foto; las vistas tienen que verse bien con fotos de celular | El negocio carga fotos de celular sin producción, y la home tiene que transmitir primero que hay cosas lindas para disfrutar el verano |
| 2026-10-10 | movimiento | La etiqueta de la portada entra con un balanceo breve de péndulo y las de producto se balancean al pasar el mouse; todo queda estático con movimiento reducido | Es el gesto de una etiqueta colgante, del concepto |
| 2026-10-11 | voz        | Los estados de los pedidos se muestran al cliente en lenguaje simple | El público tiene poca experiencia comprando online |
| 2026-10-17 | alcance    | Estructura del panel «Bandeja de tareas», pensada para celular: pedidos agrupados por etapa con contador (contactar, cotizar, esperando pago, despachar), cada uno con su próximo paso, y pestañas fijas abajo | El dueño usa el panel desde el teléfono y tiene pocos pedidos por día |
| 2026-10-17 | paleta     | En el panel las etapas se codifican con las tintas: naranja contactar, celeste cotizar, amarillo esperando pago, grafito despachar; Archivo condensada solo en títulos y contadores | El panel lleva el concepto en registro sobrio: el dueño tiene que ver de un vistazo cuántos pedidos esperan en cada etapa |
| 2026-11-18 | voz        | Con el pedido ya pagado, la página de pago se titula «Recibimos tu pago». Si la preferencia de dinero en cuenta venció, un aviso lo explica: «Pasaron más de 30 minutos y el pago con tu cuenta de Mercado Pago venció. Elegí de nuevo cómo querés pagar.» | El público tiene poca experiencia comprando online: tiene que entender que el pago entró, o por qué tiene que elegir de nuevo |
