# ADR-014: Qué impide el sello

**Referenciado en:** `review-respawn`

---

## Contexto

El sello es pasar la auditoría sin bloqueantes. Quedan casos que esa frase
no resuelve: una regla bloqueante que no se pudo comprobar porque una
herramienta no corrió, una deuda registrada y todavía abierta, y un desvío
que el usuario decidió conservar a propósito y anotó como riesgo aceptado.

Los controles legales de un sitio que cobra son un caso aparte: si faltan,
la sanción la recibe el cliente, que no tomó la decisión.

## Alternativas evaluadas

| Opción                                                     | Por qué se descartó                                                         |
| ---------------------------------------------------------- | --------------------------------------------------------------------------- |
| Lo que no se pudo comprobar aprueba con un aviso           | El sello afirmaría algo que nadie miró                                      |
| Un riesgo aceptado nunca cubre una regla bloqueante        | Obliga a corregir desvíos que el usuario decidió conservar con un motivo     |
| Un riesgo aceptado cubre todo, también lo legal            | El cliente queda expuesto a una sanción por una decisión que no tomó         |
| Todo comprobado, riesgo que cubre todo salvo lo legal      | — (es la decisión)                                                          |

## Decisión

**Un proyecto tiene el sello cuando cada regla bloqueante se comprobó y
cumple, o está cubierta por un riesgo aceptado. Los controles legales no se
cubren con un riesgo aceptado.**

- Una regla bloqueante sin comprobar deja la auditoría incompleta, sin sello.
- La deuda de severidad alta abierta impide el sello. La media y la baja se
  informan como recomendaciones.
- Un riesgo aceptado cubre una regla bloqueante si lo decidió el usuario,
  nombra esa regla y su condición de revisión no se cumplió. El informe lo
  lista aparte.
- El sello se da sobre el dominio definitivo: en una vista previa, lo que
  solo se ve en producción queda sin comprobar.
- El dominio o el hosting a nombre del desarrollador no impiden el sello. El
  informe para el cliente lo dice.

## Consecuencias

### Ganamos

- Un sello garantiza que todo lo bloqueante se miró.
- Un desvío conservado a propósito no frena la entrega, y queda escrito.

### Perdemos / Compromisos

- Una auditoría puede quedar incompleta porque una herramienta no está
  disponible.
- Un sello con riesgos aceptados dice menos que uno sin ellos: hay que leer
  su sección de riesgos.

### Vigilancia requerida

- Un informe con sello que tiene reglas bloqueantes sin comprobar.
- Un control legal anotado en los riesgos aceptados de un proyecto.
- Riesgos aceptados sobre reglas bloqueantes que se repiten en cada proyecto:
  señal de que la regla de la casa hay que revisarla.
