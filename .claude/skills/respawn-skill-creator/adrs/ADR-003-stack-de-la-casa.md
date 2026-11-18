# ADR-003: Stack de la casa: lo decide si el proyecto lleva backend

**Referenciado en:** `respawn-kickoff`, `respawn-web`, `respawn-sistemas`

---

## Contexto

Los proyectos previos usan cuatro combinaciones distintas de frontend. Cada
combinación nueva obliga a mantener reglas, plantillas y criterios de
auditoría propios. El nombre del paquete no alcanza para elegir el stack: hay
sitios de varias páginas sin panel y sin backend.

## Alternativas evaluadas

| Opción                                            | Por qué se descartó                                                      |
| ------------------------------------------------- | ------------------------------------------------------------------------ |
| Elegir stack libremente en cada proyecto          | Multiplica las reglas a mantener y vuelve incomparable la auditoría      |
| Laravel para todo                                 | Un sitio sin backend paga servidor, deploy y mantenimiento que no necesita |
| Livewire o Alpine.js como frontend de los paneles | Suman una segunda forma de manejar estado junto a HTMX                   |
| Dos stacks, elegidos por la presencia de backend  | — (es la decisión)                                                       |

## Decisión

**Sin backend: Astro con CSS plano. Con backend: Laravel + Blade + Tailwind +
HTMX.**

- Sin backend significa sin panel, sin base de datos y sin cobro en línea. Es
  un sitio estático con Astro, CSS plano sobre tokens propios y JS solo donde
  hay interacción.
- Con backend significa panel, tienda o sistema a medida. Es Laravel con
  Blade, Tailwind, HTMX, MySQL, Pest y spatie/laravel-permission.
- Los proyectos construidos con otra combinación son legados: se mantienen en
  su stack y no cambian este estándar.
- Un proyecto nuevo que necesita otro stack lo registra como excepción en su
  primer ADR de proyecto, con el porqué.

## Consecuencias

### Ganamos

- Dos conjuntos de reglas y plantillas, no uno por proyecto.
- La elección de stack es una pregunta de sí o no en el relevamiento.

### Perdemos / Compromisos

- Un sitio estático que más adelante necesita panel se reescribe en Laravel.
- Los proyectos legados quedan fuera de las reglas de stack de la casa.

### Vigilancia requerida

- Un proyecto nuevo con Livewire, Alpine.js, React o Bootstrap sin ADR de
  proyecto que lo justifique.
- Tailwind en un sitio Astro, o CSS plano en las vistas de un panel Laravel.
