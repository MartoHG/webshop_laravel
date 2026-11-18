---
name: review-respawn
description: >
    Audita un proyecto Respawn completo antes de entregarlo: recorre las
    listas de verificación de las demás skills de la casa mirando cada página
    en el navegador, mide con Lighthouse, revisa lo legal de los sitios que
    cobran y deja el informe, con el sello si no hay bloqueantes. Usar siempre
    que el usuario pida "auditá el sitio antes de entregarlo", "¿está listo
    para entregar?", "pasale la revisión final", "medí Lighthouse en todas las
    páginas", "¿cumple con el botón de arrepentimiento?", "¿le damos el
    sello?" o el informe de entrega para el cliente. No usar para diseñar o
    corregir una pantalla (es de respawn-design), para arreglar lo técnico de
    una página (respawn-web), para construir un sistema o agregarle los
    botones legales a una tienda (respawn-sistemas), para registrar deuda o
    decisiones (respawn-proyecto) ni para auditar las skills de la casa
    (respawn-skill-creator).
---

# Review Respawn — la auditoría de entrega

## Contenido

0. Convenciones
1. Pedido y alcance
2. Qué se recorre
3. Flujo de la auditoría
4. Mirar y medir
5. Sitios que cobran
6. Resultado y sello
7. Después de la auditoría
8. Antipatrones
9. Fuentes de esta skill
10. Referencias

---

## 0. Convenciones

Esta skill define cómo se audita un proyecto antes de entregarlo y qué le da
el sello `*(Ver ADR-002)*`. Las reglas que se auditan viven en las listas de
verificación de cada skill dueña: la auditoría las recorre, no las reescribe.

- **Reglas propias:** solo las mediciones, lo legal de los sitios que cobran,
  el alcance y el informe. Viven en
  [references/checklist.md](references/checklist.md).
- **Solo lectura:** la auditoría no toca código, contenido ni `.ai/` hasta
  que el usuario aprueba el plan de correcciones. Los únicos archivos que
  escribe son los informes.
- **Se mira y se mide:** toda regla que se ve en la pantalla se comprueba en
  el navegador. Leer el código no alcanza para darla por cumplida.
- **Cada resultado tiene evidencia:** dónde está y qué se observó. Lo que no
  se pudo comprobar se informa como «sin comprobar», nunca como cumplido ni
  como incumplido.
- **Lo legal no reemplaza a un abogado:** el informe para el cliente lo dice.
- **Ante un caso que ninguna lista regla:** preguntar al usuario.

---

## 1. Pedido y alcance

El objeto de la auditoría es un proyecto Respawn completo: su repositorio y
el sitio publicado. Si el `CLAUDE.md` de la carpeta no abre con
`Proyecto Respawn`, no se audita: se informa y se propone
[respawn-init](../respawn-init/SKILL.md).

El agente traduce el pedido a los parámetros de esta tabla, sin menú previo.
Antes de empezar anuncia el alcance en una línea: «Auditoría de {proyecto}
sobre {dirección}, paquete {paquete}, {n} páginas, {con o sin} backend.»

| Parámetro que el pedido no menciona | Valor por omisión                                                      |
| ----------------------------------- | ---------------------------------------------------------------------- |
| Proyecto                            | El de la carpeta de trabajo                                            |
| Dirección                           | El dominio de `entrega.md`; si no figura, se le pregunta al usuario    |
| Páginas                             | Todas las de `sitemap.xml` y la página de error                        |
| Paquete                             | El del brief                                                           |
| Backend                             | El que declara el `ADR-P-001` del proyecto                             |
| Slop Score                          | Se le pide al usuario; sin dato, el informe dice «no medido»           |

- **La capa proyecto** se lee de `.ai/` o de la ruta que declara el
  `CLAUDE.md` del proyecto.
- **Las listas se leen de la copia del proyecto**, en `.claude/skills/`: el
  proyecto se audita contra la versión de la casa que declara su `CLAUDE.md`.
- **Toda auditoría es completa.** Una corrección se comprueba con una
  auditoría nueva, no revisando solo lo que falló.
- **El sello se da sobre el dominio definitivo.** Una auditoría sobre una
  vista previa sirve para corregir, pero lo que solo se ve en producción
  queda sin comprobar `*(Ver ADR-014)*`.

---

## 2. Qué se recorre

| Área              | Lista que se recorre                                         | Cuándo aplica                        | Cómo se comprueba                                  |
| ----------------- | ------------------------------------------------------------ | ------------------------------------ | -------------------------------------------------- |
| Capa proyecto     | [respawn-proyecto](../respawn-proyecto/SKILL.md)             | Siempre                              | Leyendo el `CLAUDE.md` y la capa proyecto          |
| Relevamiento      | [respawn-kickoff](../respawn-kickoff/SKILL.md)               | Siempre                              | Leyendo el brief, el registro y los pendientes     |
| Diseño y textos   | [respawn-design](../respawn-design/SKILL.md)                 | Siempre                              | Mirando cada página, con la pasada de slop         |
| Sitio público     | [respawn-web](../respawn-web/SKILL.md)                       | Siempre                              | Leyendo el HTML que llega y mirando cada página    |
| Backend y panel   | [respawn-sistemas](../respawn-sistemas/SKILL.md)             | Si el proyecto tiene backend         | Leyendo el código y usando el panel                |
| Mediciones        | Propia                                                       | Siempre                              | Lighthouse en celular y links de cada página       |
| Sitios que cobran | Propia                                                       | Si el sitio cobra en línea           | Mirando el sitio y con una compra de prueba        |
| Alcance           | Propia                                                       | Siempre                              | Comparando el brief con el sitio                   |

- **Qué es bloqueante** lo declara cada lista en su sección «Uso». La
  auditoría no cambia esa clasificación.
- **Los entregables al cliente no tienen lista propia:** los datos de entrega
  son de la lista de respawn-proyecto; el manual del panel y los backups, de
  la de respawn-sistemas.

---

## 3. Flujo de la auditoría

1. **Preparar:** leer el `CLAUDE.md`, el brief, el `ADR-P-001` y
   `sitemap.xml`; resolver los parámetros y anunciar el alcance.
2. **Leer:** recorrer las listas que se comprueban leyendo, sobre la capa
   proyecto y sobre el código.
3. **Mirar y medir:** recorrer cada página en el navegador según la sección
   siguiente, y resolver ahí las reglas que se ven.
4. **Informar:** escribir el informe con la plantilla y presentar el resumen
   en la conversación.
5. **Esperar** la decisión del usuario sobre el plan de correcciones.

- El informe va en `.ai/auditoria.md`, o en la ruta que declara el
  `CLAUDE.md`. Se reescribe entero en cada auditoría; las anteriores las
  guarda git. Plantilla: [assets/informe.md](assets/informe.md).
- Una regla que se comprueba leyendo y también mirando, como el contraste o
  los textos alternativos, se da por cumplida recién después de mirarla.

---

## 4. Mirar y medir

El procedimiento completo está en
[references/navegador.md](references/navegador.md). Lo que no se negocia:

- **Se recorre cada página pública**, a 360 px y en escritorio.
- **La evidencia es la medición del DOM, no la captura.** La captura sirve
  para mirar el conjunto; ante una medición que la contradice, vale la
  medición.
- **Lighthouse lo corre el agente** si la máquina tiene Chrome. Si no, le
  pide al usuario el resultado de PageSpeed Insights de cada página.
- **El Slop Score lo mide el usuario** y se anota como dato con su fecha.
  Nunca aprueba ni bloquea por sí solo `*(Ver ADR-011)*`.

| Lo que se mide                     | Dónde está la regla        |
| ---------------------------------- | -------------------------- |
| Puntajes de Lighthouse, LCP y CLS  | Lista propia, Mediciones   |
| Links rotos                        | Lista propia, Mediciones   |
| Contraste, consola y desborde      | Lista de respawn-web       |
| Patrones del catálogo de slop      | Lista de respawn-design    |
| Uso del panel en el celular        | Lista de respawn-sistemas  |

---

## 5. Sitios que cobran

La auditoría revisa obligaciones legales solo en los sitios que cobran en
línea, cualquiera sea el paquete: una tienda, o un sistema que cobra una seña
con Mercado Pago `*(Ver ADR-015)*`. En los demás sitios, esta parte no aplica
y el informe lo dice.

- **Qué se exige:** el botón de arrepentimiento, el botón de baja si se vende
  un servicio que se paga en forma periódica, el aviso del plazo en la
  compra, los datos de atención y el logo de Data Fiscal. Las reglas, con la
  norma de cada una, están en la [lista propia](references/checklist.md).
- **Cómo se comprueba:** mirando el sitio publicado y con una compra de
  prueba, según [references/navegador.md](references/navegador.md).
- **Lo legal no se acepta como riesgo:** su efecto en el sello está en la
  sección siguiente.
- **Ante una norma que cambió** o un caso que la lista no cubre: informarlo
  como «sin comprobar» y consultar al usuario.

---

## 6. Resultado y sello

| Resultado de una regla | Marca | Cuándo                                                                          |
| ---------------------- | ----- | ------------------------------------------------------------------------------- |
| Cumple                 | ✅    | Se comprobó y cumple                                                            |
| Recomendación          | ⚠️    | No cumple una regla no bloqueante, o el valor está entre el umbral y el máximo  |
| Bloqueante             | ❌    | No cumple una regla bloqueante                                                  |
| Sin comprobar          | ❔    | No se pudo comprobar, con el motivo                                             |
| No aplica              | —     | La regla no corresponde al proyecto, con el motivo                              |

El resultado de la auditoría sale de esta tabla `*(Ver ADR-014)*`:

| Situación                                                                 | Resultado                    |
| ------------------------------------------------------------------------- | ---------------------------- |
| Una regla bloqueante quedó sin comprobar                                  | Incompleta: sin sello        |
| Hay un ❌ que ningún riesgo aceptado cubre, o un ❌ legal                  | Sin sello                    |
| Hay deuda de severidad alta abierta en el proyecto                        | Sin sello                    |
| Todo bloqueante cumple o está cubierto por un riesgo aceptado vigente     | Con sello                    |

- **Un riesgo aceptado cubre un ❌** si su decisión es del usuario, nombra esa
  regla y su condición de revisión no se cumplió. Un control legal nunca
  queda cubierto.
- **La deuda media y la baja** se informan como recomendaciones.
- **El dominio o el hosting a nombre del desarrollador** no impiden el sello:
  el informe para el cliente lo dice.

---

## 7. Después de la auditoría

1. **Presentar** en la conversación el resultado, la cantidad de cada marca,
   cada ❌ y cada ❔, y la ruta del informe. El informe no se copia entero.
2. **Proponer el plan de correcciones:** una línea por hallazgo, con la skill
   que corrige y la propuesta.
3. **Corregir** lo que el usuario aprueba, con la skill dueña de cada regla.
4. **Registrar como deuda** lo que el usuario pospone, con
   [respawn-proyecto](../respawn-proyecto/SKILL.md). Un bloqueante pospuesto
   es deuda de severidad alta `*(Ver ADR-013)*`.

**Con sello:**

- Escribir el informe para el cliente en `.ai/auditoria-cliente.md`, con
  [assets/informe-cliente.md](assets/informe-cliente.md).
- Completar la sección Auditoría de `entrega.md` con la ruta del informe y
  el resultado.
- Ninguna web de un cliente lleva un crédito ni un link a Respawn
  `*(Ver ADR-002)*`.

---

## 8. Antipatrones

| Antipatrón                                                          | Resolución                                                           |
| ------------------------------------------------------------------- | -------------------------------------------------------------------- |
| Dar por cumplida una regla visual leyendo solo el código            | Mirarla en el navegador, a 360 px y en escritorio                    |
| Medir solo la portada, o solo en escritorio                         | Medir cada página pública en modo celular                            |
| Usar una captura como prueba de una medida                          | Medir el DOM y anotar el valor                                       |
| Completar un puntaje de Lighthouse que nadie midió                  | Dejarlo sin comprobar y pedírselo al usuario                         |
| Anotar como recomendación una regla que su lista declara bloqueante | Usar la clasificación de la lista dueña                              |
| Dar el sello con un bloqueante sin comprobar                        | Resultado «Incompleta»                                               |
| Cubrir un control legal con un riesgo aceptado                      | Corregirlo: lo legal no se acepta como riesgo                        |
| Corregir durante la auditoría                                       | Informar, proponer el plan y esperar la aprobación                   |
| Reescribir en el informe el texto de una regla de otra skill        | Citar la skill y el identificador de la regla                        |
| Dar el Slop Score como aprobación o como rechazo                    | Anotarlo como dato, con su fecha                                     |
| Hacer una compra real para probar el botón de arrepentimiento       | Compra de prueba en el entorno de prueba                             |
| Pegar el informe completo en la conversación                        | Resumen y ruta del archivo                                           |
| Informe para el cliente con identificadores o jerga                 | Contar qué se revisó y qué tiene que hacer el cliente, en palabras   |

---

## 9. Fuentes de esta skill

| Fuente                                                                                                                                  | Qué aportó                                                                                       |
| --------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------ |
| Sistema de skills de Senda (laravel-htmx-patterns)                                                                                      | Pedido en lenguaje natural con valores por omisión, anuncio en una línea, evidencia por hallazgo y medición por el DOM |
| [Disposición 954/2025](https://www.boletinoficial.gob.ar/detalleAviso/primera/330827/20250904), Subsecretaría de Defensa del Consumidor | Botones de arrepentimiento y de baja, código en 24 horas, atención al consumidor y excepciones   |
| [Disposición 3/2026](https://www.boletinoficial.gob.ar/detalleAviso/primera/338248/20260206), Dirección Nacional de Defensa del Consumidor | Verificación de identidad al usar los botones                                                  |
| [Ley 24.240](https://servicios.infoleg.gob.ar/infolegInternet/anexos/0-4999/638/texact.htm) de Defensa del Consumidor                    | Plazo de diez días y aviso en la venta (art. 34); constancia de baja en 72 horas (art. 10 ter)   |
| Código Civil y Comercial de la Nación, art. 1116                                                                                         | Productos exceptuados del derecho a revocar                                                      |
| [ARCA, formulario 960/D](https://www.arca.gob.ar/960/formulario-960/obligados.asp)                                                       | Logo de Data Fiscal en la portada de los sitios que venden                                       |

---

## 10. Referencias

| Archivo                                                          | Contenido                                                                       |
| ---------------------------------------------------------------- | ------------------------------------------------------------------------------- |
| [references/checklist.md](references/checklist.md)               | Reglas propias: mediciones, sitios que cobran, alcance e informe                |
| [references/navegador.md](references/navegador.md)               | Cómo se recorre cada página, cómo se mide y qué hacer con los links             |
| [assets/informe.md](assets/informe.md)                           | Plantilla del informe de auditoría                                              |
| [assets/informe-cliente.md](assets/informe-cliente.md)           | Plantilla del informe para el cliente                                           |
| [evals/activacion.json](evals/activacion.json)                   | Consultas de activación de esta skill                                           |
| [../respawn-proyecto/SKILL.md](../respawn-proyecto/SKILL.md)     | Capa proyecto, inventarios y datos de entrega                                   |
| [../respawn-kickoff/SKILL.md](../respawn-kickoff/SKILL.md)       | Brief y alcance                                                                 |
| [../respawn-design/SKILL.md](../respawn-design/SKILL.md)         | Diseño, textos y catálogo de slop                                               |
| [../respawn-web/SKILL.md](../respawn-web/SKILL.md)               | Reglas técnicas de todo sitio                                                   |
| [../respawn-sistemas/SKILL.md](../respawn-sistemas/SKILL.md)     | Backend, panel, tiendas y operación                                             |
| [../respawn-init/SKILL.md](../respawn-init/SKILL.md)             | Inicialización y actualización de un proyecto                                   |
