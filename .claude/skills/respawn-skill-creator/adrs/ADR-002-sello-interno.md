# ADR-002: El sello es interno: pasar la auditoría sin bloqueantes

**Referenciado en:** `review-respawn`

---

## Contexto

"Sello de calidad" puede significar dos cosas: una vara interna de trabajo o
un crédito visible en la web del cliente. Un crédito visible es además un
patrón del catálogo de slop cuando aparece como insignia del autor.

## Alternativas evaluadas

| Opción                                             | Por qué se descartó                                                   |
| -------------------------------------------------- | --------------------------------------------------------------------- |
| Crédito visible "Hecho por Respawn" en cada web    | La web es del cliente; el crédito no aporta a quien la visita         |
| Crédito visible solo en proyectos que pasan        | Mismo problema, y suma un check que no mide calidad                   |
| Sello interno, sin marca visible                   | — (es la decisión)                                                    |

## Decisión

**Un proyecto tiene el sello cuando pasa `review-respawn` sin bloqueantes. El
sello no se muestra en la web del cliente.**

El sello es el informe de auditoría aprobado, guardado en el repositorio del
proyecto. Ninguna skill agrega créditos, insignias ni links a Respawn en el
sitio de un cliente.

## Consecuencias

### Ganamos

- El criterio de "terminado" es único y verificable.
- La web del cliente no carga elementos ajenos a su negocio.

### Perdemos / Compromisos

- Los trabajos entregados no generan visibilidad por sí solos; la difusión
  queda en el portfolio de Respawn.

### Vigilancia requerida

- Un proyecto dado por entregado sin informe de auditoría en su repositorio.
- Un pie de página o `meta` con crédito a Respawn en la web de un cliente.
