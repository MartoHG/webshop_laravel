# ADR-P-001: Stack del proyecto

## Contexto

El Refugio es una tienda con panel de administración, cobro en línea con
Mercado Pago y base de datos: lleva backend. El público tiene poca
experiencia comprando online, así que la interfaz necesita pocos pasos y
acciones claras, no una aplicación compleja. El proyecto empezó el
2026-09-26, antes de que existiera el estándar de la casa (ADR-003), y su
stack quedó fijado en la sección 3 de `el-refugio-decisiones.md`.

## Alternativas evaluadas

| Opción                                                  | Por qué se descartó |
| ------------------------------------------------------- | ------------------- |
| SPA con React o Vue                                     | La sección 3 pide una aplicación Laravel tradicional y no hay una necesidad concreta que la justifique; la sección 50 la deja fuera de la V1 |
| Roles con spatie/laravel-permission                     | Solo hay dos roles (administrador y cliente) y la V1 no tiene permisos granulares (secciones 6 y 50) |
| Laravel 13, Blade, Tailwind CSS v4, HTMX, MySQL y PHPUnit | — (es la decisión) |

## Decisión

**Construir El Refugio con Laravel 13, Blade, Tailwind CSS v4, HTMX para la
interactividad puntual y MySQL, con tests en PHPUnit.**

Es el stack con backend de la casa, con dos desvíos:

- **PHPUnit en lugar de Pest:** el backend de la V1 ya estaba construido con
  225 tests en PHPUnit cuando se fijó el estándar. Según el ADR-003, lo
  construido con otra combinación se mantiene en su stack.
- **Sin spatie/laravel-permission:** los dos roles no lo necesitan
  (sección 6 de `el-refugio-decisiones.md`).

Cada ruta responde HTML o JSON desde el mismo controlador (sección 4 de
`el-refugio-decisiones.md`).

## Consecuencias

- **Ganamos:** las reglas de `respawn-sistemas` y `respawn-web` para Laravel
  y Blade aplican sin adaptaciones de stack, y la suite existente se
  conserva entera.
- **Perdemos:** las reglas y los ejemplos de tests de la casa están escritos
  para Pest y hay que traducirlos a PHPUnit.
- **Revisar si:** aparece la necesidad de permisos por empleado o de una
  interacción que HTMX no resuelva, o si se decide migrar la suite a Pest.
