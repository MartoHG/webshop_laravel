# Proyecto Respawn · El Refugio

Skills de la casa: versión sin publicar

Tienda online de El Refugio, una forrajería con local en Olavarría, para
vender muebles de jardín a todo el país. Incluye el panel con el que el dueño
opera la tienda desde el celular.

## Proyecto

- **Cliente:** El Refugio, forrajería, Olavarría (provincia de Buenos Aires)
- **Paquete:** sin encuadrar todavía; se hace con `respawn-kickoff`
- **Stack:** Laravel 13, Blade, Tailwind CSS v4, HTMX y MySQL, con tests en
  PHPUnit, registrado en `.ai/adrs/ADR-P-001-stack.md`
- **Acción objetivo:** crear un pedido sin pagar, que se coordina por
  WhatsApp y se paga con el link de Mercado Pago
- **Concepto:** «La etiqueta»; el detalle está en `.ai/decisiones.md`

## Skills que aplican

| Skill              | Cuándo                                                          |
| ------------------ | --------------------------------------------------------------- |
| `respawn-proyecto` | Registrar o consultar decisiones, deuda, pendientes y estado    |
| `respawn-kickoff`  | Relevar al cliente, encuadrar el paquete y fijar el alcance     |
| `respawn-design`   | Diseñar o criticar pantallas y escribir textos                  |
| `respawn-web`      | Accesibilidad, rendimiento, SEO y links de contacto             |
| `respawn-sistemas` | Tienda, panel de administración, Mercado Pago y operación       |
| `review-respawn`   | Auditar antes de entregar                                       |

Las skills de `.claude/skills/` son una copia de la casa: no se editan acá.

## Memoria del proyecto

| Archivo                      | Qué guarda                                                        |
| ---------------------------- | ----------------------------------------------------------------- |
| `el-refugio-decisiones.md`   | Reglas de negocio y de arquitectura: rigen el proyecto            |
| `.ai/brief.md`               | Cliente, visitante, acción objetivo, material, alcance y marca    |
| `.ai/decisiones.md`          | Concepto, excepciones al catálogo de slop y decisiones de diseño  |
| `.ai/estado.md`              | Etapa, próximo paso, bloqueos y lo que sigue                      |
| `.ai/entrega.md`             | Dominio, hosting, backups, accesos y manual                       |
| `.ai/adrs/`                  | Decisiones difíciles de revertir                                  |
| `.ai/pendientes-cliente.md`  | Lo que falta que el cliente entregue                              |
| `.ai/deuda-tecnica.md`       | Desvíos a corregir, si los hay                                    |
| `.ai/riesgos-aceptados.md`   | Desvíos conservados a propósito, si los hay                       |
| `.ai/problemas-conocidos.md` | Trampas del entorno, si las hay                                   |
| `.ai/auditoria.md`           | Último informe de auditoría, si ya se auditó                      |
| `.ai/auditoria-cliente.md`   | Informe para el cliente, si el proyecto tiene el sello            |
| `diseno/boceto/`             | Bocetos aprobados de la tienda y del panel                        |
| `.impeccable/surfaces/`      | Contratos de dirección de la home y del panel                     |

## Cómo trabajar

- Al empezar una sesión, leer `.ai/estado.md` y `.ai/pendientes-cliente.md`
  e informar etapa, próximo paso y bloqueos.
- Una decisión nueva de negocio o de arquitectura se anota en
  `el-refugio-decisiones.md` antes de implementarla; una de diseño,
  contenido o alcance, en `.ai/decisiones.md`.
- Antes de implementar, acordar el diseño con el usuario.
- Ante un pedido nuevo del cliente, comprobar con `respawn-kickoff` si entra
  en el alcance del brief antes de hacerlo.
- Pantalla por pantalla: terminar una, mostrarla y esperar la revisión antes
  de seguir.
- Nunca inventar precios, reseñas, cifras, clientes ni testimonios. Lo que
  falta queda como `[FALTA: …]`.
- El diseño sigue `respawn-design`. `impeccable`, `emil-design-eng` y
  `design-taste-frontend` quedan como consulta técnica (ADR-012 de la casa),
  aunque las secciones 2 y 46 de `el-refugio-decisiones.md` las nombren.
- Una sesión de Claude orquesta y los subagentes Haiku implementan, de a un
  módulo por vez, porque comparten la base de tests. El orquestador escribe
  cada encargo, revisa el diff y corre la suite completa. Haiku implementa
  las vistas siguiendo el boceto.
- Sin commit ni subida de cambios sin pedido del usuario. Los commits van
  directo a `main`, con mensajes en infinitivo y en español.

## Comandos

- **Preparar:** MySQL con las bases `el_refugio_local` y `el_refugio_test`,
  `.env` a partir de `.env.example`, `composer run setup` y
  `php artisan db:seed`. El detalle está en el `README.md`.
- **Datos de ejemplo (solo local):** `php artisan db:seed --class=DemoSeeder`
  carga productos, categorías, portada y dos usuarios de prueba (ver el
  seeder). Las fotos de ejemplo se leen de `storage/app/demo/`, que no se
  versiona.
- **Primer administrador:** `php artisan usuario:crear-administrador`.
- **Levantar en local:** `composer run dev`, con `php artisan queue:work` y
  `php artisan schedule:work` en otras terminales.
- **Compilar:** `npm run build`.
- **Probar:** `php artisan test --compact`.
- **Publicar:** sin definir hasta que haya hosting (ver `.ai/entrega.md`).

En otra máquina: clonar el repositorio, seguir la preparación y verificar que
la suite esté en verde. Las skills de la casa vienen en `.claude/skills/`;
para actualizarlas hace falta instalar la casa (`respawn-skills`) y pedir
«actualizá las skills de Respawn de este proyecto». La memoria de una sesión
no viaja: `.ai/` la reemplaza.

@AGENTS.md
