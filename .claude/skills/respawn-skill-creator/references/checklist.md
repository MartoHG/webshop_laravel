# Lista de verificación de skills (reglas A a D)

> Fuente operativa de `respawn-skill-creator`: las reglas viven acá y el
> cuerpo de la skill solo las introduce. Cada ítem tiene un ID estable: no se
> reordena ni se reúsa, y una regla eliminada deja su hueco.

## Contenido

- Uso
- A. Estructura
- B. Contenido y redacción
- C. Cierre y versionado
- D. ADRs

---

## Uso

Recorrer la lista completa antes de dar por terminada una skill nueva o
modificada. Para un ADR, aplicar las reglas D y las reglas B que valen para
todo documento (B1 a B9).

1. **Leer del disco** cada archivo que cambió.
2. **Evaluar cada regla** como «cumple», «no cumple» o «no aplica».
3. **Corregir** todo bloqueante.
4. **Informar** al usuario las reglas que no cumplen y quedaron sin corregir,
   con su motivo.

**Bloqueantes: A1 a A4, A7, B9, B13, C1 y D3.** Un bloqueante impide cerrar el
cambio. El resto se corrige o se justifica en el informe.

## A. Estructura

- **A1.** Ubicación y fuente de verdad:
    - La skill vive en `skills/{nombre}/SKILL.md` del repositorio
      `respawn-skills`.
    - Nunca se edita la copia de un proyecto ni la de la carpeta global
      `*(Ver ADR-001)*`.
- **A2.** Frontmatter: bloque YAML válido con `name` y `description`, ninguno
  vacío.
- **A3.** Formato del `name`:
    - Kebab-case: minúsculas, dígitos y guiones, sin guion inicial, final ni
      doble. Máximo 64 caracteres.
    - Idéntico al nombre de su carpeta.
    - Contiene `respawn`: `respawn-{dominio}` o `review-respawn`.
- **A4.** Requisitos de la `description`:
    - Máximo 1024 caracteres, en bloque plegado (`description: >`).
    - Tercera persona o impersonal («Usar siempre que el usuario pida…»).
    - Responde tres cosas, una oración cada una: qué hace, cuándo usarla y
      qué no cubre, nombrando la skill vecina a la que corresponde.
    - Incluye pedidos literales entre comillas y palabras clave del dominio.
    - Se escribe al final, con el cuerpo cerrado.
    - _Incorrecto:_ «Ayuda con el diseño de webs.»
    - _Correcto:_ «Usar siempre que el usuario pida diseñar o criticar una
      pantalla […]. No usar para accesibilidad o SEO: es de respawn-web.»
- **A5.** Título e índice:
    - Abre con un H1 y la sección `## Contenido`, con la lista numerada de
      las secciones `##`.
    - Toda sección `##` va numerada desde 0, salvo `## Contenido`.
    - La lista coincide con los títulos en número, texto y orden.
- **A6.** Largo: `SKILL.md` completo tiene menos de 500 líneas. Si las
  supera, el detalle pasa a `references/`; el límite nunca justifica borrar
  una regla.
- **A7.** Enlaces:
    - Todo enlace local resuelve a un archivo existente y usa `/`.
    - Un solo nivel: los archivos de `references/` y `assets/` no enlazan a
      otros archivos; los nombran en texto plano.
    - Hacia otra skill se enlaza solo su `SKILL.md`.
    - Los ADRs se enlazan o se citan por número; su carpeta es la única
      excepción a la regla anterior `*(Ver ADR-006)*`.
- **A8.** Fin de línea: terminadores LF y salto de línea final.
- **A9.** Cierre del documento, en este orden:
    - `## {N}. Antipatrones`: tabla `| Antipatrón | Resolución |`. Es la
      última sección de contenido.
    - `## {N}. Fuentes de esta skill`: tabla `| Fuente | Qué aportó |`, solo
      con fuentes externas al repositorio. Obligatoria si la skill tomó
      material externo.
    - `## {N}. Referencias`: tabla `| Archivo | Contenido |` con cada archivo
      que el cuerpo enlaza.
- **A10.** Archivos adicionales:
    - El detalle va en `references/`; las plantillas para copiar, en
      `assets/`.
    - Un archivo de `references/` de más de 100 líneas abre con un índice
      `## Contenido`.
    - Una lista de verificación usa `## Uso` y secciones con letra
      (`## A. …`), con IDs estables.
    - Una skill de dominio técnico tiene `references/checklist.md`.

## B. Contenido y redacción

- **B1.** Idioma: castellano rioplatense con voseo, según `idioma.md`. Una
  palabra que falta se agrega al diccionario, no a la skill.
- **B2.** Tono:
    - Imperativo y concreto.
    - Sin tutorial: no se explica el funcionamiento propio del lenguaje, del
      framework ni de la herramienta.
    - Sin direcciones web en el cuerpo. Excepciones: la tabla de fuentes y la
      dirección que una regla manda a consultar.
- **B3.** Oraciones cortas: una idea por oración. Una bifurcación con más de
  dos casos se escribe como tabla `| Caso | Decisión |`, no en prosa.
- **B4.** Un término por concepto en todo el documento.
- **B5.** Reglas verificables: cada regla se responde con «cumple» o «no
  cumple».
    - _Incorrecto:_ «Usar una paleta adecuada al cliente.»
    - _Correcto:_ «Cada color de la paleta figura en el registro de
      decisiones con el dato del cliente del que sale.»
- **B6.** Cero supuestos:
    - Toda condición, límite y excepción se escribe.
    - Ante un caso que la skill no regla, la skill manda a preguntar al
      usuario.
    - Donde la skill pide proponer opciones, la elección es del usuario.
- **B7.** Flujos: cada paso contiene una sola acción, y un flujo muestra de
  tres a cinco pasos. Si necesita más, se parte en subsecciones o baja a
  `references/`.
- **B8.** Realidad vigente:
    - Sin fechas, sin «por ahora» ni «migrando a».
    - Sin atribución de decisiones («se acordó en la reunión»).
    - El historial lo guarda git.
- **B9.** Repositorio público `*(Ver ADR-004)*`:
    - Sin precios ni montos.
    - Sin nombres ni datos de clientes.
    - Sin credenciales, teléfonos ni rutas absolutas locales.
- **B10.** Sin duplicados:
    - Cada tema vive en una sola skill; las demás enlazan su `SKILL.md`.
    - Dentro de una skill, cada regla tiene un solo lugar. Las demás
      menciones son un rótulo de una línea con el enlace.
    - Resumir con otras palabras una regla ya escrita cuenta como duplicado.
- **B11.** Cita de ADR: va en la línea de la regla, como `*(Ver ADR-NNN)*`.
  Nunca en un título.
- **B12.** Ejemplos:
    - Con marcadores `{entre-llaves}` y nombres ficticios.
    - La web de Respawn es el único proyecto real admitido como ejemplo.
- **B13.** Fuentes externas `*(Ver ADR-004)*`:
    - El material tomado de una fuente se reescribe con palabras propias.
    - La fuente figura en la tabla de fuentes de la skill.
- **B14.** IDs de reglas: un ID (`A1`, `B5`) aparece solo en la lista de
  verificación que lo define. Los demás archivos enuncian la regla o enlazan
  la lista.
- **B15.** Capa correcta: la skill no contiene nada que valga para un solo
  proyecto. Eso va al `.ai/` del proyecto `*(Ver ADR-001)*`.

## C. Cierre y versionado

- **C1.** Verificación: la lista se recorrió completa sobre lo que cambió y
  el resultado se informó al usuario `*(Ver ADR-010)*`.
- **C2.** Prueba de activación:
    - La skill tiene `evals/activacion.json` con al menos doce consultas, la
      mitad negativas.
    - Si cambió la `description` o se sumó una skill, la prueba se corrió
      según `pruebas-activacion.md`.
- **C3.** Versión: el cambio figura en `CHANGELOG.md`, bajo un número que
  sigue el criterio de mayor, menor y parche del cierre de la skill.
- **C4.** README: la tabla de skills del `README.md` coincide con las
  carpetas de `skills/`.
- **C5.** Disco: el resultado se comprobó leyendo los archivos, no por el
  mensaje de la herramienta.
- **C6.** Sin commit ni subida de cambios sin pedido del usuario.
- **C7.** Resumen de decisiones: cada ADR del índice figura en
  `DECISIONES.md` con su enlace, y cada línea dice la decisión vigente.

## D. ADRs

- **D1.** Una decisión por ADR.
- **D2.** Nombre y edición:
    - `ADR-NNN-descripcion-kebab-case.md`, con número secuencial que no se
      reúsa.
    - Un cambio de decisión reescribe el archivo. Sin estado, fecha ni
      historial.
- **D3.** Consenso previo: el agente redacta un ADR recién después del
  acuerdo explícito del usuario.
- **D4.** Sincronía: la regla que el ADR respalda lleva su cita en la skill,
  y cambia en el mismo cambio que el ADR.
- **D5.** Índice: el ADR figura en `adrs/README.md` con el mismo título que
  su H1.
- **D6.** Porqué, no cómo:
    - Contexto, alternativas, decisión y consecuencias.
    - Sin bloques de código ni pasos de implementación: la mecánica vive en
      la skill dueña.
    - La Decisión entra en 25 líneas.
- **D7.** Capa: los ADRs de la casa se numeran `ADR-NNN`. Un `ADR-P-NNN` es
  de un proyecto y no vive en este repositorio `*(Ver ADR-009)*`.
