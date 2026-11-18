# ADR-005: Umbrales de la auditoría de entrega

**Referenciado en:** `review-respawn`, `respawn-web`

---

## Contexto

Una auditoría sin números se discute en cada proyecto. Los umbrales tienen que
ser alcanzables por un sitio estático bien hecho y por las páginas públicas de
una aplicación con servidor, que parten de un piso de rendimiento más bajo.
La mayoría de las visitas llega desde el celular.

## Alternativas evaluadas

| Opción                                           | Por qué se descartó                                                |
| ------------------------------------------------ | ------------------------------------------------------------------ |
| Sin umbrales, a criterio en cada entrega         | No es verificable ni comparable entre proyectos                    |
| Exigir 100 en todas las categorías               | Bloquea entregas por detalles que no afectan a quien visita        |
| Un mismo piso de rendimiento para todo stack     | Penaliza a las aplicaciones con servidor por su arquitectura       |
| Piso de 90 con rendimiento diferenciado por stack | — (es la decisión)                                                 |

## Decisión

**La auditoría mide en celular y bloquea la entrega por debajo de estos
valores.**

| Medición                                              | Bloqueante si       |
| ----------------------------------------------------- | ------------------- |
| Lighthouse Rendimiento, sitio sin backend             | menos de 90         |
| Lighthouse Rendimiento, páginas públicas con backend  | menos de 80         |
| Lighthouse Accesibilidad, Buenas prácticas y SEO      | menos de 90         |
| LCP                                                   | más de 2,5 s        |
| CLS                                                   | más de 0,1          |
| Contraste de texto                                    | por debajo de AA    |
| Errores en la consola al cargar                       | uno o más           |
| Links rotos                                           | uno o más           |
| Desplazamiento horizontal a 360 px de ancho           | presente            |

- Se mide cada página pública del sitio, no solo la portada.
- Un valor entre el umbral y 100 se informa como recomendación.
- Las pantallas del panel de administración no se miden con Lighthouse.

## Consecuencias

### Ganamos

- "Pasa" o "no pasa" deja de ser una opinión.
- El mismo informe sirve para todos los proyectos.

### Perdemos / Compromisos

- Lighthouse varía entre corridas: un valor en el límite exige repetir la
  medición.
- Los umbrales no miden diseño ni textos; eso lo cubre el catálogo de slop.

### Vigilancia requerida

- Un informe con mediciones solo de la portada o solo en escritorio.
- Un proyecto entregado con un bloqueante anotado como recomendación.
