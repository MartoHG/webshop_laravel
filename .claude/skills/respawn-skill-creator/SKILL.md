---
name: respawn-skill-creator
description: >
    Crea, modifica y audita las skills de la casa Respawn (respawn-* y
    review-respawn), sus references/ y assets/, y los ADRs de la casa. Usar en
    el repositorio respawn-skills siempre que el usuario pida crear una skill
    ("haceme una respawn-pc"), agregar o cambiar reglas de una existente,
    auditar las skills, revisar un frontmatter o una description, registrar un
    ADR de la casa, actualizar el diccionario de idioma o versionar un cambio
    en el CHANGELOG. No usar dentro de un proyecto de cliente: ahí las skills
    son una copia de solo lectura. No usar para aplicar las reglas de una
    skill al código de un proyecto (es de respawn-design, respawn-web y
    respawn-sistemas) ni para la memoria o los ADRs de un proyecto (es de
    respawn-proyecto).
---

# Respawn Skill Creator — crear y mantener las skills de la casa

## Contenido

0. Convenciones
1. Formato estándar
2. Anatomía del SKILL.md
3. Reglas de contenido
4. Crear una skill nueva
5. Modificar una skill existente
6. Auditar
7. Cierre: verificar, versionar y propagar
8. ADRs de la casa
9. Antipatrones
10. Fuentes de esta skill
11. Referencias

---

## 0. Convenciones

Esta skill guía todo trabajo sobre las skills y los ADRs de la casa. Las
reglas viven en la [lista de verificación](references/checklist.md); este
cuerpo las introduce y no las repite.

- **Fuente de verdad:** `skills/{nombre}/` en el repositorio `respawn-skills`.
  La copia que vive en un proyecto nunca se edita `*(Ver ADR-001)*`.
- **Dos capas:** la casa guarda reglas; lo específico de un cliente va al
  `.ai/` de su proyecto, que mantiene
  [respawn-proyecto](../respawn-proyecto/SKILL.md) `*(Ver ADR-001)*`.
- **Verificación manual:** no hay script de validación. Toda skill nueva o
  modificada se verifica recorriendo la lista completa `*(Ver ADR-010)*`.
- **Las skills existentes se toman con pinzas:** una skill que no cumple las
  reglas se corrige; su defecto no se copia como patrón.
- **Ante un caso no reglado:** preguntar al usuario antes de decidir.
- **Esta skill se mantiene corta a propósito:** si una edición la deja
  inflada, se recorta y el detalle pasa a `references/`.

---

## 1. Formato estándar

Las skills siguen el estándar abierto Agent Skills.

```
{nombre}/
├── SKILL.md       ← obligatorio: frontmatter YAML + instrucciones
├── references/    ← opcional: detalle que se lee bajo demanda
├── assets/        ← opcional: plantillas para copiar y completar
├── scripts/       ← opcional: tareas deterministas
└── evals/         ← consultas de activación
```

La carga es progresiva, en tres niveles:

1. **Frontmatter** (`name` y `description`): siempre en contexto. Es lo único
   que el agente lee para decidir si activa la skill.
2. **Cuerpo de `SKILL.md`:** se carga completo al activarse.
3. **Archivos adicionales:** se leen solo cuando el cuerpo los enlaza.

### Tipos de skills de la casa

| Tipo              | Qué norma                                           | Skills                                                           | `references/` obligatorias          |
| ----------------- | --------------------------------------------------- | ---------------------------------------------------------------- | ----------------------------------- |
| Dominio técnico   | Código de sitios y sistemas                         | `respawn-web`, `respawn-sistemas`                                | `checklist.md`                      |
| Dominio de diseño | Decisiones visuales y de textos                     | `respawn-design`                                                 | `catalogo-slop.md`, `textos.md` y `checklist.md` |
| Proceso           | Cómo se arranca, se registra y se audita un proyecto | `respawn-init`, `respawn-kickoff`, `respawn-proyecto`, `review-respawn` | Según el proceso                    |
| Meta              | Estándar de skills y ADRs                           | `respawn-skill-creator`                                          | `checklist.md` e `idioma.md`        |

---

## 2. Anatomía del SKILL.md

Toda skill de la casa sigue esta estructura, en este orden:

1. **Frontmatter** con `name` y `description`.
2. **Título H1** descriptivo del dominio.
3. **`## Contenido`:** índice numerado de las secciones `##`.
4. **Secciones `##` numeradas** desde 0, cada una con una sola función.
5. **`## {N}. Antipatrones`:** tabla de error frecuente y resolución. Es la
   última sección de contenido.
6. **`## {N}. Fuentes de esta skill`:** solo si la skill tomó material externo.
7. **`## {N}. Referencias`:** tabla con cada archivo enlazado.

### La description

Decide si la skill existe para una tarea: si el agente no la activa, el cuerpo
nunca se lee. Se escribe al final, con el cuerpo ya cerrado, y responde tres
cosas: qué hace, cuándo usarla y qué deja a una skill vecina. Sus requisitos
están en la [lista de verificación](references/checklist.md); cómo probarla,
en [references/pruebas-activacion.md](references/pruebas-activacion.md).

Las skills de la casa no son visibles fuera de los proyectos Respawn, así que
la `description` se dedica a distinguir entre skills vecinas de la casa
`*(Ver ADR-008)*`.

---

## 3. Reglas de contenido

- **Reglas verificables:** cada regla se puede responder con «se cumple» o «no
  se cumple». «Hacé un buen diseño» no es una regla.
- **El porqué va al ADR:** la skill enuncia la regla y cita el ADR en la misma
  línea, con `*(Ver ADR-NNN)*`.
- **Sin duplicados:** cada tema tiene una skill dueña y las demás enlazan su
  `SKILL.md`. Dentro de una skill, cada regla vive en un solo lugar.
- **Densidad antes que volumen:** se documentan contratos, restricciones y
  decisiones de Respawn. No se explica lo que el modelo ya sabe del lenguaje o
  del framework.
- **Repositorio público:** sin precios, clientes, credenciales ni texto
  copiado de una fuente `*(Ver ADR-004)*`.
- **Idioma:** castellano rioplatense con voseo, según el diccionario
  [references/idioma.md](references/idioma.md). El diccionario es único y vive
  solo en esta skill.

---

## 4. Crear una skill nueva

1. **Definir la skill** con la tabla de decisión siguiente.
2. **Escribir el borrador** con la anatomía de esta skill. Se escribe para
   otro agente: lo útil y no obvio, sin relleno.
3. **Escribir la `description`** y sus consultas de activación en
   `evals/activacion.json`.
4. **Seguir el cierre** de esta skill.

| Caso                                                     | Decisión                                                                                   |
| -------------------------------------------------------- | ------------------------------------------------------------------------------------------ |
| Pedido «haceme una skill para X» sin más contexto        | Preguntar qué debe poder hacer el agente, cuándo debe activarse y qué resultado se espera  |
| Pedido después de una sesión de trabajo                  | Extraer de la sesión los pasos seguidos y las correcciones del usuario                     |
| El contenido ya lo cubre otra skill de la casa           | No crear la skill: agregar la regla a la dueña                                             |
| Solapamiento parcial con otra skill                      | Fijar el límite en la `description` de ambas                                               |
| Regla que vale para un solo cliente                      | No crear la skill: va al `.ai/` de ese proyecto                                            |
| Skill nueva justificada                                  | Decidir tipo, `name` y si necesita `references/`, `assets/` o `scripts/`                   |

Ejemplo de skill nueva justificada: una `respawn-pc` para el servicio técnico
de PC. Es de dominio técnico, no se solapa con ninguna existente y su límite
negativo es todo lo que sea web o sistema.

---

## 5. Modificar una skill existente

1. **Leer del disco** el `SKILL.md` y los archivos que el cambio toca.
2. **Buscar la regla** en las demás skills antes de escribirla: si ya existe,
   se enlaza a su dueña.
3. **Editar solo la fuente de verdad**, cambiando lo que el pedido exige.
4. **Actualizar lo que depende del cambio:** el índice `## Contenido`, la tabla
   de Referencias, los enlaces desde otras skills y la lista de verificación
   de la skill.
5. **Seguir el cierre** de esta skill.

- El `name` y la carpeta se renombran solo ante un pedido explícito.
- Si la regla viene de una fuente registrada, se revisa esa fuente antes de
  cambiarla.

---

## 6. Auditar

Una auditoría es de solo lectura hasta que el usuario aprueba el plan.

| Pedido                                    | Alcance                              |
| ----------------------------------------- | ------------------------------------ |
| Nombra una skill                          | Esa skill con sus archivos           |
| Nombra los ADRs                           | Todos los ADRs de la casa y su índice |
| «Auditá las skills» o sin objeto          | Todas las skills y los ADRs          |
| Nombra algo que no existe o es ambiguo    | Listar las skills y preguntar cuál   |

1. **Leer del disco** el alcance completo.
2. **Recorrer la lista de verificación** regla por regla.
3. **Contrastar las citas:** cada ADR citado dice lo que la regla afirma, y
   cada archivo o ruta nombrado existe.
4. **Informar los hallazgos** con regla, archivo y línea, y una decisión
   propuesta para cada uno.
5. **Ejecutar lo aprobado** con el flujo de modificación.

| Decisión propuesta | Cuándo                                                             |
| ------------------ | ------------------------------------------------------------------ |
| Corregir           | Desvío de una regla vigente                                        |
| Descartar          | El texto cumple una excepción reglada; se cita la excepción        |
| Consultar          | Regla nueva, contradicción entre skills o término fuera del diccionario |

---

## 7. Cierre: verificar, versionar y propagar

Se ejecuta una vez, al terminar de crear o modificar una skill o un ADR.

1. **Recorrer la [lista de verificación](references/checklist.md)** completa
   sobre lo que cambió e informar el resultado.
2. **Correr la prueba de activación** si cambió una `description` o se sumó
   una skill.
3. **Anotar el cambio** en `CHANGELOG.md` con su número de versión.
4. **Actualizar los archivos de la raíz:** la tabla de skills del
   `README.md` si se sumó, quitó o renombró una skill, y `DECISIONES.md` si
   cambió un ADR o una decisión que figura ahí.
5. **Informar al usuario** qué cambió y qué proyectos conviene actualizar.

| Cambio                                                              | Versión |
| ------------------------------------------------------------------- | ------- |
| Regla nueva o más estricta que puede dejar fuera de norma a un proyecto ya inicializado | Mayor   |
| Skill nueva, o regla nueva que no invalida trabajo hecho            | Menor   |
| Redacción, enlaces o correcciones sin cambio de regla               | Parche  |

Un proyecto recibe la versión nueva recién cuando se le corre `respawn-init`
otra vez `*(Ver ADR-007)*`. El agente nunca hace commit ni sube cambios sin
pedido del usuario.

---

## 8. ADRs de la casa

Los ADRs registran el porqué de cada decisión y viven en `adrs/` de esta
skill, con su índice en [adrs/README.md](adrs/README.md) `*(Ver ADR-006)*`.
Los ADRs de un proyecto usan otra numeración y no son de esta skill
`*(Ver ADR-009)*`.

Cuándo amerita un ADR, los pasos del registro y sus antipatrones:
[references/flujo-adr.md](references/flujo-adr.md). Plantilla:
[assets/plantilla-adr.md](assets/plantilla-adr.md).

---

## 9. Antipatrones

| Antipatrón                                                        | Resolución                                                             |
| ----------------------------------------------------------------- | ---------------------------------------------------------------------- |
| Corregir una regla en la copia de un proyecto                     | Corregirla en la casa y actualizar el proyecto con `respawn-init`      |
| Regla que no se puede verificar («cuidar la calidad»)             | Reescribirla con el dato que se mide o se observa                      |
| Explicar el porqué en la skill                                    | Mover la justificación al ADR y dejar la regla con su cita             |
| Repetir en el cuerpo lo que dice la lista de verificación         | Dejar un rótulo de una línea con el enlace                             |
| Copiar la redacción de una fuente externa                         | Reescribir con palabras propias y registrar la fuente en su tabla      |
| Sumar una sección «por las dudas»                                 | Escribirla recién cuando un caso real la pida                          |
| Poner un precio, un cliente o una ruta local como ejemplo         | Usar un marcador `{entre-llaves}`                                      |
| Escribir la `description` antes que el cuerpo                     | Escribirla al final y probarla con sus consultas de activación         |
| Crear un ADR por iniciativa propia                                | Plantear la decisión, esperar el consenso y recién entonces redactar   |

---

## 10. Fuentes de esta skill

Registrar el origen evita que una edición futura desvirtúe las reglas que
vienen de cada fuente: antes de cambiar una regla, revisar su fuente.

| Fuente                                              | Qué aportó                                                                                         |
| --------------------------------------------------- | -------------------------------------------------------------------------------------------------- |
| Especificación Agent Skills (agentskills.io)        | Frontmatter, estructura de carpetas, carga progresiva y límite de 500 líneas                       |
| Skill authoring best practices (Anthropic)          | Tercera persona en la `description`, formato del `name` e índice en referencias largas             |
| skill-creator (Anthropic)                           | Consultas de activación con casos límite entre skills vecinas                                      |
| Sistema de skills de Senda (laravel-htmx-patterns)  | Lista de verificación con IDs estables, diccionario de idioma, sistema de ADRs y tabla de fuentes  |
| Keep a Changelog y Versionado Semántico             | Formato del `CHANGELOG.md` y criterio de mayor, menor y parche                                     |

---

## 11. Referencias

| Archivo                                                                | Contenido                                                                              |
| ---------------------------------------------------------------------- | -------------------------------------------------------------------------------------- |
| [references/checklist.md](references/checklist.md)                     | Lista de verificación completa: estructura, contenido, cierre y ADRs                   |
| [references/idioma.md](references/idioma.md)                           | Diccionario de idioma: voseo, términos fijos, excepciones y palabras bloqueadas        |
| [references/pruebas-activacion.md](references/pruebas-activacion.md)   | Cómo probar que una `description` activa la skill correcta                             |
| [references/flujo-adr.md](references/flujo-adr.md)                     | Cuándo amerita un ADR, pasos del registro y antipatrones                               |
| [assets/plantilla-adr.md](assets/plantilla-adr.md)                     | Plantilla de ADR para copiar y completar                                               |
| [evals/activacion.json](evals/activacion.json)                         | Consultas de activación de esta skill                                                  |
| [adrs/README.md](adrs/README.md)                                       | Índice de los ADRs de la casa                                                          |
| [../respawn-proyecto/SKILL.md](../respawn-proyecto/SKILL.md)           | Capa proyecto: memoria, decisiones y ADRs de cada proyecto                             |
