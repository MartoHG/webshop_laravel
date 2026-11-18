# ADR-009: Numeración separada: `ADR-NNN` en la casa, `ADR-P-NNN` en el proyecto

**Referenciado en:** `respawn-skill-creator`, `respawn-kickoff`

---

## Contexto

Hay ADRs en dos capas. Si ambas numeran `ADR-001`, una cita dentro de un
proyecto es ambigua: no se sabe si remite a la decisión de la casa o a la del
proyecto.

## Alternativas evaluadas

| Opción                                           | Por qué se descartó                                            |
| ------------------------------------------------ | -------------------------------------------------------------- |
| La misma numeración en ambas capas               | Las citas chocan                                               |
| Una numeración global compartida                 | Obliga a coordinar números entre repositorios independientes   |
| Prefijo distinto en la capa proyecto             | — (es la decisión)                                             |

## Decisión

**Los ADRs de la casa se llaman `ADR-NNN`; los de un proyecto, `ADR-P-NNN`,
en `.ai/adrs/` de su repositorio.**

- Las skills citan solo ADRs de la casa, como `*(Ver ADR-NNN)*`.
- El `ADR-P-001` de todo proyecto registra el stack elegido.
- En ambas capas los números son secuenciales y no se reúsan.
- Un ADR de proyecto se reserva para decisiones difíciles de revertir. Las
  decisiones de diseño y contenido van al registro de decisiones del proyecto.

## Consecuencias

### Ganamos

- Toda cita identifica su capa sin contexto adicional.
- Cada proyecto numera sin coordinar con nadie.

### Perdemos / Compromisos

- Dos convenciones de nombre que recordar.

### Vigilancia requerida

- Un archivo `ADR-NNN` sin prefijo `P` dentro de un repositorio de cliente.
- Una skill de la casa que cita un `ADR-P-NNN`.
