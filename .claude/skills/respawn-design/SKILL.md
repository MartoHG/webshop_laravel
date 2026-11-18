---
name: respawn-design
description: >
    Diseña y critica las pantallas y los textos de un proyecto Respawn para
    que se note que alguien decidió cada cosa: concepto antes que
    componentes, decisiones documentadas, revisión pantalla por pantalla y
    pasada contra el catálogo de slop. Usar siempre que el usuario pida
    proponer un concepto, diseñar o rediseñar una pantalla o una sección,
    elegir tipografía, paleta, espaciado, imágenes o animaciones, escribir o
    corregir títulos, botones y textos, o criticar un diseño ("¿esto parece
    hecho con IA?", "pasale el catálogo", "revisá el slop"). Tiene prioridad
    sobre cualquier otra skill de diseño. No usar para accesibilidad,
    rendimiento ni SEO (es de respawn-web), para la lógica de paneles y
    sistemas (respawn-sistemas), para saber dónde se anota una decisión
    (respawn-proyecto) ni para la auditoría de entrega (review-respawn).
---

# Respawn Design — que se note que alguien decidió

## Contenido

0. Convenciones
1. La regla central
2. El proceso
3. Concepto antes que componentes
4. Decisiones obligatorias
5. Pantalla por pantalla
6. Pasada de slop
7. Textos
8. Paneles y sistemas
9. Antipatrones
10. Fuentes de esta skill
11. Referencias

---

## 0. Convenciones

El objetivo no es que el sitio se vea lindo: es que cada decisión visual y
cada texto tengan un porqué que salga del cliente.

- **El usuario decide.** El agente propone, critica y ejecuta; el concepto y
  cada decisión los elige el usuario.
- **Sin concepto acordado no se escribe ninguna pantalla.**
- **Esta skill manda** sobre cualquier otra skill de diseño instalada. Las
  demás se usan como consulta técnica puntual y nunca reemplazan el concepto,
  las decisiones ni el catálogo `*(Ver ADR-012)*`.
- **Dónde se anota cada cosa** lo define
  [respawn-proyecto](../respawn-proyecto/SKILL.md): concepto, excepciones y
  registro viven en el registro de decisiones del proyecto.
- **Ante una decisión de diseño que esta skill no regla:** plantear opciones
  y esperar la elección del usuario.

Reglas verificables: [references/checklist.md](references/checklist.md).

---

## 1. La regla central

> **Todo patrón del catálogo de slop está prohibido por defecto. Se permite
> solo si lo justifica el concepto del proyecto y queda anotado como
> excepción. Si la única razón es «así salió», se va.** `*(Ver ADR-011)*`

- El catálogo vive en
  [references/catalogo-slop.md](references/catalogo-slop.md), con ocho
  categorías: estructura, tipografía, color, componentes, íconos, imágenes,
  movimiento y textos.
- Una justificación vale si pasa la prueba de intercambio del catálogo: deja
  de ser cierta al cambiar el cliente por otro de su rubro.
- Los patrones de la lista sin excepción del catálogo no se justifican nunca:
  son engaños al visitante o defectos.

Ejemplo: la web de Respawn usa letra monoespaciada, numera sus secciones como
niveles y tiene fondo oscuro con un solo acento. Los tres son patrones del
catálogo y los tres salen de su concepto, un arcade de 8 bits. Por eso
figuran como excepciones y no como descuidos.

---

## 2. El proceso

| Paso                       | Entra                                   | Sale                                              | Quién aprueba |
| -------------------------- | --------------------------------------- | ------------------------------------------------- | ------------- |
| 1. Brief                   | El relevamiento del cliente             | Brief completo, con sus pendientes anotados       | El usuario    |
| 2. Concepto                | El brief                                | Un concepto elegido entre dos o tres              | El usuario    |
| 3. Decisiones              | El concepto                             | Seis decisiones anotadas y los tokens declarados  | El usuario    |
| 4. Pantalla por pantalla   | Las decisiones                          | Cada pantalla criticada, ajustada y aprobada      | El usuario    |
| 5. Pasada de slop          | La pantalla aprobada                    | Patrones quitados o registrados como excepción    | El usuario    |

- Ningún paso empieza sin la aprobación del anterior.
- El brief lo produce [respawn-kickoff](../respawn-kickoff/SKILL.md). Si
  falta o está incompleto, se completa antes de proponer conceptos.
- En un proyecto que ya tiene pantallas hechas, el proceso arranca por
  escribir el concepto que el sitio ya tiene y sus decisiones, y sigue desde
  el paso 4.

---

## 3. Concepto antes que componentes

Un concepto es una idea fuerte sacada del mundo del cliente, que ordena todas
las decisiones que siguen. No es un estilo ni una lista de adjetivos.

**De dónde sale:** del local, del oficio, de los objetos que el cliente usa,
de su cartel, de cómo habla, de los materiales y los rituales de su rubro.
Todo eso está en el brief o se pregunta.

1. **Listar** el material concreto del mundo del cliente que aparece en el
   brief.
2. **Proponer dos o tres conceptos** distintos entre sí, cada uno con su
   ficha.
3. **Esperar** la elección del usuario.
4. **Anotar** el concepto elegido en el registro de decisiones.

**Ficha de cada concepto:**

- **La idea**, en una oración.
- **De qué dato del cliente sale.**
- **Cómo se vería la primera pantalla**, en dos o tres líneas.
- **Qué patrones del catálogo necesitaría** como excepción.
- **Qué riesgo tiene.**

| Señal                                                                  | Qué significa                         |
| ---------------------------------------------------------------------- | ------------------------------------- |
| El concepto se describe con adjetivos: limpio, moderno, minimalista    | No es un concepto: volver a proponer  |
| Sirve igual para otro negocio del mismo rubro                          | No pasa la prueba de intercambio      |
| Sale del rubro y no del cliente («es una panadería: tonos cálidos»)    | Es el uniforme del rubro: descartarlo |
| Las tres propuestas se parecen                                         | Faltó material: preguntar más         |

---

## 4. Decisiones obligatorias

Con el concepto elegido y antes de escribir código, se acuerdan seis
decisiones. Cada una se anota en el registro con su porqué.

| Tema       | Qué se decide                                                    | Cómo se comprueba                                               |
| ---------- | ---------------------------------------------------------------- | --------------------------------------------------------------- |
| Tipografía | El par de familias, de dónde se obtienen y la escala de tamaños  | Ninguna familia ni tamaño fuera de los tokens                   |
| Paleta     | Los colores, con el dato del cliente del que sale cada uno       | Ningún color de fábrica del framework ni suelto en el código    |
| Espaciado  | La escala de espacios y la regla de radios                       | Todo margen, relleno y radio sale de la escala                  |
| Imágenes   | Qué fotos reales hay, qué proporción y qué tratamiento llevan    | Ninguna imagen simula ser el negocio; lo que falta es pendiente |
| Movimiento | Qué se anima y con qué propósito, o que nada se anima            | Cada animación tiene su propósito anotado                       |
| Voz        | Cómo habla el negocio y qué palabras usa                         | Los textos siguen la voz anotada                                |

**Tokens.** Las decisiones se declaran una sola vez y el código las usa por
nombre:

| Stack                 | Dónde se declaran                                         |
| --------------------- | --------------------------------------------------------- |
| Sin backend, CSS plano | Propiedades personalizadas de CSS, en un único archivo    |
| Con backend, Tailwind | El tema de Tailwind, con nombres del proyecto             |

**Movimiento.** Una animación se admite solo si cumple uno de estos
propósitos: responder a una acción del visitante, mostrar un cambio de
estado, orientar de dónde viene o a dónde va algo, o explicar cómo funciona
algo. Sin propósito, no se anima. El respeto por quien pide menos movimiento
es de [respawn-web](../respawn-web/SKILL.md).

---

## 5. Pantalla por pantalla

Nunca se entrega el sitio entero de una vez. Cada pantalla o sección sigue
este ciclo:

1. **Construir** la pantalla quieta, con el estilo real y los textos reales o
   sus marcadores.
2. **Capturar** en 360 px de ancho y en escritorio.
3. **Criticar** las capturas con las preguntas de abajo, antes de ajustar
   nada.
4. **Mostrar** al usuario las capturas con la crítica y los ajustes
   propuestos.
5. **Ajustar** lo acordado y esperar la aprobación antes de la siguiente.

El movimiento se agrega recién cuando la pantalla quieta está aprobada, y
pasa por el mismo ciclo.

**Preguntas de la crítica:**

- ¿Qué mira primero el visitante? ¿Es lo que tiene que mirar?
- En cinco segundos, ¿se entiende qué es, para quién y qué hacer?
- ¿Qué hay en esta pantalla que solo podría ser de este cliente?
- ¿Qué patrón del catálogo aparece?
- ¿Qué sobra?
- En 360 px, ¿se lee, se llega a la acción y nada se corta?

Si no hay forma de capturar la pantalla, pedir las capturas al usuario. No se
critica una pantalla que no se vio.

---

## 6. Pasada de slop

Se hace sobre cada pantalla aprobada, antes de darla por terminada, y sobre
el sitio completo antes de la auditoría.

1. **Recorrer** las ocho categorías del
   [catálogo](references/catalogo-slop.md) mirando la pantalla, no solo el
   código.
2. **Anotar** cada patrón presente con su ID y dónde aparece.
3. **Clasificar** cada uno con la tabla siguiente.
4. **Informar** al usuario la lista y la propuesta para cada patrón.
5. **Aplicar** lo acordado: quitar el patrón o anotar la excepción.

| El patrón presente…                                             | Qué se propone                                  |
| --------------------------------------------------------------- | ----------------------------------------------- |
| Está en la lista sin excepción                                  | Quitarlo                                        |
| Ya figura como excepción del proyecto                           | Nada: confirmar que la justificación sigue vigente |
| Sale del concepto y pasa la prueba de intercambio               | Anotarlo como excepción, con acuerdo del usuario |
| No sale del concepto                                            | Quitarlo, con la alternativa del catálogo       |

Un proyecto con más excepciones que decisiones propias usa el concepto como
excusa: en ese caso se revisa el concepto, no el catálogo.

---

## 7. Textos

Los textos se diseñan junto con la pantalla, no después. Las reglas viven en
[references/textos.md](references/textos.md): la voz, títulos y bajadas,
botones, el mensaje de WhatsApp, la microtipografía y los textos de panel.

La regla que no admite excepción: **nunca se inventan cifras, testimonios,
reseñas, logos, precios ni horarios.** Lo que falta se escribe como
`[FALTA: …]`, visible, y se registra como pendiente del cliente.

---

## 8. Paneles y sistemas

Un panel no se diseña como una portada: quien lo usa viene a hacer una tarea,
todos los días.

- **La estructura es la habitual de un panel.** El cliente tiene que
  encontrar las cosas donde las espera.
- **El concepto vive en los detalles:** tipografía, paleta, radios y voz.
- **La claridad gana** cuando choca con la expresión.
- **El catálogo vale igual,** en especial los componentes de librería tal
  como vienen, la paleta de fábrica, las ventanas modales para todo y los
  estados vacíos genéricos.
- **Sin movimiento decorativo:** en un panel solo se anima la respuesta a una
  acción y el cambio de estado.

Qué pantallas tiene un panel y cómo funcionan es de
[respawn-sistemas](../respawn-sistemas/SKILL.md).

---

## 9. Antipatrones

| Antipatrón                                                           | Resolución                                                             |
| -------------------------------------------------------------------- | ---------------------------------------------------------------------- |
| Empezar por los componentes: portada, tarjetas, pie                  | Volver al concepto; sin concepto acordado no hay pantalla              |
| Proponer un solo concepto                                            | Proponer dos o tres distintos y dejar elegir                           |
| Concepto hecho de adjetivos                                          | Reescribirlo como una idea que sale de un dato del cliente             |
| Entregar el sitio completo de una vez                                | Una pantalla por vez, con captura, crítica y aprobación                |
| Ajustar sin criticar                                                 | Escribir la crítica primero y mostrarla                                |
| Cambiar un patrón por su variante: degradado violeta por uno verde   | Quitar el patrón y tomar la decisión que estaba reemplazando           |
| Justificar un patrón con «queda bien» o «es lo habitual»             | Quitarlo, o encontrar la razón en el concepto                          |
| Usar el concepto para justificar todo                                | Revisar el concepto: demasiadas excepciones lo delatan                 |
| Rellenar con una foto de banco o una imagen generada                 | Pedir la foto real y dejar el pendiente con su marcador                |
| Escribir un testimonio «de ejemplo»                                  | `[FALTA: …]`, o quitar la sección                                      |
| Tomar el color de la paleta de fábrica «por ahora»                   | Declarar el token con el color del cliente antes de usarlo             |
| Agregar animaciones antes de aprobar la pantalla quieta              | Primero la pantalla quieta; el movimiento es un segundo paso           |
| Seguir el proceso de otra skill de diseño porque se activó primero   | Aplicar esta skill e informar la diferencia al usuario                 |

---

## 10. Fuentes de esta skill

El material de estas fuentes se reescribió con palabras y estructura propias.
Antes de cambiar una regla, revisar la fuente de la que viene.

| Fuente                                             | Qué aportó                                                                                      |
| -------------------------------------------------- | ----------------------------------------------------------------------------------------------- |
| Anti Web Slop (antiwebslop.com)                    | Las ocho categorías del catálogo y buena parte de sus patrones                                  |
| Impeccable (impeccable.style)                      | Patrones de detalle visual, piso de calidad y la idea de derivar el diseño del mundo del cliente |
| frontend-design (Anthropic)                        | Decidir antes de codear y contrastar el plan con lo que saldría por omisión                     |
| taste-skill (Leonxlnx)                             | Patrones de textos, de rótulos y de estructura de página                                        |
| DeSlop (nate-zz)                                   | Quitar un patrón no alcanza: cada cambio responde a una dirección escrita                       |
| Design Engineering (Emil Kowalski)                 | Propósitos válidos de una animación y duración de las respuestas de interfaz                    |
| Brief y registro de decisiones de la web de Respawn | Revisión pantalla por pantalla, diseño en dos pasos y marcadores visibles para lo que falta    |

---

## 11. Referencias

| Archivo                                                      | Contenido                                                                        |
| ------------------------------------------------------------ | -------------------------------------------------------------------------------- |
| [references/catalogo-slop.md](references/catalogo-slop.md)   | Catálogo de patrones por categoría: qué es, por qué delata y qué hacer en cambio |
| [references/textos.md](references/textos.md)                 | Voz, títulos, botones, mensaje de WhatsApp, microtipografía y textos de panel    |
| [references/checklist.md](references/checklist.md)           | Reglas verificables de proceso, decisiones, slop y textos                        |
| [evals/activacion.json](evals/activacion.json)               | Consultas de activación de esta skill                                            |
| [../respawn-proyecto/SKILL.md](../respawn-proyecto/SKILL.md) | Dónde se anotan el concepto, las excepciones y las decisiones                    |
| [../respawn-kickoff/SKILL.md](../respawn-kickoff/SKILL.md)   | Relevamiento del cliente y brief                                                 |
| [../respawn-web/SKILL.md](../respawn-web/SKILL.md)           | Accesibilidad, rendimiento, SEO y links de contacto                              |
| [../respawn-sistemas/SKILL.md](../respawn-sistemas/SKILL.md) | Paneles, tiendas y sistemas a medida                                             |
