---
name: respawn-web
description: >
    Reglas técnicas de todo sitio público de un proyecto Respawn, en Astro o
    en Blade: HTML semántico, accesibilidad, rendimiento, SEO local,
    indexación de vistas previas y de producción, links de WhatsApp y
    formularios. Usar siempre que se cree o modifique una página pública, un
    layout, el head, imágenes, fuentes, metadatos, JSON-LD, sitemap, robots o
    un link de contacto, y cuando el usuario mencione contraste, foco,
    teclado, alt, Lighthouse, LCP, CLS, title, description, canonical, Open
    Graph, noindex, schema o "no abre el WhatsApp". No usar para decidir cómo
    se ve o qué dice una pantalla (es de respawn-design), para la lógica de
    backend, el panel o Mercado Pago (respawn-sistemas) ni para medir y
    auditar antes de entregar (review-respawn).
---

# Respawn Web — calidad técnica de todo sitio

## Contenido

0. Convenciones
1. Principio rector
2. Qué regla vale dónde
3. HTML semántico
4. Accesibilidad
5. Rendimiento
6. SEO local
7. Indexación: vista previa y producción
8. Contacto: WhatsApp y formularios
9. Notas por stack
10. Antipatrones
11. Fuentes de esta skill
12. Referencias

---

## 0. Convenciones

Esta skill fija lo que todo sitio cumple, sea cual sea su diseño y su stack.
Las reglas verificables viven en
[references/checklist.md](references/checklist.md); este cuerpo las introduce.

- **Vale para los dos stacks de la casa** `*(Ver ADR-003)*`. Lo propio de
  cada uno está en las notas por stack.
- **El diseño no negocia estas reglas:** si una decisión visual choca con una
  regla de esta skill, se avisa al usuario y se propone una alternativa. No
  se cambia en silencio.
- **Un desvío que no se corrige en la tarea** se registra como deuda con
  [respawn-proyecto](../respawn-proyecto/SKILL.md).
- **Medir es de la auditoría:** los umbrales y la forma de medirlos son de
  [review-respawn](../review-respawn/SKILL.md) `*(Ver ADR-005)*`.
- **Ante un caso que la lista no cubre:** preguntar al usuario.

---

## 1. Principio rector

> **Todo el contenido llega en el HTML. El JavaScript mejora una página que
> ya funciona; nunca la arma.**

Un visitante con un celular modesto, un lector de pantalla o un buscador
reciben lo mismo: títulos, textos, precios, preguntas y links, escritos en el
HTML que manda el servidor o que genera el build. Lo que el JS agrega encima
(una transición, un menú que se despliega, un envío sin recargar) puede
fallar sin que se pierda nada.

---

## 2. Qué regla vale dónde

| Superficie                                   | HTML | Accesibilidad | Rendimiento | SEO | Indexación        | Contacto |
| -------------------------------------------- | ---- | ------------- | ----------- | --- | ----------------- | -------- |
| Página pública de un sitio o de una tienda   | Sí   | Sí            | Sí          | Sí  | Sí                | Sí       |
| Carrito, pago, ingreso y resultados internos | Sí   | Sí            | Sí          | No  | Nunca se indexa   | Sí       |
| Pantalla del panel                           | Sí   | Sí            | No se mide  | No  | Nunca se indexa   | No       |
| Página de error                              | Sí   | Sí            | Sí          | No  | Nunca se indexa   | Sí       |

---

## 3. HTML semántico

Cada cosa se escribe con el elemento que la nombra: eso le da gratis el
teclado, el lector de pantalla y el buscador.

| Lo que es                                   | Elemento                                         |
| ------------------------------------------- | ------------------------------------------------ |
| Algo que lleva a otra dirección             | `<a href>`                                       |
| Algo que hace una acción en la página       | `<button>`                                       |
| El título de la página                      | Un único `<h1>`                                  |
| El título de una sección y de sus partes    | `<h2>` y `<h3>`, sin saltear niveles             |
| Cabecera, menú, contenido y pie             | `<header>`, `<nav>`, `<main>` y `<footer>`       |
| Contenido que se pliega                     | `<details>` y `<summary>`                        |
| Una lista de cosas                          | `<ul>` u `<ol>`                                  |
| Datos en filas y columnas                   | `<table>` con sus `<th>`                         |
| Un campo y su rótulo                        | `<label>` asociado al campo                      |

Reglas de estructura y de contenido en el HTML:
[references/checklist.md](references/checklist.md).

---

## 4. Accesibilidad

El piso es WCAG 2.2 en nivel AA. Seis cosas se comprueban en toda pantalla,
porque son las que más se rompen:

- Contraste del texto y de los controles.
- Foco visible en todo lo que se puede accionar.
- Uso completo con teclado, en un orden que se entiende.
- Texto alternativo con sentido en las imágenes que tienen contenido.
- Link para saltar al contenido.
- Respeto por quien pide menos movimiento.

El foco visible se diseña con el estilo del sitio: nunca se quita. Las reglas
completas, con sus valores, están en
[references/checklist.md](references/checklist.md).

---

## 5. Rendimiento

La mayoría de las visitas llega desde el celular, con datos móviles. El peso
de una página lo deciden tres cosas, en este orden: imágenes, fuentes y
JavaScript.

| Recurso    | Decisión                                                                                         |
| ---------- | ------------------------------------------------------------------------------------------------ |
| Imágenes   | Formato moderno, tamaño declarado, y servidas al tamaño en que se ven                            |
| Fuentes    | Alojadas en el propio sitio, recortadas a los caracteres que se usan, pocas familias y pesos     |
| JavaScript | El mínimo: primero HTML y CSS; un script propio antes que una librería                           |
| Terceros   | Cada script de un tercero se justifica y se anota en el registro de decisiones                   |

Antes de sumar una dependencia se recorre esta escalera y se frena en el
primer escalón que resuelve:

1. ¿Hace falta? Si es por las dudas, no va.
2. ¿Lo resuelve HTML o CSS nativo?
3. ¿Lo resuelve un script propio de pocas líneas?
4. Recién entonces, una dependencia, anotada con su motivo.

Reglas con sus valores: [references/checklist.md](references/checklist.md).

---

## 6. SEO local

El objetivo es que alguien de la ciudad del cliente lo encuentre al buscar lo
que vende, y que lo que encuentre coincida con lo que el negocio dice en
todos lados.

- **Una página por tema,** con contenido propio. Una página que repite a otra
  no suma.
- **Cada página se presenta sola:** título, descripción, dirección canónica e
  imagen para compartir propios.
- **Datos estructurados de negocio local,** con los mismos datos que se ven
  en la página.
- **Nombre, teléfono y zona idénticos** en la web, en los datos
  estructurados, en el Perfil de Empresa de Google y en las redes.
- **Una sola dirección base:** canónica, imagen para compartir, sitemap y
  datos estructurados salen de la misma variable.

Los textos del título y de la descripción se escriben con
[respawn-design](../respawn-design/SKILL.md). Reglas con sus largos y
propiedades: [references/checklist.md](references/checklist.md).

---

## 7. Indexación: vista previa y producción

Una vista previa indexada compite con el sitio real y muestra trabajo sin
terminar. Por eso **indexar es una decisión explícita, no el estado de
fábrica:** toda página sale con `noindex` salvo que el entorno de producción
declare lo contrario.

| Entorno                                  | `noindex` | Sitemap enviado | `robots.txt`             |
| ---------------------------------------- | --------- | --------------- | ------------------------ |
| Desarrollo local                         | Sí        | No              | No bloquea               |
| Vista previa o dominio provisorio        | Sí        | No              | No bloquea               |
| Producción, con el dominio definitivo    | No        | Sí              | No bloquea y enlaza el sitemap |

- `robots.txt` nunca bloquea una vista previa: el buscador tiene que poder
  leer el `noindex`.
- Pasar a producción es un paso con lista propia: activar la indexación,
  redirigir el dominio provisorio y comprobar que ningún pendiente llegó al
  HTML publicado.

Reglas y pasos: [references/checklist.md](references/checklist.md).

---

## 8. Contacto: WhatsApp y formularios

La acción que casi todo sitio busca es que el visitante escriba. El link de
WhatsApp es la vía principal y un formulario es la excepción.

| Necesidad                                             | Vía                                   |
| ----------------------------------------------------- | ------------------------------------- |
| Consultar, pedir precio o pedir un turno              | Link de WhatsApp con mensaje armado   |
| El cliente no usa WhatsApp o pidió recibir correos    | Formulario, con backend               |
| Juntar datos para un trámite o un pedido estructurado | Formulario, con backend               |

- **El mensaje cambia según desde dónde escribe el visitante.** Qué dice cada
  mensaje es de [respawn-design](../respawn-design/SKILL.md); acá se arma el
  link.
- **El número y el armado del link viven en un solo lugar del código.**
- **Un sitio sin backend no lleva formulario.** Si el cliente necesita uno,
  cambia el stack `*(Ver ADR-003)*`.

Formato del link y reglas de formularios:
[references/checklist.md](references/checklist.md).

---

## 9. Notas por stack

| Stack                         | Notas                                                    |
| ----------------------------- | -------------------------------------------------------- |
| Sin backend: Astro, CSS plano | [references/astro.md](references/astro.md)               |
| Con backend: Laravel y Blade  | [references/laravel-blade.md](references/laravel-blade.md) |

Las notas dicen cómo se cumple cada regla en ese stack. No agregan reglas.

---

## 10. Antipatrones

| Antipatrón                                                             | Resolución                                                              |
| ---------------------------------------------------------------------- | ----------------------------------------------------------------------- |
| Precios, servicios o preguntas que se pintan con JS                    | Escribirlos en el HTML, en el servidor o en el build                    |
| `div` con un manejador de clic                                         | `<a href>` si lleva a otro lado; `<button>` si hace algo                |
| Quitar el contorno de foco porque «queda feo»                          | Diseñar un foco con el estilo del sitio                                 |
| Título o botón como imagen con letras                                  | Texto real con la fuente del sitio                                      |
| Texto alternativo que dice «imagen» o repite el nombre del archivo     | Decir lo que la imagen muestra, o dejarlo vacío si es decorativa        |
| Fuentes pedidas a un servidor de terceros                              | Alojarlas en el sitio, recortadas                                       |
| Librería para un carrusel, un acordeón o un menú                       | HTML y CSS nativos, o un script propio                                  |
| Imagen de 3000 px mostrada en 400                                      | Servirla al tamaño en que se ve                                         |
| Acordarse de sacar el `noindex` el día de publicar                     | Indexar es una variable del entorno de producción                       |
| Bloquear la vista previa con `robots.txt`                              | Dejarla leer y marcarla con `noindex`                                   |
| Un `[FALTA: …]` dentro de los datos estructurados                      | Omitir la propiedad hasta tener el dato                                 |
| El mismo mensaje de WhatsApp en todos los botones                      | Un mensaje por origen                                                   |
| El número de WhatsApp escrito en cada componente                       | Un dato único y una función que arma el link                            |
| Formulario en un sitio sin backend, con un servicio de terceros        | Link de WhatsApp, o replantear el stack con el usuario                  |

---

## 11. Fuentes de esta skill

| Fuente                                                         | Qué aportó                                                                            |
| -------------------------------------------------------------- | ------------------------------------------------------------------------------------- |
| WCAG 2.2 (W3C)                                                 | Contraste, foco visible, teclado, nombres accesibles y tamaño de las zonas táctiles   |
| web.dev: Core Web Vitals                                       | LCP, CLS e INP, y el tratamiento de imágenes y fuentes que los afecta                 |
| Google Search Central                                          | `noindex` y `robots.txt`, datos estructurados de negocio local y largos de título     |
| schema.org                                                     | Propiedades de `LocalBusiness`, `Service` y `Offer`                                   |
| Protocolo Open Graph                                           | Metadatos para compartir                                                              |
| Documentación de WhatsApp: links de clic para chatear          | Formato del link con número y mensaje                                                 |
| Brief y estrategia de SEO de la web de Respawn                 | Indexación por variable de entorno, dirección base única y contenido completo en el HTML |

---

## 12. Referencias

| Archivo                                                        | Contenido                                                                      |
| -------------------------------------------------------------- | ------------------------------------------------------------------------------ |
| [references/checklist.md](references/checklist.md)             | Reglas verificables: HTML, accesibilidad, rendimiento, SEO, indexación y contacto |
| [references/astro.md](references/astro.md)                     | Cómo se cumple cada regla en un sitio Astro                                    |
| [references/laravel-blade.md](references/laravel-blade.md)     | Cómo se cumple cada regla en las páginas públicas de un sistema Laravel        |
| [evals/activacion.json](evals/activacion.json)                 | Consultas de activación de esta skill                                          |
| [../respawn-design/SKILL.md](../respawn-design/SKILL.md)       | Diseño, textos y mensajes de WhatsApp                                          |
| [../respawn-proyecto/SKILL.md](../respawn-proyecto/SKILL.md)   | Registro de decisiones y de deuda                                              |
| [../respawn-sistemas/SKILL.md](../respawn-sistemas/SKILL.md)   | Backend, panel, seguridad y deploy de los sistemas                             |
| [../review-respawn/SKILL.md](../review-respawn/SKILL.md)       | Medición y auditoría de entrega                                                |
