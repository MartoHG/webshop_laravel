# Plantilla de ADR

Copiar el bloque a `adrs/ADR-NNN-descripcion-kebab-case.md` y completarlo.
Sin estado, fecha ni autores: un cambio de decisión reescribe el archivo y el
historial lo guarda git. Sin bloques de código: la mecánica vive en la skill
dueña.

```markdown
# ADR-NNN: [Título en una línea]

**Referenciado en:** [skills que citan este ADR]

---

## Contexto

[El problema que se quería resolver y las fuerzas en juego. Los casos reales
se admiten como evidencia de un problema general, sin nombrar clientes.]

## Alternativas evaluadas

| Opción           | Por qué se descartó |
| ---------------- | ------------------- |
| [Opción A]       | [Razón concreta]    |
| [Opción B]       | [Razón concreta]    |
| [Opción elegida] | — (es la decisión)  |

## Decisión

**[Frase imperativa. Es la regla, y se puede copiar a la skill.]**

[Qué se hace y qué no, en uno o dos párrafos o en viñetas cortas. Sin pasos
de implementación.]

## Consecuencias

### Ganamos

- [Beneficio 1]
- [Beneficio 2]

### Perdemos / Compromisos

- [Costo o límite 1]

### Vigilancia requerida

- [Señal observable de que la regla se está violando]
```
