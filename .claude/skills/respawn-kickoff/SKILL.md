---
name: respawn-kickoff
description: >
    Arranca un proyecto Respawn y cuida su alcance: releva al cliente con
    preguntas de a una, encuadra el pedido en un paquete vigente, fija por
    escrito qué entra y qué no, estima en horas un sistema a medida, elige el
    stack y dice qué skill sigue en cada etapa. Usar siempre que el usuario
    diga "tengo un cliente nuevo", "relevá este negocio", "armá el brief",
    "¿qué paquete le corresponde?", "¿cuántas horas lleva?", "¿qué le
    pregunto al cliente?", y cada vez que aparece un pedido nuevo durante el
    proyecto: "ahora quiere…", "¿esto entra?", "me pidió además…". No usar
    para crear las carpetas del proyecto (es de respawn-init), para proponer
    conceptos o diseñar (respawn-design), para registrar decisiones, deuda o
    estado (respawn-proyecto) ni para auditar la entrega (review-respawn).
---

# Respawn Kickoff — arrancar un proyecto y cuidar su alcance

## Contenido

0. Convenciones
1. Qué deja hecho
2. El relevamiento
3. Preguntas para el cliente
4. Encuadre en un paquete
5. Cuando el pedido se sale del paquete
6. Alcance por escrito
7. Estimación de un sistema a medida
8. Stack y cierre
9. Qué skill sigue en cada etapa
10. Antipatrones
11. Fuentes de esta skill
12. Referencias

---

## 0. Convenciones

Esta skill es la puerta de entrada de todo proyecto y la que vigila que el
trabajo no crezca sin acuerdo.

- **Los paquetes se leen, no se recuerdan.** La lista vigente, con lo que
  incluye cada uno, está en la web de Respawn:
  `https://respawninformatica.com.ar/paginas-web`. Se lee cada vez. Si no se
  puede leer, se le pide al usuario.
- **Sin precios en el repositorio.** Las horas van al brief; los importes los
  maneja el usuario fuera del proyecto `*(Ver ADR-004)*`.
- **No se inventa nada del cliente.** Lo que no se sabe queda como
  `[FALTA: …]` y como pendiente.
- **El formato del brief** y de los demás archivos es de
  [respawn-proyecto](../respawn-proyecto/SKILL.md).
- **El proyecto ya está inicializado.** Si falta el `.ai/`, primero corre
  [respawn-init](../respawn-init/SKILL.md).
- **Ante un pedido que no encaja en ningún paquete:** decirlo y preguntar al
  usuario cómo lo quiere encuadrar.

Reglas verificables: [references/checklist.md](references/checklist.md).

---

## 1. Qué deja hecho

| Entregable                            | Dónde queda                                    |
| ------------------------------------- | ---------------------------------------------- |
| Brief completo, o con sus faltantes marcados | `brief.md`                              |
| Paquete encuadrado, con lo que incluye y lo que no | `brief.md`, Paquete y alcance     |
| Tareas del cliente y su frecuencia    | `brief.md`, Quién actualiza el contenido       |
| Estimación por módulos, si es un sistema | `brief.md`, Estimación                      |
| Stack elegido                         | `adrs/ADR-P-001-stack.md`                      |
| Datos del proyecto y skills que aplican | `CLAUDE.md`                                  |
| Lo que falta del cliente              | `pendientes-cliente.md`                        |
| Etapa y próximo paso                  | `estado.md`                                    |
| Texto del alcance para mandarle al cliente | Se entrega en la respuesta                |

---

## 2. El relevamiento

Es una entrevista, no un formulario. Tiene cuatro reglas:

- **Una pregunta por vez.** Se espera la respuesta antes de la siguiente.
- **Cada pregunta trae una lectura propuesta.** El agente dice qué cree y por
  qué, y el usuario confirma o corrige: «Mi lectura: llegan desde Instagram y
  quieren saber precio y turno. ¿Es así?».
- **No se pregunta lo que se puede averiguar.** Antes de preguntar se mira lo
  que hay: la web actual del negocio, sus redes, su ficha en mapas y el
  material que haya en el proyecto.
- **Cada respuesta abre o cierra ramas.** Si hay panel, se pregunta quién lo
  usa y cada cuánto; si se cobra en línea, se pregunta por la cuenta de
  Mercado Pago.

**Cómo corre:**

1. **Mirar** lo que ya existe del negocio y anotar lo que se averiguó.
2. **Recorrer** los bloques del árbol en orden, salteando lo ya sabido.
3. **Marcar** en el momento todo lo que cruza el límite de un paquete.
4. **Cerrar** cuando cada campo del brief tiene su dato o su `[FALTA: …]`.
5. **Mostrar** el brief armado y pedir la confirmación del usuario.

| Bloque                  | Qué resuelve                                                   |
| ----------------------- | -------------------------------------------------------------- |
| El negocio              | Qué hace, dónde y qué lo distingue                             |
| El visitante            | Quién entra, desde dónde, qué quiere saber y qué lo frena      |
| La acción objetivo      | Qué tiene que hacer el visitante y por qué canal               |
| El material real        | Qué logo, fotos, textos y productos existen                    |
| Quién actualiza         | Qué va a cambiar, quién lo cambia y cada cuánto                |
| Plazos                  | Para cuándo y de qué depende                                   |

- Si el usuario no sabe una respuesta, es un faltante: no se insiste ni se
  completa con una suposición.
- El relevamiento no propone diseño. Una idea visual que surja se anota para
  [respawn-design](../respawn-design/SKILL.md).

Árbol completo, con las ramas y de dónde se averigua cada dato:
[references/relevamiento.md](references/relevamiento.md).

---

## 3. Preguntas para el cliente

Muchas respuestas las tiene el cliente y no el usuario. Con lo que quedó como
faltante se arma la lista para llevarle.

- **Corta:** hasta diez preguntas. Si hay más, van primero las que frenan el
  trabajo.
- **En palabras del cliente:** sin términos técnicos ni nombres de paquetes.
- **Cada pregunta se puede contestar por mensaje,** con una frase o una foto.
- **Los pedidos de material van aparte** de las preguntas: logo, fotos,
  textos y accesos.

Al volver con las respuestas, el relevamiento sigue desde donde quedó y los
pendientes resueltos se borran. Plantilla:
[assets/preguntas-cliente.md](assets/preguntas-cliente.md).

---

## 4. Encuadre en un paquete

1. **Leer** los paquetes vigentes en la web de Respawn.
2. **Ubicar** el pedido con las preguntas de la tabla, en orden.
3. **Contrastar** cada cosa pedida con lo que el paquete incluye según la
   web.
4. **Proponer** el paquete al usuario, con lo que queda adentro y lo que
   queda afuera.

| Pregunta                                                              | Si la respuesta es sí                           |
| --------------------------------------------------------------------- | ----------------------------------------------- |
| ¿El negocio necesita operar algo propio: turnos, stock, gestión?      | Sistema a medida, por horas                     |
| ¿Se cobra en línea?                                                   | Tienda                                          |
| ¿El cliente tiene que editar contenido sin ayuda?                     | Web con panel                                   |
| ¿Es más de una página, cada una con contenido propio?                 | Web de varias páginas                           |
| ¿Es una sola página de presentación con contacto?                     | Landing page                                    |

- Se toma la primera fila que da sí.
- Los nombres de la tabla describen el tipo de trabajo. El nombre comercial y
  lo que incluye salen de la web.
- El mantenimiento mensual y el soporte para oficinas son servicios aparte:
  se ofrecen, no se dan por incluidos.

---

## 5. Cuando el pedido se sale del paquete

Un pedido se sale cuando necesita algo que el paquete acordado no incluye. Se
detecta en el momento en que aparece, en el relevamiento o a mitad del
proyecto.

| Señal en lo que pide el cliente                                         | Qué cruza                                        |
| ----------------------------------------------------------------------- | ------------------------------------------------ |
| «Quiero cambiar los textos y los precios yo»                            | Necesita panel: deja de ser un sitio sin backend |
| «Que la gente saque turno» o «que reserve»                              | Es un sistema, aunque parezca un formulario      |
| «Que se pueda pagar» o «un carrito»                                     | Es una tienda                                    |
| «Un formulario que me llegue por correo» o «que guarde los datos»       | Necesita backend                                 |
| «Una sección de novedades que cargo yo»                                 | Necesita panel                                   |
| «Otra página para cada servicio», más allá de las acordadas             | Supera la cantidad de páginas del paquete        |
| «Que cada empleado entre con su usuario»                                | Roles: es un sistema                             |
| «Que se conecte con» otro sistema                                       | Integración: se estima aparte                    |
| «Cargame vos los doscientos productos»                                  | Carga de contenido: se estima aparte             |
| «Después me lo vas actualizando»                                        | Es mantenimiento mensual                         |

**Qué se hace al detectarlo:**

1. **Frenar** antes de escribir código para ese pedido.
2. **Decirlo con claridad:** qué se pidió, por qué no entra y a qué tipo de
   trabajo corresponde. Ejemplo: «Un sistema de turnos no entra en una
   landing: necesita backend, panel y base de datos. Es un sistema a medida».
3. **Proponer las salidas** de la tabla siguiente.
4. **Esperar** la decisión del usuario.
5. **Anotar** el resultado en el brief y en el registro de decisiones.

| Salida                         | Cuándo conviene                                                    |
| ------------------------------ | ------------------------------------------------------------------ |
| Cambiar de paquete             | El pedido es el corazón de lo que el cliente necesita              |
| Sumarlo como etapa posterior   | Se puede entregar lo acordado y agregar esto después               |
| Resolverlo con algo más chico  | Lo que el cliente quiere lograr se cubre sin salir del paquete     |
| Dejarlo afuera                 | No es necesario; se anota en «No incluye»                          |

- Un cambio de paquete puede cambiar el stack. En ese caso se reescribe el
  `ADR-P-001`, con la decisión explícita del usuario.
- El agente nunca absorbe en silencio un pedido que se sale, aunque sea
  chico.
- Tampoco lo rechaza por su cuenta: la decisión es del usuario.

---

## 6. Alcance por escrito

El alcance evita la discusión del final. Queda en el brief y se le manda al
cliente con sus palabras.

- **Incluye:** cada página, sección y función acordada, nombrada una por una.
- **No incluye:** todo lo que se habló y quedó afuera, también nombrado.
- **Dominio y hosting:** siempre aparte del paquete. Se anota quién los
  contrata y a nombre de quién.
- **Material:** qué entrega el cliente y para cuándo. Sin material no hay
  fecha de entrega.
- **Mantenimiento:** si se contrata, qué cubre según la web. Si no, qué pasa
  con los cambios después de entregar.
- **Rondas de cambios:** cuántas tiene el trabajo antes de la entrega. Si el
  usuario no lo definió, se pregunta.

Plantilla del texto para el cliente:
[assets/alcance-cliente.md](assets/alcance-cliente.md).

---

## 7. Estimación de un sistema a medida

Un sistema se presupuesta por horas. La estimación es una tabla de módulos
con sus horas, que el usuario multiplica por su valor hora.

1. **Listar los módulos** a partir de las tareas del relevamiento.
2. **Sumar las partidas que no son módulos** y siempre están.
3. **Proponer las horas** de cada fila, con el supuesto que las sostiene.
4. **Ajustar** con el usuario, fila por fila.
5. **Anotar** la tabla en el brief, sin importes.

- Cada fila dice qué hace el módulo y qué se dio por supuesto.
- Una fila que no se puede estimar porque falta un dato lleva
  `[FALTA: …]` y no un número.
- El agente propone; las horas las decide el usuario.

Cómo se desglosa, qué partidas se olvidan y cómo se escriben los supuestos:
[references/estimacion.md](references/estimacion.md).

---

## 8. Stack y cierre

El stack sale de una sola pregunta: si el proyecto lleva backend
`*(Ver ADR-003)*`.

| El proyecto…                                         | Stack                                  |
| ---------------------------------------------------- | -------------------------------------- |
| No tiene panel, base de datos ni cobro               | Astro y CSS plano                      |
| Tiene panel, tienda o sistema                        | Laravel, Blade, Tailwind y HTMX        |
| Necesita otra cosa                                   | Se plantea al usuario como excepción   |

**Para cerrar el kickoff:**

1. **Proponer** el stack y, con el sí del usuario, completar el `ADR-P-001`.
2. **Completar** el `CLAUDE.md`: cliente, paquete, stack, acción objetivo y
   la tabla de skills que aplican.
3. **Registrar** los faltantes como pendientes del cliente.
4. **Actualizar** el estado: etapa Diseño y próximo paso.
5. **Entregar** el texto del alcance y la lista de preguntas para el cliente.

---

## 9. Qué skill sigue en cada etapa

| Etapa         | Skill                                                                                           | Qué sale                                     |
| ------------- | ----------------------------------------------------------------------------------------------- | -------------------------------------------- |
| Inicio        | [respawn-init](../respawn-init/SKILL.md)                                                        | Carpetas del proyecto y copia de las skills  |
| Relevamiento  | Esta skill                                                                                      | Brief, paquete, alcance y stack              |
| Diseño        | [respawn-design](../respawn-design/SKILL.md)                                                    | Concepto, decisiones y pantallas aprobadas   |
| Desarrollo    | [respawn-web](../respawn-web/SKILL.md) y, con backend, [respawn-sistemas](../respawn-sistemas/SKILL.md) | El sitio o el sistema funcionando    |
| Auditoría     | [review-respawn](../review-respawn/SKILL.md)                                                    | Informe de entrega                           |
| Todo el proyecto | [respawn-proyecto](../respawn-proyecto/SKILL.md)                                             | Decisiones, deuda, pendientes y estado       |

Esta skill vuelve a entrar en cualquier etapa cuando aparece un pedido nuevo.

---

## 10. Antipatrones

| Antipatrón                                                               | Resolución                                                             |
| ------------------------------------------------------------------------ | ---------------------------------------------------------------------- |
| Mandar al usuario una lista de veinte preguntas                          | Una por vez, con una lectura propuesta                                 |
| Preguntar el rubro, la dirección o el horario que están en las redes     | Mirar primero y preguntar solo lo que no se encontró                   |
| Completar el brief con lo que «seguramente» es                           | `[FALTA: …]` y pendiente del cliente                                   |
| Usar el precio o lo que incluye un paquete de memoria                    | Leer la web de Respawn                                                 |
| Escribir un importe en el brief                                          | Dejar las horas; el importe lo maneja el usuario                       |
| Hacer «de paso» lo que el cliente pidió a mitad del proyecto             | Frenar, decir que se sale y proponer las salidas                       |
| Decirle que no al cliente sin consultar                                  | Plantear las salidas y esperar la decisión del usuario                 |
| Llamar «formulario» a un sistema de turnos                               | Nombrarlo por lo que es: necesita backend, panel y base de datos       |
| Alcance sin la lista de lo que no incluye                                | Nombrar cada exclusión que se habló                                    |
| Estimar un sistema con un solo número                                    | Una fila por módulo, con su supuesto                                   |
| Olvidar el deploy, los backups, el manual y la auditoría en la estimación | Sumar las partidas fijas                                              |
| Elegir el stack por gusto                                                | Aplicar la pregunta del backend y registrar el `ADR-P-001`             |
| Empezar a diseñar durante el relevamiento                                | Anotar la idea y pasarla a la etapa de diseño                          |

---

## 11. Fuentes de esta skill

| Fuente                                        | Qué aportó                                                                         |
| --------------------------------------------- | ---------------------------------------------------------------------------------- |
| grill-me (Matt Pocock)                        | Entrevista de a una pregunta, con respuesta recomendada y sin preguntar lo averiguable |
| Impeccable (impeccable.style)                 | Preguntar por propósito, personas y material antes que por aspecto                 |
| Web de Respawn Informática                    | Paquetes vigentes y lo que incluye cada uno, leídos en el momento                  |

---

## 12. Referencias

| Archivo                                                          | Contenido                                                                 |
| ---------------------------------------------------------------- | ------------------------------------------------------------------------- |
| [references/relevamiento.md](references/relevamiento.md)         | Árbol de preguntas por bloque, ramas y de dónde se averigua cada dato     |
| [references/estimacion.md](references/estimacion.md)             | Desglose por módulos, partidas fijas y supuestos                          |
| [references/checklist.md](references/checklist.md)               | Reglas verificables del relevamiento, el paquete, el alcance y el cierre  |
| [assets/preguntas-cliente.md](assets/preguntas-cliente.md)       | Plantilla de la lista de preguntas para el cliente                        |
| [assets/alcance-cliente.md](assets/alcance-cliente.md)           | Plantilla del texto del alcance para el cliente                           |
| [evals/activacion.json](evals/activacion.json)                   | Consultas de activación de esta skill                                     |
| [../respawn-init/SKILL.md](../respawn-init/SKILL.md)             | Inicialización del proyecto                                               |
| [../respawn-proyecto/SKILL.md](../respawn-proyecto/SKILL.md)     | Formato del brief, del registro de decisiones y de los pendientes         |
| [../respawn-design/SKILL.md](../respawn-design/SKILL.md)         | Concepto y diseño                                                         |
| [../respawn-web/SKILL.md](../respawn-web/SKILL.md)               | Reglas técnicas de todo sitio                                             |
| [../respawn-sistemas/SKILL.md](../respawn-sistemas/SKILL.md)     | Paneles, tiendas y sistemas                                               |
| [../review-respawn/SKILL.md](../review-respawn/SKILL.md)         | Auditoría de entrega                                                      |
