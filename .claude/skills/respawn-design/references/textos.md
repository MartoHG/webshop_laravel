# Textos

> Referencia de `respawn-design`: cómo se escribe todo texto visible de un
> sitio o de un sistema. Los patrones de texto prohibidos están en
> `catalogo-slop.md`; acá está lo que se hace.

## Contenido

1. La voz
2. Nunca inventar
3. Títulos y bajadas
4. Botones y links
5. Mensaje de WhatsApp
6. Microtipografía
7. Textos de panel y de sistema
8. Lectura final

## 1. La voz

- **Castellano rioplatense con voseo:** _escribinos_, _pedí_, _mirá_, _tenés_.
  Sin tuteo (_escríbenos_, _tienes_) ni trato de usted (_escríbanos_).
- **Habla el negocio, no el sistema.** El texto suena como el dueño
  atendiendo en el mostrador.
- **Las palabras son las del cliente:** si en el brief dice «arreglos», el
  sitio no dice «reparaciones».
- **Un solo registro por sitio.** La voz se decide una vez, se anota en el
  registro de decisiones con tema «voz» y vale para todas las pantallas.
- **Excepción:** si el negocio trata de usted a sus clientes y el usuario lo
  confirma, el sitio trata de usted. Se anota en el registro.

## 2. Nunca inventar

El agente escribe con los hechos del brief y del material del cliente. Puede
ordenar, acortar y dar forma; no puede crear hechos.

| Dato                                                      | Si no está en el material |
| --------------------------------------------------------- | ------------------------- |
| Cifras: años, cantidad de clientes, porcentajes           | Marcador                  |
| Testimonios y reseñas                                     | Marcador, o sin sección   |
| Logos de clientes o de marcas                             | Sin sección               |
| Precios, promociones y plazos                             | Marcador                  |
| Horarios, dirección, teléfono y redes                     | Marcador                  |
| Nombres de personas, premios y certificaciones            | Marcador                  |
| Qué incluye un producto o un servicio                     | Marcador                  |

- **Marcador:** `[FALTA: {qué falta}]`, visible en la pantalla, en el lugar
  donde iría el dato. Ejemplo: `[FALTA: horarios de atención]`.
- Cada marcador se registra como pendiente del cliente.
- Un texto de ejemplo que parece real es peor que un marcador: nadie lo
  detecta y se publica.

## 3. Títulos y bajadas

- **El título de la portada dice tres cosas:** qué hace el negocio, para
  quién y dónde. Si falta una, la agrega la bajada.
- **Prueba de intercambio:** si el título se puede pegar sin cambios en la
  web de la competencia, se reescribe.
- **La bajada suma un dato** que el título no tiene. Si repite, se quita.
- **Cada título de sección dice lo que la sección afirma,** no el nombre de
  la sección.
- **Largo:** un título entra en dos o tres renglones en celular.

| Incorrecto                                  | Correcto                                                       |
| ------------------------------------------- | -------------------------------------------------------------- |
| Soluciones integrales para tu hogar         | Destapaciones y arreglos de plomería en {ciudad}, en el día     |
| Nuestros servicios                          | Qué arreglamos                                                 |
| ¿Por qué elegirnos?                         | Vamos el mismo día y pasamos el precio antes de empezar        |

## 4. Botones y links

- **El rótulo dice qué pasa al tocarlo**, y por qué canal: «Escribinos por
  WhatsApp», «Ver el catálogo», «Pedir un turno».
- **Verbo en imperativo con voseo** en sitios: _escribinos_, _pedí_, _mirá_.
- **Un rótulo por intención:** si el contacto es «Escribinos por WhatsApp»,
  se llama así en el menú, en la portada y en el pie.
- **Una acción principal por pantalla.** Las demás tienen menos peso.
- **El rótulo entra en un renglón** en celular.
- **El texto de un link dice a dónde lleva.** «Clic acá» no es un link.

| Incorrecto        | Correcto                          |
| ----------------- | --------------------------------- |
| Empezar           | Escribinos por WhatsApp           |
| Saber más         | Ver cómo trabajamos               |
| Enviar            | Mandar la consulta                |
| Contáctanos       | Escribinos                        |

## 5. Mensaje de WhatsApp

Todo link de WhatsApp lleva un mensaje escrito de antemano, distinto según
desde dónde escribe el visitante. Cómo se arma el link es de `respawn-web`;
acá está qué dice.

- **Lo escribe el visitante:** va en primera persona, como lo diría él.
- **Dice desde dónde viene y qué quiere:** «Hola {negocio}! Quiero consultar
  por {servicio o producto}.»
- **Un mensaje por servicio o por producto,** más uno general para el
  contacto del pie.
- **Corto:** una o dos oraciones. El visitante lo tiene que poder leer antes
  de mandarlo.
- **Sin datos del visitante** ni preguntas que lo obliguen a completar.

## 6. Microtipografía

- **Mayúsculas tipo oración:** solo la primera letra y los nombres propios,
  también en títulos, botones y menús.
- **Signos de apertura y de cierre** en preguntas y exclamaciones.
- **Punto final:** sí en párrafos y oraciones completas; no en títulos,
  botones, rótulos ni ítems de menú.
- **Sin raya larga.** Se reemplaza por punto, coma, dos puntos o paréntesis.
- **Precios:** signo pegado al número y punto como separador de miles. Un
  precio mínimo lleva «desde» delante.
- **Horarios:** en palabras y sin abreviar: «lunes a viernes, de 9 a 18».
- **Teléfonos:** escritos en grupos, como se dictan: código de área, espacio
  y el número partido con un guion.
- **Sin emojis** en títulos, botones ni párrafos, salvo que el concepto los
  pida y figuren como excepción.

## 7. Textos de panel y de sistema

El panel lo usa el cliente sin ayuda: los textos son los de su negocio, no
los de la base de datos.

- **Vocabulario del negocio:** «productos», «turnos», «pedidos». Nunca el
  nombre de una tabla o de un modelo.
- **Botones:** infinitivo y sustantivo: «Guardar producto», «Crear turno».
  El descarte es siempre «Cancelar».
- **Confirmación de algo que no tiene vuelta:** afirmación explícita: «Sí,
  eliminar producto».
- **Aviso de éxito:** una oración en pasado que dice qué pasó: «El producto
  se guardó.»
- **Error:** dice qué pasó y cómo resolverlo, sin códigos ni términos
  técnicos: «La foto pesa más de 5 MB. Probá con una más liviana.»
- **Estado vacío sin registros:** dice qué falta y cuál es el primer paso:
  «Todavía no cargaste productos. Creá el primero.»
- **Estado vacío con filtros:** «No hay productos con esos filtros.»
- **Ayuda de un campo:** debajo del campo, solo cuando el rótulo no alcanza.

## 8. Lectura final

Antes de dar por terminada una pantalla, leer en voz baja cada texto visible:
títulos, bajadas, botones, epígrafes, mensajes de error, textos alternativos
y pie.

| Al leer se encuentra                                   | Qué hacer                               |
| ------------------------------------------------------ | --------------------------------------- |
| Un dato que no salió del material del cliente          | Reemplazarlo por un marcador            |
| Una frase que no se entiende sin contexto              | Reescribirla como una oración llana     |
| Una frase ingeniosa que no dice nada                   | Reemplazarla por el hecho               |
| Dos rótulos distintos para la misma acción             | Dejar uno                               |
| Un texto en tuteo, en usted o en neutro                | Pasarlo a la voz del sitio              |
| Un patrón de textos del catálogo                       | Quitarlo o registrarlo como excepción   |
