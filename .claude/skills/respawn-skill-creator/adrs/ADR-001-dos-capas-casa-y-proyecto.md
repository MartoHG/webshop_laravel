# ADR-001: Dos capas: la casa dice cómo se decide, el proyecto guarda qué se decidió

**Referenciado en:** todas las skills `respawn-*`

---

## Contexto

Respawn hace muchos proyectos con stacks distintos. Las reglas de trabajo son
las mismas para todos; los datos de cada cliente no. Si las reglas se escriben
en cada proyecto, cada uno termina con una versión distinta. Si viven solo en
la máquina de quien desarrolla, el repositorio del cliente no alcanza para
retomar el trabajo en otra máquina o en una sesión en la nube.

## Alternativas evaluadas

| Opción                                                       | Por qué se descartó                                                      |
| ------------------------------------------------------------ | ------------------------------------------------------------------------ |
| Escribir las reglas a mano en cada proyecto                  | Cada proyecto diverge y ninguna corrección llega a los demás             |
| Una sola capa instalada en la máquina, sin nada por proyecto | El repositorio del cliente queda incompleto y sin dónde guardar decisiones |
| Capa casa como fuente de verdad + capa proyecto en cada repositorio | — (es la decisión)                                                |

## Decisión

**Las reglas se escriben una sola vez en el repositorio `respawn-skills` (capa
casa). Cada proyecto lleva una copia de esas reglas y guarda sus propios datos
y decisiones en su `.ai/` (capa proyecto).**

- La capa casa es la fuente de verdad de toda regla.
- La copia de las skills en un proyecto es de solo lectura: se reemplaza
  entera desde la casa, nunca se edita en el proyecto.
- La capa proyecto contiene el brief, el registro de decisiones, los ADRs
  propios y la memoria del proyecto.
- Una skill de la casa nunca guarda datos de un cliente. Un proyecto nunca
  redefine una regla de la casa: registra una excepción con su porqué.

## Consecuencias

### Ganamos

- Una regla se corrige en un solo lugar.
- Cada proyecto es auditable contra la misma vara.
- El repositorio de un proyecto alcanza por sí solo para retomarlo.

### Perdemos / Compromisos

- Una corrección de la casa no llega sola a los proyectos: hay que actualizar
  su copia.
- Proyectos distintos pueden estar en versiones distintas de las reglas.

### Vigilancia requerida

- Una skill de la casa que nombra un cliente o un proyecto concreto como regla.
- Diferencias entre la copia de un proyecto y la versión de la casa que
  declara.
