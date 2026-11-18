# ADR-008: El disparo de las skills se acota por presencia en el proyecto

**Referenciado en:** todas las skills `respawn-*`

---

## Contexto

La `description` es el único texto que decide si una skill se activa. Una
skill visible en todo repositorio necesita condiciones en su `description`
para no activarse donde no corresponde, y esas condiciones dependen de que el
agente las evalúe bien cada vez. Hay repositorios con su propio sistema de
skills que no deben recibir reglas de Respawn.

## Alternativas evaluadas

| Opción                                                   | Por qué se descartó                                              |
| -------------------------------------------------------- | ---------------------------------------------------------------- |
| Skills globales con descripciones genéricas              | Se activan en repositorios ajenos y pisan sus reglas             |
| Skills globales condicionadas a una marca en `CLAUDE.md` | La condición es texto: puede evaluarse mal y no impide el disparo |
| Solo invocación manual por nombre                        | La vara deja de aplicarse sola                                   |
| Skills presentes solo en los proyectos Respawn           | — (es la decisión)                                               |

## Decisión

**Las skills de la casa existen solo dentro de los proyectos Respawn y del
repositorio de la casa. Fuera de ahí el agente no las ve.**

- `respawn-init` es la única visible en todo repositorio. Su `description` la
  limita al pedido explícito de inicializar o actualizar un proyecto Respawn.
- El `CLAUDE.md` de cada proyecto abre con la línea `Proyecto Respawn` y
  declara qué skills de la casa aplican a ese proyecto.
- Cada `description` nombra su límite con las skills vecinas de la casa.

## Consecuencias

### Ganamos

- Ninguna regla de Respawn llega a un repositorio que no la pidió.
- Las descripciones se dedican a distinguir entre skills de la casa.

### Perdemos / Compromisos

- Un proyecto sin inicializar no tiene ninguna regla hasta correr
  `respawn-init`.

### Vigilancia requerida

- Una skill de la casa, distinta de `respawn-init`, en la carpeta global.
- Un proyecto Respawn sin la línea `Proyecto Respawn` en su `CLAUDE.md`.
