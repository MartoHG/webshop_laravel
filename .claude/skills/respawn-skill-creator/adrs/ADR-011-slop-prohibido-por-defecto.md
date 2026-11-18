# ADR-011: Los patrones de slop están prohibidos salvo excepción registrada

**Referenciado en:** `respawn-design`, `review-respawn`

---

## Contexto

Una web hecha con valores predeterminados se reconoce por patrones repetidos
de estructura, tipografía, color, componentes y textos. Prohibirlos sin
matices impide diseños legítimos: una identidad arcade usa tipografía pixel,
numeración de niveles y fondo oscuro con un acento, tres patrones del catálogo.
Lo que delata no es el patrón sino que nadie lo haya decidido.

Los medidores externos de slop cambian sus criterios, limitan el uso gratuito
o miden otra cosa, como texto en inglés. Sirven de segunda opinión, no de vara.

## Alternativas evaluadas

| Opción                                            | Por qué se descartó                                               |
| ------------------------------------------------- | ----------------------------------------------------------------- |
| Prohibición absoluta de los patrones del catálogo | Descarta conceptos válidos y empuja a otro uniforme               |
| Catálogo como sugerencia                          | "Así salió" pasa como decisión                                    |
| Exigir un puntaje de un medidor externo           | La entrega queda atada a una herramienta que no se controla       |
| Prohibido por defecto, permitido por concepto     | — (es la decisión)                                                |

## Decisión

**Todo patrón del catálogo de slop está prohibido por defecto. Se permite solo
si lo justifica el concepto del proyecto y queda anotado en su registro de
decisiones.**

- La vara es el catálogo propio de `respawn-design`, recorrido completo antes
  de cada entrega.
- Cada patrón presente necesita una entrada en el registro de decisiones del
  proyecto que nombre el patrón y lo derive del concepto.
- "Así salió", "se ve bien" o "es lo habitual" no son justificaciones.
- Un patrón presente sin entrada es bloqueante en la auditoría.
- El resultado de un medidor externo se anota en el informe como dato, con
  fecha, y nunca bloquea ni aprueba por sí solo.

## Consecuencias

### Ganamos

- Cada decisión visual tiene un porqué escrito.
- El criterio no depende de servicios de terceros.

### Perdemos / Compromisos

- Más trabajo de registro en cada proyecto.
- El catálogo hay que mantenerlo: los patrones que delatan cambian con el
  tiempo.

### Vigilancia requerida

- Entradas de excepción que no nombran el concepto del proyecto.
- Un proyecto con más excepciones que decisiones propias: señal de que el
  concepto se usa como excusa.
