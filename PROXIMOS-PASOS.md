# Próximos pasos

Nota de traspaso escrita al cerrar la sesión del 10/10/2026, para retomar desde cualquier máquina.

## Dónde quedó

- **Backend de la V1 casi completo:** los módulos 1 a 9 de la sección 48 de [el-refugio-decisiones.md](el-refugio-decisiones.md) están implementados, con tests en verde y commiteados en `main`. Del módulo 10 (contenido de la home) están la portada y las fotos de categorías.
- **En curso al cerrar: M9 parte B.** Faltan beneficios editables (máximo 6 activos), datos de contacto con horarios por día y el cálculo de "abierto ahora", `GET /tienda/inicio` con los datos de la home y `GET /carrito/retiro-whatsapp`. Si no aparece un commit "Agregar beneficios, contacto, datos de la home y retiro en el local", es lo primero que hay que retomar. El encargo detallado está en la sección 46 del documento de decisiones y en la subsección "Retiro en el local" de la sección 24. Todo responde JSON. El [README](README.md) tiene la tabla de módulos.
- **Diseño aprobado: «La etiqueta».**
  - Boceto de la home (escritorio y celular) y del flujo de compra (producto, carrito, checkout, pedido recibido, link de pago) en [diseno/boceto/](diseno/boceto/LEEME.md).
  - Lienzo original: https://claude.ai/artifact/9c4rGkGfkjLkGMQb1ReAhS (privado, se abre con la cuenta del dueño).
  - Contrato de dirección: `.impeccable/surfaces/resources-views-tienda-inicio-blade-php.md`.
  - Contexto del producto para el diseño: [PRODUCT.md](PRODUCT.md).
- **Todavía no hay vistas Blade.**

## Cómo se trabaja

- Una sesión de Claude orquesta y los subagentes Haiku implementan, de a un módulo por vez, porque comparten la base de tests.
- El orquestador escribe cada encargo, revisa el diff, corre la suite completa y commitea.
- Los commits van directo a `main`, con mensajes en infinitivo y en español. Push solo cuando lo pide el usuario.
- El diseño lo hace el orquestador con las skills `impeccable`, `emil-design-eng` y `design-taste-frontend` y con la herramienta de diseño de Claude. Haiku implementa las vistas siguiendo el boceto.
- Las reglas de negocio están en `el-refugio-decisiones.md`. Cualquier decisión nueva se anota ahí antes de implementarla.

## Fase 3: pantallas (después de cerrar M9)

1. **Base visual en código** (orquestador):
   - Tokens en el `@theme` de Tailwind v4 (`resources/css/app.css`): paleta blanco, grafito #26282B, naranja #F2601F, celeste #19A7CE y amarillo #FFC20E; fuente Archivo.
   - htmx y layout Blade.
   - Componentes: etiqueta colgante, sello, botón, tarjeta de producto y franja de color.
2. **Tienda:**
   - Pantallas: home (`/`), categoría, producto, búsqueda, carrito con htmx, checkout, pedido recibido, cuenta y pedidos, login y registro, link de pago con el Brick de Mercado Pago.
   - Los controladores pasan de JSON a vistas; los servicios no cambian y los tests se adaptan.
3. **Panel de administración:** primero el boceto, más sobrio y pensado para trabajar; después sus vistas.
4. **Cierre de diseño:** revisión final de impeccable y `DESIGN.md` generado por el documentador.
5. **Fase 4, producción:** hosting, almacenamiento S3, proveedor de emails, credenciales productivas de Mercado Pago, HTTPS, worker y cron, backups. Antes, revisar que la cuenta de Mercado Pago no le cobre interés al comprador por las cuotas (sección 35).

## Pendiente del negocio

- Credenciales de prueba de Mercado Pago y un túnel para probar el webhook en sandbox.
- Logo y fotos reales de los productos.
- Número de WhatsApp del negocio y horarios. Se cargan desde el panel; el código no los trae.

## Si seguís desde otra máquina

1. Cloná el repo y seguí la puesta en marcha del [README](README.md): MySQL con las bases `el_refugio_local` y `el_refugio_test`, `.env` a partir de `.env.example`, `composer run setup` y `php artisan db:seed`.
2. Verificá que todo esté en verde con `php artisan test --compact`.
3. Si vas a usar Claude Code, instalá en esa máquina las skills de diseño (`impeccable`, `emil-design-eng`, `design-taste-frontend`) en `~/.claude/skills/`. La memoria de la sesión anterior no viaja: este archivo la reemplaza.
