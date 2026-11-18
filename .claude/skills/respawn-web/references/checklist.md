# Lista de verificación de sitios (reglas A a F)

> Reglas verificables de `respawn-web`. Se recorre al cerrar cada página y la
> usa `review-respawn` en la auditoría. Los IDs son estables: no se reordenan
> ni se reúsan.

## Contenido

- Uso
- A. HTML
- B. Accesibilidad
- C. Rendimiento
- D. SEO local
- E. Indexación y publicación
- F. Contacto y formularios

---

## Uso

1. **Mirar la página** en 360 px de ancho y en escritorio, con teclado y con
   el JavaScript desactivado.
2. **Leer el HTML que llega**, no solo el código fuente: el del build o el de
   la respuesta del servidor.
3. **Evaluar cada regla** como «cumple», «no cumple» o «no aplica», según la
   tabla de superficies de la skill.
4. **Informar** cada regla que no cumple con la página, el elemento y, si es
   código, el archivo y la línea.

**Bloqueantes: A1, A2, B1, B2, B3, B6, B10, C11, D1, D3, D7, E1, E2, E4, E7,
F1 y F2.** El resto se informa como recomendación.

## A. HTML

- **A1.** Contenido en el HTML: títulos, textos, precios, listas de servicios
  o productos y preguntas están en el HTML inicial. La página se lee completa
  con el JavaScript desactivado.
- **A2.** Navegación sin JavaScript: todos los links entre páginas y el link
  de contacto funcionan con el JavaScript desactivado.
- **A3.** Idioma: `<html lang="es-AR">`.
- **A4.** Un solo `<h1>` por página, con texto real.
- **A5.** Títulos en orden: los niveles no se saltean.
- **A6.** Regiones: un único `<main>`, con `<header>`, `<nav>` y `<footer>`
  donde corresponden.
- **A7.** Elemento correcto: lo que navega es `<a href>` y lo que acciona es
  `<button>`. Ningún `div` ni `span` recibe clics.
- **A8.** Texto real: títulos, menús y botones son texto, nunca una imagen
  con letras.
- **A9.** Mayúsculas por CSS: el HTML lleva el texto con su escritura normal
  y las mayúsculas se aplican con `text-transform`.
- **A10.** Plegables nativos: el contenido que se pliega usa `<details>`,
  salvo que el diseño exija otro patrón y ese patrón tenga su teclado y sus
  atributos completos.

## B. Accesibilidad

- **B1.** Contraste: 4,5:1 en texto común; 3:1 en texto de 24 px o más, en
  texto en negrita de 19 px o más y en bordes e íconos de los controles.
- **B2.** Foco visible: todo elemento que se puede accionar muestra su foco.
  No hay `outline: none` sin un estilo de foco que lo reemplace.
- **B3.** Teclado: todo se alcanza y se acciona con teclado, en el orden en
  que se lee, y ningún componente atrapa el foco.
- **B4.** Saltar al contenido: el primer elemento que recibe foco es un link
  que lleva al `<main>`.
- **B5.** Texto alternativo: cada imagen con contenido tiene un `alt` que
  dice lo que muestra. Las decorativas llevan `alt=""`.
    - _Incorrecto:_ `alt="imagen"`, `alt="foto1.jpg"`.
    - _Correcto:_ `alt="Mostrador del local con la heladera de quesos"`.
- **B6.** Menos movimiento: con `prefers-reduced-motion: reduce`, ninguna
  animación corre, salvo un cambio de opacidad de menos de 200 ms.
- **B7.** Nombre accesible: todo botón o link de solo ícono tiene nombre, y
  el nombre de un control contiene su texto visible.
- **B8.** Íconos decorativos: llevan `aria-hidden="true"`.
- **B9.** Zonas táctiles: todo control mide al menos 44 px de alto y de
  ancho, o tiene esa zona de toque.
- **B10.** Sin desborde: a 360 px de ancho no hay desplazamiento horizontal ni
  contenido cortado.
- **B11.** Ampliación: al 200 % de ampliación el contenido sigue legible y
  sin superponerse.
- **B12.** Color con respaldo: ningún dato se comunica solo con color.
- **B13.** Sin reproducción automática de sonido ni de video con sonido.
- **B14.** Estado anunciado: un mensaje que aparece sin recargar la página,
  como un error o una confirmación, se anuncia a los lectores de pantalla.

## C. Rendimiento

- **C1.** Formato: las fotos se sirven en AVIF o WebP. Los íconos y los
  logos, en SVG.
- **C2.** Tamaño declarado: toda imagen lleva `width` y `height`.
- **C3.** Tamaño servido: ninguna imagen se sirve a más del doble del tamaño
  en que se muestra.
- **C4.** Carga diferida: las imágenes que no se ven al cargar llevan
  `loading="lazy"`. La imagen principal de la primera pantalla no lo lleva y
  tiene `fetchpriority="high"`.
- **C5.** Fuentes propias: se alojan en el sitio, en WOFF2 y recortadas a los
  caracteres del castellano. No hay pedidos a servidores de fuentes de
  terceros.
- **C6.** Precarga acotada: se precargan solo las fuentes de la primera
  pantalla.
- **C7.** Sin salto de fuente: `font-display: swap` con una fuente de
  respaldo ajustada, de modo que el cambio no mueva el texto.
- **C8.** Terceros anotados: cada script, fuente o recurso de un tercero
  figura en el registro de decisiones con su motivo.
- **C9.** Sin dependencia evitable: ninguna librería hace lo que el sitio
  podía resolver con HTML, CSS o un script propio.
- **C10.** Analítica sin cookies, o ninguna.
- **C11.** Consola limpia: la página carga sin errores en la consola.
- **C12.** Recursos vigentes: ningún pedido de la página responde 404.

## D. SEO local

- **D1.** Título: cada página tiene un `<title>` propio, de hasta 60
  caracteres. En una página de servicio incluye el servicio y la ciudad.
- **D2.** Descripción: cada página tiene su `<meta name="description">`
  propia, de hasta 155 caracteres.
- **D3.** Canónica: cada página tiene `<link rel="canonical">` con dirección
  absoluta, igual a la que figura en el sitemap.
- **D4.** Una sola forma de dirección: minúsculas, sin tildes y con guiones;
  con o sin barra final, pero igual en la canónica, el sitemap y los links
  internos.
- **D5.** Para compartir: `og:title`, `og:description`, `og:url`, `og:type`,
  `og:locale` con `es_AR`, `og:site_name` y `og:image` con dirección absoluta
  y sus medidas. La imagen mide 1200 × 630.
- **D6.** Negocio local: la portada lleva JSON-LD de tipo `LocalBusiness` o
  de un subtipo, con `@id` estable, nombre, dirección web, teléfono en
  formato internacional, localidad, zona atendida, horario y redes.
- **D7.** Datos estructurados veraces: cada valor del JSON-LD se ve también
  en la página. Una propiedad sin dato se omite. No hay marcadores, reseñas
  ni puntajes inventados.
- **D8.** Datos iguales en todos lados: nombre, teléfono, zona y horario
  coinciden entre la página, el JSON-LD y el brief.
- **D9.** Dirección base única: la canónica, `og:url`, `og:image`, el sitemap
  y el JSON-LD salen de una sola variable.
- **D10.** Sitemap: `sitemap.xml` lista todas las páginas públicas que se
  indexan y ninguna otra.
- **D11.** Robots: `robots.txt` enlaza el sitemap y no bloquea ninguna página
  pública.
- **D12.** Error real: una dirección que no existe responde con estado 404 y
  muestra un link al inicio.
- **D13.** Links con sentido: el texto de cada link dice a dónde lleva.
- **D14.** Sin duplicados: no hay dos páginas con el mismo contenido.
- **D15.** Marcado válido: el JSON-LD pasa sin errores la prueba de
  resultados enriquecidos de Google.

## E. Indexación y publicación

- **E1.** Indexar es explícito: toda página lleva
  `<meta name="robots" content="noindex">` salvo que una variable del entorno
  de producción lo quite.
- **E2.** Producción indexable: con el dominio definitivo, ninguna página
  pública lleva `noindex`.
- **E3.** Vista previa legible: en una vista previa `robots.txt` no bloquea
  nada.
- **E4.** Nunca indexadas: llevan `noindex` en todo entorno la página de
  error, las de prueba, el panel, el ingreso, el carrito, el pago y los
  resultados de búsqueda interna.
- **E5.** Pruebas fuera: las páginas de prueba no llegan al build ni al
  servidor de producción.
- **E6.** Dominio provisorio: redirige con 301 al definitivo.
- **E7.** Sin pendientes publicados: el HTML de producción no contiene
  `[FALTA:` ni textos en borrador.
- **E8.** HTTPS y una sola variante: las variantes de una dirección, con
  `www`, con barra final o con extensión, redirigen a la canónica.

## F. Contacto y formularios

- **F1.** Formato del link: `https://wa.me/{número}?text={mensaje}`, con el
  número en formato internacional sin signos ni espacios y el mensaje
  codificado para dirección web.
- **F2.** Mensaje por origen: cada link de WhatsApp lleva un mensaje que dice
  desde qué página, servicio o producto escribe el visitante.
- **F3.** Dato único: el número y la función que arma el link están en un
  solo lugar del código.
- **F4.** Link real: el contacto por WhatsApp es un `<a href>`, no un script.
- **F5.** Contacto a la vista: el canal de contacto se ve en la primera
  pantalla en celular y se repite al final de cada página.
- **F6.** Teléfono y correo accionables: van como `tel:` y `mailto:`.
- **F7.** Formulario mínimo: pide solo los datos que hacen falta para
  responder.
- **F8.** Rótulos visibles: cada campo tiene su `<label>` visible. El texto
  de ejemplo no reemplaza al rótulo.
- **F9.** Validación en el servidor: lo que valida el navegador se valida de
  nuevo en el servidor.
- **F10.** Error junto al campo: el mensaje aparece al lado del campo que
  falló, en palabras, y el resto de lo escrito se conserva.
- **F11.** Confirmación visible: después de enviar, la página dice que el
  mensaje llegó y qué pasa después.
- **F12.** Envíos automáticos: el formulario tiene un campo trampa oculto y
  un límite de envíos por visitante.
- **F13.** Datos personales: un formulario que los junta enlaza la política
  de privacidad junto al botón de envío.
