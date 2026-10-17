---
version: 1
slug: "resources-views-administracion-inicio-blade-php"
primary_target: "resources/views/administracion/inicio.blade.php"
related_targets: []
---

## Alcance

Panel de administración de El Refugio. Modo: Operate. Se usa sobre todo desde el celular; pocos pedidos por día (hasta 5), cada uno se mira con calma.

## Público y tarea

El dueño o quien atiende: coordina pedidos por WhatsApp, carga costos de envío, habilita pagos, despacha; además edita productos, precios y el contenido de la home. No es técnico.

## Restricciones

Mismo mundo visual que la tienda (La etiqueta), en registro sobrio. Acciones grandes, textos llanos, nada de tablas anchas en el celular. Toda acción sensible queda auditada (lo hace el backend).

## Direction contract

THESIS: El panel es una bandeja: los pedidos se agrupan por lo que les falta y cada uno muestra un solo próximo paso. Rechaza el panel genérico de tablas con barra lateral.
OWN-WORLD: La etiqueta en registro sobrio: blanco y niebla, grafito; etapas codificadas con las tintas: naranja contactar, celeste cotizar, amarillo esperando pago, grafito despachar; Archivo condensada solo en títulos y contadores.
STORY: Abre en el celular, ve cuántos pedidos esperan en cada etapa, toca una y resuelve el próximo paso de cada pedido sin perderse.
FIRST VIEWPORT: Barra grafito «Panel»; cuatro etiquetas de etapa en grilla 2×2 con contador grande; debajo la lista de la etapa elegida (cliente, número, total, botón de próxima acción); pestañas fijas abajo.
FORM: Bandeja de tareas, candidato 1 de 6, semilla 31f12dfb.
FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance

## Pendiente

Boceto de pedidos, detalle de pedido, productos, editor de producto y menú «Más»; después las vistas Blade.
