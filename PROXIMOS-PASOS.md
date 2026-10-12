# Próximos pasos

Nota de traspaso para retomar desde cualquier máquina. Última actualización: 11/10/2026.

## Dónde quedó

- **Backend de la V1 completo:** los diez módulos de la sección 48 de [el-refugio-decisiones.md](el-refugio-decisiones.md) están implementados, con 225 tests en verde, y commiteados en `main`. Todo responde JSON. El [README](README.md) tiene la tabla de módulos.
- **Diseño aprobado: «La etiqueta».**
  - Boceto de la home (escritorio y celular) y del flujo de compra (producto, carrito, checkout, pedido recibido, link de pago) en [diseno/boceto/](diseno/boceto/LEEME.md).
  - Lienzo original: https://claude.ai/artifact/9c4rGkGfkjLkGMQb1ReAhS (privado, se abre con la cuenta del dueño).
  - Contrato de dirección: `.impeccable/surfaces/resources-views-tienda-inicio-blade-php.md`.
  - Contexto del producto para el diseño: [PRODUCT.md](PRODUCT.md).
- **Tienda pública terminada en Blade (11/10/2026):** home, catálogo con búsqueda y filtros, producto con cambio de color por htmx, carrito con htmx, ingreso, registro, recuperar contraseña, mi cuenta, checkout, pedido recibido, mis pedidos con estados en lenguaje simple y la página del link de pago con Checkout Bricks. Revisada en escritorio y a 375 px.
- **Convención:** cada ruta responde HTML o JSON desde el mismo controlador (sección 4 del documento de decisiones). Los componentes están en `resources/views/components` y el layout en `x-layouts.tienda`.
- **Datos de ejemplo:** `php artisan db:seed --class=DemoSeeder` (solo local) carga productos, categorías, portada y dos usuarios de prueba (ver el seeder). Las fotos de ejemplo se leen de `storage/app/demo/`, que no se versiona.
- **Todavía sin pantallas:** el panel de administración (el backend está completo).

## Cómo se trabaja

- Una sesión de Claude orquesta y los subagentes Haiku implementan, de a un módulo por vez, porque comparten la base de tests.
- El orquestador escribe cada encargo, revisa el diff, corre la suite completa y commitea.
- Los commits van directo a `main`, con mensajes en infinitivo y en español. Push solo cuando lo pide el usuario.
- El diseño lo hace el orquestador con las skills `impeccable`, `emil-design-eng` y `design-taste-frontend` y con la herramienta de diseño de Claude. Haiku implementa las vistas siguiendo el boceto.
- Las reglas de negocio están en `el-refugio-decisiones.md`. Cualquier decisión nueva se anota ahí antes de implementarla.

## Fase 3: pantallas

1. ✅ **Base visual en código** (orquestador):
   - Tokens en el `@theme` de Tailwind v4 (`resources/css/app.css`): paleta blanco, grafito #26282B, naranja #F2601F, celeste #19A7CE y amarillo #FFC20E; fuente Archivo.
   - htmx y layout Blade.
   - Componentes: etiqueta colgante, sello, botón, tarjeta de producto y franja de color.
2. ✅ **Tienda:**
   - Pantallas: home (`/`), categoría, producto, búsqueda, carrito con htmx, checkout, pedido recibido, cuenta y pedidos, login y registro, link de pago con el Brick de Mercado Pago.
   - Los controladores pasan de JSON a vistas; los servicios no cambian y los tests se adaptan.
3. **Panel de administración (lo que sigue):** primero el boceto, más sobrio y pensado para trabajar; después sus vistas.
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
