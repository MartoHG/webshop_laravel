# Notas para sitios con backend: Laravel, Blade y Tailwind

> Referencia de `respawn-web`: cómo se cumple cada regla en las páginas
> públicas de un sistema Laravel. No agrega reglas: las reglas están en
> `checklist.md`. La arquitectura del backend y del panel es de
> `respawn-sistemas`.

## Contenido

1. Dos superficies
2. Configuración
3. Head y SEO
4. Indexación
5. Imágenes y fuentes
6. JavaScript y HTMX
7. Contacto y formularios
8. Antes de publicar

## 1. Dos superficies

| Superficie | Layout                         | Qué reglas de `respawn-web` lleva                              |
| ---------- | ------------------------------ | -------------------------------------------------------------- |
| Pública    | `layouts/publico.blade.php`    | Todas                                                          |
| Panel      | `layouts/panel.blade.php`      | HTML y accesibilidad; nunca se indexa                          |

- Cada superficie tiene su layout. El público no carga los estilos ni los
  scripts del panel.
- El contenido de una página pública sale de la base de datos y se escribe en
  la vista Blade: llega completo en el HTML.

## 2. Configuración

| Clave              | Para qué                                                   | Valor de fábrica                |
| ------------------ | ---------------------------------------------------------- | ------------------------------- |
| `APP_URL`          | Dirección base: canónica, Open Graph, sitemap y JSON-LD    | La del entorno                  |
| `APP_INDEXAR`      | Con `true`, quita el `noindex` de las páginas públicas     | `false`                         |
| `NEGOCIO_WHATSAPP` | Número de WhatsApp, si no se edita desde el panel          | Sin valor                       |

- Las vistas leen estos valores con `config()`, nunca con `env()`.
- Los datos del negocio que el cliente edita desde el panel (teléfono,
  horario, dirección) salen de la base de datos y de un solo lugar.
- `.env.example` lista las tres claves.

## 3. Head y SEO

| Regla                         | Cómo se cumple                                                                                   |
| ----------------------------- | ------------------------------------------------------------------------------------------------ |
| Título y descripción propios  | Componente `<x-seo>` en el layout público; cada vista le pasa los suyos                          |
| Canónica absoluta             | `<x-seo>` usa la dirección de la ruta sin parámetros de consulta                                 |
| Listados con filtros o página | La canónica apunta al listado sin filtros; la paginación conserva solo `page`                    |
| Open Graph                    | `<x-seo>`; en una ficha de producto, la imagen es la foto del producto                           |
| JSON-LD                       | Se arma en PHP como arreglo, se filtran los valores vacíos y se imprime con `@json`              |
| Sitemap                       | Ruta `/sitemap.xml` que lista las páginas fijas y los registros públicos, con su respuesta en caché |
| Robots                        | Ruta `/robots.txt`, que enlaza el sitemap                                                        |
| Error real                    | `errors/404.blade.php` con el layout público                                                     |
| Direcciones                   | Rutas con nombre y un `slug` en minúsculas, sin tildes y con guiones                             |

- Un producto o una página que el cliente despublica deja de estar en el
  sitemap y responde 404.
- El `slug` no cambia al editar el nombre. Si tiene que cambiar, la dirección
  vieja redirige con 301.

## 4. Indexación

| Qué                                              | Cómo                                                                  |
| ------------------------------------------------ | --------------------------------------------------------------------- |
| Páginas públicas                                 | `<x-seo>` escribe el `noindex` cuando `app.indexar` es `false`        |
| Panel, ingreso, carrito, pago y búsqueda interna | Un middleware agrega `X-Robots-Tag: noindex` a todo el grupo de rutas |
| Página de error                                  | `noindex` siempre                                                     |

## 5. Imágenes y fuentes

Las fotos las sube el cliente desde el panel, con el tamaño y el formato que
tenga. Por eso se procesan al subirlas, no al mostrarlas.

| Regla                      | Cómo se cumple                                                                              |
| -------------------------- | ------------------------------------------------------------------------------------------- |
| Formato                    | Al subir, se genera la versión WebP en los anchos que usa el sitio                          |
| Tamaño declarado           | El ancho y el alto de cada versión se guardan con el registro y la vista los imprime        |
| Tamaño servido             | `srcset` y `sizes` con las versiones generadas                                              |
| Carga diferida             | `loading="lazy"` salvo en la imagen principal de la primera pantalla                        |
| Texto alternativo          | Campo del panel junto a la foto, con una ayuda que explica qué escribir                     |
| Fuentes propias            | Archivos WOFF2 dentro del proyecto, cargados por Vite                                       |
| Tokens                     | El tema de Tailwind, con nombres del proyecto                                               |

- La herramienta que procesa las imágenes es una dependencia: se anota en el
  registro de decisiones con su motivo.
- El original se guarda fuera de la carpeta pública.

## 6. JavaScript y HTMX

- **Una página pública funciona sin JavaScript:** los links son links y los
  formularios envían con un pedido normal. HTMX mejora encima.
- **HTMX y el script propio se cargan desde el paquete de Vite,** en el
  layout.
- **El estado lo decide el servidor:** cada estado visible es una respuesta
  HTML, no una variable en el navegador.
- **El panel puede depender de HTMX.** Las páginas públicas no.

## 7. Contacto y formularios

- **WhatsApp:** un componente Blade recibe el mensaje y arma el link con el
  número del negocio. Ninguna vista escribe la dirección a mano.
- **Mensaje por producto:** la ficha arma el mensaje con el nombre del
  producto.

| Regla del formulario       | Cómo se cumple                                                              |
| -------------------------- | --------------------------------------------------------------------------- |
| Validación en el servidor  | Form Request                                                                |
| Error junto al campo       | `@error` debajo de cada campo, con `old()` para conservar lo escrito        |
| Confirmación visible       | Redirección con un mensaje de sesión que la vista muestra                   |
| Límite de envíos           | Middleware `throttle` en la ruta                                            |
| Campo trampa               | Campo oculto que un visitante no completa; si llega con valor, se descarta sin avisar |
| Datos personales           | Link a la política de privacidad junto al botón                             |

## 8. Antes de publicar

1. Poner `APP_INDEXAR=true` y la `APP_URL` definitiva en el entorno de
   producción.
2. Pedir una página pública y una del panel, y comprobar que la primera no
   tiene `noindex` y la segunda sí.
3. Abrir `/sitemap.xml` y comprobar que lista solo páginas públicas con la
   dirección definitiva.
4. Buscar `[FALTA:` en las vistas y en los textos cargados en la base.
5. Comprobar que una dirección inexistente responde 404.
