# Proyecto Respawn · {nombre del proyecto}

Skills de la casa: versión {X.Y.Z}

{Una o dos oraciones: qué es el proyecto y para qué negocio.}

## Proyecto

- **Cliente:** {negocio, rubro y ciudad}
- **Paquete:** {paquete contratado}
- **Stack:** {stack}, registrado en `.ai/adrs/ADR-P-001-stack.md`
- **Acción objetivo:** {qué tiene que hacer el visitante}
- **Concepto:** {una línea; el detalle está en `.ai/decisiones.md`}

## Skills que aplican

| Skill              | Cuándo                                                          |
| ------------------ | --------------------------------------------------------------- |
| `respawn-proyecto` | Registrar o consultar decisiones, deuda, pendientes y estado    |
| `respawn-kickoff`  | Relevar al cliente, encuadrar el paquete y fijar el alcance     |
| `respawn-design`   | Diseñar o criticar pantallas y escribir textos                  |
| `respawn-web`      | Accesibilidad, rendimiento, SEO y links de contacto             |
| `respawn-sistemas` | {Solo si el proyecto lleva backend: panel, tienda o sistema}    |
| `review-respawn`   | Auditar antes de entregar                                       |

Las skills de `.claude/skills/` son una copia de la casa: no se editan acá.

## Memoria del proyecto

| Archivo                      | Qué guarda                                              |
| ---------------------------- | ------------------------------------------------------- |
| `.ai/brief.md`               | Cliente, visitante, acción objetivo, material y alcance |
| `.ai/decisiones.md`          | Concepto, excepciones al catálogo de slop y decisiones  |
| `.ai/estado.md`              | Etapa, próximo paso y bloqueos                          |
| `.ai/entrega.md`             | Dominio, hosting, backups, accesos y manual             |
| `.ai/adrs/`                  | Decisiones difíciles de revertir                        |
| `.ai/deuda-tecnica.md`       | Desvíos a corregir, si los hay                          |
| `.ai/riesgos-aceptados.md`   | Desvíos conservados a propósito, si los hay             |
| `.ai/problemas-conocidos.md` | Trampas del entorno, si las hay                         |
| `.ai/pendientes-cliente.md`  | Lo que falta que el cliente entregue, si hay algo       |
| `.ai/auditoria.md`           | Último informe de auditoría, si ya se auditó            |
| `.ai/auditoria-cliente.md`   | Informe para el cliente, si el proyecto tiene el sello  |

## Cómo trabajar

- Al empezar una sesión, leer `.ai/estado.md` e informar etapa, próximo paso
  y bloqueos.
- Antes de implementar, acordar el diseño con el usuario.
- Ante un pedido nuevo del cliente, comprobar con `respawn-kickoff` si entra
  en el alcance del brief antes de hacerlo.
- Pantalla por pantalla: terminar una, mostrarla y esperar la revisión antes
  de seguir.
- Nunca inventar precios, reseñas, cifras, clientes ni testimonios. Lo que
  falta queda como `[FALTA: …]`.
- Sin commit ni subida de cambios sin pedido del usuario.

## Comandos

{Comandos del proyecto: instalar, levantar en local, compilar, probar y
publicar.}
