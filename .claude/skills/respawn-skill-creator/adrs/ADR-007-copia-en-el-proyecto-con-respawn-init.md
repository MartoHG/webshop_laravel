# ADR-007: Las skills se copian a cada proyecto con `respawn-init`

**Referenciado en:** `respawn-skill-creator`, `respawn-init`, `respawn-proyecto`

---

## Contexto

Un proyecto de cliente se trabaja desde más de una máquina, en Windows y en
Linux, y también en sesiones en la nube. Con las skills instaladas solo en la
máquina, un repositorio abierto en otro lado queda sin reglas. Con todas las
skills instaladas globalmente, además, el agente las ve en repositorios que no
son de Respawn.

## Alternativas evaluadas

| Opción                                                 | Por qué se descartó                                                        |
| ------------------------------------------------------ | -------------------------------------------------------------------------- |
| Enlazar todas las skills en la carpeta global          | El proyecto depende de la máquina y las skills aparecen en repositorios ajenos |
| Submódulo de git en cada proyecto                      | Suma comandos de git propios y no coincide con la carpeta que lee el agente |
| Copiar a mano las carpetas                             | Se olvida, y no queda registro de qué versión se copió                     |
| Copia hecha por una skill global de inicialización     | — (es la decisión)                                                         |

## Decisión

**La única skill instalada globalmente es `respawn-init`. Las demás llegan a
cada proyecto como copia, en `.claude/skills/`, hecha por `respawn-init`.**

- `respawn-init` copia las skills de la casa al proyecto, crea el esqueleto de
  `.ai/` y anota la versión de la casa de la que salió la copia.
- Correr `respawn-init` de nuevo en un proyecto reemplaza la copia por la
  versión vigente de la casa y actualiza la versión anotada. No toca `.ai/`.
- La copia se versiona en el repositorio del proyecto.
- `respawn-init` nunca pisa una carpeta de skill ajena a la casa.
- Un instalador por sistema (`install.ps1` e `install.sh`) enlaza
  `respawn-init` en la carpeta global y deja activas las skills dentro del
  repositorio de la casa.

## Consecuencias

### Ganamos

- El repositorio de un proyecto funciona en cualquier máquina sin instalar
  nada.
- Cada proyecto queda fijado a una versión conocida de las reglas.
- Una regla nueva de la casa no deja fuera de norma, sin aviso, a un proyecto
  ya entregado.

### Perdemos / Compromisos

- Actualizar las reglas de un proyecto es un paso explícito.
- Las skills ocupan lugar en el repositorio de cada cliente.

### Vigilancia requerida

- Una skill `respawn-*` editada dentro de un proyecto.
- Un proyecto sin versión anotada, o con una copia que no coincide con ella.
- Skills de la casa distintas de `respawn-init` en la carpeta global.
