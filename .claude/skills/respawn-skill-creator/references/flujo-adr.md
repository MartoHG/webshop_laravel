# Flujo de trabajo de ADR

> Referencia de `respawn-skill-creator`: cuándo se registra una decisión de
> la casa y cómo. La plantilla vive en `assets/plantilla-adr.md`; el índice,
> en `adrs/README.md`.

## ¿Cuándo amerita un ADR?

| Caso                                                                  | ¿ADR? |
| --------------------------------------------------------------------- | ----- |
| Decisión difícil de revertir o que afecta a más de una skill          | Sí    |
| Regla que alguien va a querer discutir de nuevo («¿por qué no X?»)    | Sí    |
| Umbral o criterio que define si un proyecto se entrega                | Sí    |
| Redacción, orden de secciones o nombre de un archivo                  | No    |
| Regla que se explica sola en una línea                                | No    |
| Decisión que vale para un solo proyecto                               | No: es un `ADR-P-NNN` o una fila del registro de decisiones de ese proyecto |

## Consenso previo

1. El agente plantea la decisión con sus opciones y una recomendación.
2. El usuario elige.
3. Recién entonces el agente redacta el ADR.

## Registro

1. Crear `adrs/ADR-NNN-descripcion-kebab-case.md` con la plantilla y el número
   siguiente al último del índice.
2. Completar contexto, alternativas, decisión y consecuencias.
3. Agregar la fila en `adrs/README.md`, con el mismo título que el H1.
4. Agregar o ajustar la regla en la skill dueña, con su cita
   `*(Ver ADR-NNN)*` en la misma línea.
5. Seguir el cierre de la skill.

## Cambiar una decisión

- El archivo se reescribe para decir la decisión vigente. No se agregan notas
  de historial ni fechas.
- El número no cambia. Si cambia el título, se renombra el archivo y se
  actualizan el índice y los enlaces.
- La regla de la skill cambia en el mismo cambio.
- Un ADR descartado no se borra: se reescribe con la decisión que lo
  reemplaza.

## Cuando alguien pregunta «¿por qué no usamos X?»

No se debate de nuevo: se lee el ADR. Si aparece un argumento que el ADR no
consideró, se plantea como decisión nueva y sigue el consenso previo.

## Antipatrones

| Antipatrón                                              | Resolución                                                          |
| ------------------------------------------------------- | ------------------------------------------------------------------- |
| El porqué escrito en la skill                           | Moverlo al ADR; la skill queda con la regla y la cita               |
| Dos decisiones en un ADR                                | Un ADR por decisión                                                 |
| Pasos de implementación o código en el ADR              | Llevarlos a la skill dueña                                          |
| ADR que no figura en el índice                          | Agregar la fila al registrarlo                                      |
| «Editado el…» o «Estado: reemplazado»                   | Reescribir el texto con la decisión vigente                         |
| ADR redactado antes del acuerdo                         | Plantear, esperar la elección y después redactar                    |
| Contexto que nombra a un cliente                        | Describir la fuerza general que el caso muestra                     |
