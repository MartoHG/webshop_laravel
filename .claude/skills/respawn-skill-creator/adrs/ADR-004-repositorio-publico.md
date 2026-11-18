# ADR-004: El repositorio de la casa es público

**Referenciado en:** `respawn-skill-creator`

---

## Contexto

Las skills documentan cómo trabaja Respawn. Publicarlas muestra ese método a
posibles clientes y colegas. Un repositorio público no puede contener datos de
clientes, precios ni material con derechos de terceros.

## Alternativas evaluadas

| Opción                                       | Por qué se descartó                                              |
| -------------------------------------------- | ---------------------------------------------------------------- |
| Repositorio privado                          | Pierde el valor de portfolio y no protege nada que lo necesite   |
| Público solo la skill de diseño              | Parte el sistema en dos repositorios que hay que sincronizar     |
| Repositorio público completo                 | — (es la decisión)                                               |

## Decisión

**`respawn-skills` es público y no contiene precios, datos de clientes,
credenciales ni texto copiado de otras fuentes.**

- Los precios se leen de la web de Respawn en el momento de usarlos.
- Los informes de auditoría y los datos de cada cliente viven en el
  repositorio de su proyecto.
- El material tomado de fuentes externas se reescribe con palabras propias y
  la fuente se acredita en la tabla de fuentes de la skill.

## Consecuencias

### Ganamos

- El método de trabajo es parte del portfolio.
- Obliga a mantener las skills libres de datos sensibles.

### Perdemos / Compromisos

- Cualquier dato interno necesita otro lugar donde vivir.
- El texto queda expuesto a crítica y copia.

### Vigilancia requerida

- Montos, nombres de clientes, teléfonos o rutas locales dentro del
  repositorio.
- Párrafos que reproducen la redacción de una fuente externa.
