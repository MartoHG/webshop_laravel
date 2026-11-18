# Estado · El Refugio

Este archivo se reescribe entero al cerrar cada sesión.

- **Etapa:** Desarrollo
- **Próximo paso:** confirmar con el usuario el boceto del panel «Bandeja de
  tareas» y construir el layout del panel (`x-layouts.panel`).

## Bloqueos

- El boceto del panel espera la aprobación del usuario.
- Las pruebas de Mercado Pago en sandbox esperan las credenciales de prueba
  del cliente y un túnel para el webhook (lo arma el desarrollador).
- La fase de producción espera la decisión del cliente sobre dominio,
  hosting y proveedor de emails (ver `.ai/entrega.md`).

## Hecho

- **Backend de la V1 completo:** los diez módulos de la sección 48 de
  `el-refugio-decisiones.md`, con 225 tests en verde al 2026-10-17. La tabla
  de módulos está en el `README.md`.
- **Tienda pública terminada en Blade (2026-10-11):** home, catálogo con
  búsqueda y filtros, producto con cambio de color por htmx, carrito con
  htmx, ingreso, registro, recuperar contraseña, mi cuenta, checkout, pedido
  recibido, mis pedidos y la página del link de pago con Checkout Bricks.
  Revisada en escritorio y a 375 px.
- **Base visual en código:** tokens en el `@theme` de Tailwind v4
  (`resources/css/app.css`), layout `x-layouts.tienda` y componentes en
  `resources/views/components` (etiqueta colgante, sello, botón, tarjeta de
  producto y franja de color).
- **Boceto del panel (2026-10-17):** cinco pantallas en la página «Panel»
  del lienzo y copia en `diseno/boceto/Panel*.dc.html`: bandeja de pedidos
  por etapa, detalle de pedido con el próximo paso, lista de productos,
  editor de producto y menú «Más». Contrato:
  `.impeccable/surfaces/resources-views-administracion-inicio-blade-php.md`.
  Lienzo original (privado, se abre con la cuenta del dueño):
  https://claude.ai/artifact/9c4rGkGfkjLkGMQb1ReAhS

## Lo que sigue

1. **Panel de administración.** Los controladores de `/administracion/*` ya
   existen y responden JSON: se les agrega la rama HTML con la misma
   convención de la tienda. Orden:
   - layout del panel (`x-layouts.panel`): barra superior grafito, pestañas
     fijas abajo (Pedidos · Productos · Precios · Más) y contador de pedidos
     pendientes;
   - bandeja de pedidos por etapa (contactar, cotizar, esperando pago,
     despachar) y aviso de pagos en revisión;
   - detalle del pedido con el próximo paso (contacto, cotizar envío,
     aceptación, habilitar pago, avanzar envío, links de WhatsApp), emails
     manuales, cancelar, reemplazar, editar ítems, corregir dirección y
     resolver pagos en revisión;
   - productos: lista con búsqueda y filtros, y editor (datos, categorías,
     fotos, colores con precio y stock, activar);
   - precios: ajuste, formas de pago y cuotas, cambio masivo con vista
     previa e historial;
   - contenido (portada, beneficios, categorías con foto, contacto y
     horarios), usuarios y registro de cambios (auditoría).
2. **Cierre de diseño:** pasada de `respawn-design` contra el catálogo de
   slop sobre la tienda y el panel, con las excepciones registradas en
   `.ai/decisiones.md`.
3. **Pruebas de Mercado Pago en sandbox:** verificar que el Brick ofrezca
   solo las cuotas elegidas (el backend igual rechaza otra cantidad), que
   envíe el DNI del pagador, los códigos de rechazo y el regreso del pago
   con dinero en cuenta.
4. **Producción:** hosting, almacenamiento S3, proveedor de emails,
   credenciales productivas de Mercado Pago, HTTPS, worker y cron, backups.
   Antes, revisar que la cuenta de Mercado Pago no le cobre interés al
   comprador por las cuotas (sección 35 de `el-refugio-decisiones.md`).
   - Recomendación de hosting dada al usuario (el cliente prioriza que sea
     barato): un VPS de 2 GB (Hetzner CX23 si hay disponibilidad, si no
     Vultr en São Paulo), fotos en Cloudflare R2 (gratis hasta 10 GB),
     Cloudflare gratis para DNS y HTTPS, y emails con el plan gratis de un
     proveedor transaccional.
   - Menor: `npm audit` marca `shell-quote` y `source-map-js`, herramientas
     de desarrollo que no llegan a producción. Se corrige con
     `npm audit fix` si el usuario lo aprueba.
5. **Auditoría de entrega** con `review-respawn`.

## Última sesión

2026-11-18: se inicializó El Refugio como proyecto Respawn. Se copiaron las
skills de la casa, la memoria de `PRODUCT.md` y `PROXIMOS-PASOS.md` pasó a
`.ai/` y el `CLAUDE.md` se integró con la plantilla.

## Para la casa

- La casa todavía no tiene una versión numerada en su `CHANGELOG.md`, así
  que el proyecto quedó en la versión «sin publicar» · `respawn-init` ·
  publicar una primera versión (por ejemplo, 0.1.0) para que una
  actualización pueda saber qué entradas cambiaron.
- El script copia `respawn-skill-creator` a los proyectos, aunque su
  descripción dice que no se usa en un proyecto de cliente · `respawn-init`
  (`scripts/init.sh`) · saltear esa skill al copiar, como ya se hace con
  `respawn-init`.
