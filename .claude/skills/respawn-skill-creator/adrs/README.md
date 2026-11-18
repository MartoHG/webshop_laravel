# Índice de ADRs de la casa

Los ADRs registran el **porqué** de las decisiones del sistema `respawn-*`:
contexto, alternativas descartadas, decisión y consecuencias.

## Regla de consulta

- «¿Por qué la regla es así?» → **ADR**.
- «¿Qué tengo que hacer en esta tarea?» → **skill**.
- «¿Qué se decidió en este proyecto?» → el `.ai/` del proyecto.

## Convención

- Un ADR = una decisión.
- Nombre: `ADR-NNN-descripcion-kebab-case.md`. Números secuenciales, sin
  reúso.
- Un cambio de decisión reescribe el archivo. Sin fechas ni historial: eso lo
  guarda git.
- Las skills citan el ADR en la línea de la regla: `*(Ver ADR-NNN)*`.
- El agente nunca crea ni edita un ADR sin consenso previo con el usuario.

## Índice

| ADR                                                         | Título                                                                        |
| ----------------------------------------------------------- | ----------------------------------------------------------------------------- |
| [ADR-001](ADR-001-dos-capas-casa-y-proyecto.md)             | Dos capas: la casa dice cómo se decide, el proyecto guarda qué se decidió     |
| [ADR-002](ADR-002-sello-interno.md)                         | El sello es interno: pasar la auditoría sin bloqueantes                       |
| [ADR-003](ADR-003-stack-de-la-casa.md)                      | Stack de la casa: lo decide si el proyecto lleva backend                      |
| [ADR-004](ADR-004-repositorio-publico.md)                   | El repositorio de la casa es público                                          |
| [ADR-005](ADR-005-umbrales-de-auditoria.md)                 | Umbrales de la auditoría de entrega                                           |
| [ADR-006](ADR-006-adrs-dentro-de-skill-creator.md)          | Los ADRs de la casa viven dentro de `respawn-skill-creator`                   |
| [ADR-007](ADR-007-copia-en-el-proyecto-con-respawn-init.md) | Las skills se copian a cada proyecto con `respawn-init`                       |
| [ADR-008](ADR-008-disparo-acotado-por-presencia.md)         | El disparo de las skills se acota por presencia en el proyecto                |
| [ADR-009](ADR-009-numeracion-de-adrs.md)                    | Numeración separada: `ADR-NNN` en la casa, `ADR-P-NNN` en el proyecto         |
| [ADR-010](ADR-010-verificacion-manual.md)                   | Las skills se verifican a mano con la lista de verificación                   |
| [ADR-011](ADR-011-slop-prohibido-por-defecto.md)            | Los patrones de slop están prohibidos salvo excepción registrada              |
| [ADR-012](ADR-012-prioridad-sobre-skills-de-terceros.md)    | En proyectos Respawn manda `respawn-design` sobre las skills de diseño de terceros |
| [ADR-013](ADR-013-memoria-del-proyecto-en-inventarios.md)   | La memoria del proyecto vive en inventarios dentro de su `.ai/`               |
| [ADR-014](ADR-014-que-impide-el-sello.md)                   | Qué impide el sello                                                           |
| [ADR-015](ADR-015-legal-solo-en-sitios-que-cobran.md)       | La auditoría revisa lo legal solo en los sitios que cobran                    |
