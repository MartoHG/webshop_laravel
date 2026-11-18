# ADR-006: Los ADRs de la casa viven dentro de `respawn-skill-creator`

**Referenciado en:** `respawn-skill-creator`

---

## Contexto

Lo que llega a un proyecto son las carpetas de las skills, una al lado de la
otra. Una carpeta `adrs/` en la raíz del repositorio de la casa no forma parte
de ninguna skill: no se copia, y un enlace hacia ella no resuelve desde el
proyecto. Las skills sí se ven entre sí como carpetas hermanas.

## Alternativas evaluadas

| Opción                                             | Por qué se descartó                                               |
| -------------------------------------------------- | ----------------------------------------------------------------- |
| `adrs/` en la raíz del repositorio                 | Inalcanzable desde una skill copiada a un proyecto                |
| Copiar los ADRs que cita cada skill en su carpeta  | Duplica archivos y las copias se desalinean                       |
| Citar por número, sin enlace                       | El porqué queda fuera del alcance de quien trabaja en el proyecto |
| ADRs dentro de la skill dueña del sistema de ADRs  | — (es la decisión)                                                |

## Decisión

**Los ADRs de la casa y su índice viven en
`skills/respawn-skill-creator/adrs/`.**

- Las demás skills los enlazan como `../respawn-skill-creator/adrs/ADR-NNN-….md`.
- El índice es el `README.md` de esa carpeta.
- `respawn-skill-creator` se copia a todo proyecto junto con las demás skills,
  aunque ahí no se use para editar.

## Consecuencias

### Ganamos

- Todo enlace a un ADR resuelve igual en la casa y en un proyecto.
- El sistema de ADRs tiene una sola skill dueña.

### Perdemos / Compromisos

- Las skills de dominio dependen de que la skill meta esté presente.
- Los ADRs no se ven en la raíz del repositorio; el README los enlaza.

### Vigilancia requerida

- Un enlace a un ADR que sube más de un nivel desde la carpeta de la skill.
- Archivos `ADR-*.md` de la casa fuera de esa carpeta.
