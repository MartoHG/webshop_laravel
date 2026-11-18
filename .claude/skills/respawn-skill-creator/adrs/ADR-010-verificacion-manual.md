# ADR-010: Las skills se verifican a mano con la lista de verificación

**Referenciado en:** `respawn-skill-creator`

---

## Contexto

Un validador automático detecta errores mecánicos: nombre de carpeta, enlaces
rotos, largo del archivo. Escribirlo y mantenerlo cuesta más que el sistema
que valida mientras las skills son pocas y las edita una sola persona.

## Alternativas evaluadas

| Opción                                        | Por qué se descartó                                              |
| --------------------------------------------- | ---------------------------------------------------------------- |
| Script de validación completo desde el inicio | Es código a mantener para un problema que todavía no apareció    |
| Sin verificación                              | Los defectos de las skills no se corrigen solos                  |
| Lista de verificación recorrida a mano        | — (es la decisión)                                               |

## Decisión

**Toda skill nueva o modificada se verifica a mano contra la lista de
verificación de `respawn-skill-creator`. No hay script de validación.**

El script se escribe recién cuando un mismo error mecánico pasa la
verificación manual dos veces. En ese caso cubre solo ese error.

## Consecuencias

### Ganamos

- Cero código de soporte que mantener.
- La lista de verificación se mantiene corta, porque alguien la recorre
  entera.

### Perdemos / Compromisos

- Un enlace roto o un nombre mal escrito puede pasar inadvertido.
- La verificación depende de la disciplina de quien edita.

### Vigilancia requerida

- Una skill modificada sin constancia de haber recorrido la lista.
- El mismo error mecánico encontrado por segunda vez.
