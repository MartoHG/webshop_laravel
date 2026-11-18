# ADR-012: En proyectos Respawn manda `respawn-design` sobre las skills de diseño de terceros

**Referenciado en:** `respawn-design`

---

## Contexto

Conviven instaladas skills de diseño de terceros que se activan ante
cualquier trabajo de frontend. Cada una trae su propio proceso y sus propias
preferencias estéticas. Sin un orden, dos skills pueden dar instrucciones
opuestas sobre la misma pantalla, o saltear el concepto acordado con el
cliente.

## Alternativas evaluadas

| Opción                                           | Por qué se descartó                                                |
| ------------------------------------------------ | ------------------------------------------------------------------ |
| Desinstalar las skills de terceros               | Sirven en otros proyectos y como material de consulta              |
| Dejar que el agente elija en cada caso           | El resultado depende de cuál se active primero                     |
| Prioridad explícita de la skill de la casa       | — (es la decisión)                                                 |

## Decisión

**En un proyecto Respawn, el proceso y las reglas de `respawn-design`
prevalecen sobre cualquier otra skill de diseño.**

- Otra skill de diseño se usa solo como consulta técnica puntual.
- Ninguna skill reemplaza el concepto acordado, las decisiones registradas ni
  la pasada contra el catálogo.
- Ante una contradicción entre skills, se aplica la de la casa y se informa la
  diferencia al usuario.

## Consecuencias

### Ganamos

- Un solo proceso de diseño por proyecto.
- Las skills de terceros siguen disponibles sin gobernar el resultado.

### Perdemos / Compromisos

- Una buena idea de otra skill no se aplica sola: pasa por el registro de
  decisiones.

### Vigilancia requerida

- Pantallas diseñadas sin concepto acordado porque otra skill tomó el control.
- Decisiones visuales justificadas con la regla de una skill de terceros.
