# Notas para sitios sin backend: Astro y CSS plano

> Referencia de `respawn-web`: cómo se cumple cada regla en un sitio Astro.
> No agrega reglas: las reglas están en `checklist.md`. Los nombres de
> archivos y de variables son la convención de la casa.

## Contenido

1. Estructura
2. Variables del build
3. Head y SEO
4. Indexación
5. Imágenes y fuentes
6. JavaScript
7. Contacto
8. Antes de publicar

## 1. Estructura

```
src/
├── layouts/Base.astro      ← único layout: head, saltar al contenido, main y pie
├── components/SeoHead.astro ← título, descripción, canónica, robots, Open Graph y JSON-LD
├── components/             ← componentes chicos, uno por archivo
├── data/                   ← contenido: negocio, servicios, precios, preguntas
├── lib/sitio.js            ← dirección base y variable de indexación
├── lib/whatsapp.js         ← función que arma el link de WhatsApp
├── pages/                  ← una página por archivo; 404.astro; sitemap y robots como extremos
└── styles/tokens.css       ← tokens del proyecto
```

- **El contenido vive en `src/data/`**, separado del maquetado. Las páginas lo
  leen al compilar, así queda escrito en el HTML.
- **Un dato, un lugar:** el teléfono, el horario y los precios se escriben
  una vez en `src/data/` y de ahí salen la página y el JSON-LD.
- **Un único layout.** Toda página lo usa y le pasa su título, su descripción
  y su ruta.
- **Sin dependencias más allá de Astro,** salvo las anotadas en el registro
  de decisiones.

## 2. Variables del build

| Variable          | Para qué                                                                  | Valor de fábrica                      |
| ----------------- | ------------------------------------------------------------------------- | ------------------------------------- |
| `SITE_URL`        | Dirección base: canónica, Open Graph, sitemap y JSON-LD                   | El dominio definitivo                 |
| `INDEXAR`         | Con `true`, quita el `noindex`                                            | Sin definir: todo sale con `noindex`  |
| `INCLUIR_PRUEBAS` | Con `true`, deja las páginas de prueba en el build                        | Sin definir: se quitan                |

- `site` de `astro.config.mjs` toma `SITE_URL`.
- En el hosting, la vista previa define `SITE_URL` con su propia dirección y
  no define `INDEXAR`. Producción define `INDEXAR=true`.
- Las tres variables se documentan en el README del proyecto.

## 3. Head y SEO

| Regla                         | Cómo se cumple                                                                                 |
| ----------------------------- | ---------------------------------------------------------------------------------------------- |
| Título y descripción propios  | Cada página los pasa al layout; `SeoHead.astro` los escribe                                    |
| Canónica absoluta             | `SeoHead.astro` la arma con la ruta de la página y `Astro.site`                                |
| Una sola forma de dirección   | `trailingSlash` y `build.format` fijados en `astro.config.mjs`, y los links internos escritos igual |
| Open Graph                    | `SeoHead.astro`, con la imagen en dirección absoluta y sus medidas                             |
| JSON-LD                       | Cada página arma su objeto desde `src/data/` y lo pasa al layout; una propiedad sin dato no se agrega |
| Sitemap                       | Extremo `src/pages/sitemap.xml.js`, con la lista de rutas públicas                             |
| Robots                        | Extremo `src/pages/robots.txt.js`, que enlaza el sitemap                                       |
| Error real                    | `src/pages/404.astro`; el hosting lo sirve con estado 404                                      |
| Idioma                        | `<html lang="es-AR">` en el layout                                                             |

- El sitemap y el robots son extremos propios de pocas líneas: no hace falta
  una integración.
- Al sumar una página, se agrega su ruta a la lista del sitemap en el mismo
  cambio.

## 4. Indexación

- `SeoHead.astro` escribe el `noindex` cuando `INDEXAR` no es `true`, y
  también cuando la página se lo pide por una propiedad.
- La página de error y las de prueba piden `noindex` siempre.
- Las páginas de prueba se borran del resultado del build cuando
  `INCLUIR_PRUEBAS` no es `true`.

## 5. Imágenes y fuentes

| Regla                          | Cómo se cumple                                                                          |
| ------------------------------ | --------------------------------------------------------------------------------------- |
| Formato y tamaño declarado     | `<Image>` de `astro:assets`, con la imagen importada desde `src/`                       |
| Tamaño servido                 | `widths` y `sizes` en cada `<Image>`, según el ancho en que se muestra                  |
| Carga diferida                 | `loading="lazy"` es el valor de fábrica; la imagen principal lleva `loading="eager"` y `fetchpriority="high"` |
| Íconos                         | SVG propios que heredan el color con `currentColor`                                     |
| Fuentes propias                | Archivos WOFF2 recortados dentro del proyecto, con su `@font-face`                      |
| Precarga                       | `<link rel="preload" as="font" type="font/woff2" crossorigin>` en el layout, solo las de la primera pantalla |

- Una imagen en `public/` no se optimiza: ahí van solo el ícono del sitio y
  la imagen para compartir.

## 6. JavaScript

- **Astro no manda JavaScript si nadie lo pide.** Ese es el estado buscado.
- **Interacción chica:** una etiqueta `<script>` dentro del componente, con
  funciones documentadas.
- **Isla con `client:*`:** solo si el componente no puede resolverse con HTML,
  CSS y un script propio. Cada isla se anota en el registro de decisiones.
- **Transición entre páginas:** `@view-transition` en CSS, como mejora. Sin
  soporte, el navegador navega normal.
- **Todo script arranca de un HTML que ya funciona:** agrega comportamiento a
  elementos que existen, no crea el contenido.

## 7. Contacto

- El número de WhatsApp está en `src/data/`, en formato internacional y sin
  signos.
- `linkWhatsApp(mensaje)` de `src/lib/whatsapp.js` arma el link y codifica el
  mensaje. Ningún componente escribe la dirección a mano.
- Los mensajes de cada servicio viven junto a los datos del servicio.
- Un sitio Astro de la casa no lleva formulario.

## 8. Antes de publicar

1. Compilar con las variables de producción.
2. Buscar en el resultado del build `noindex`, `[FALTA:` y la dirección de la
   vista previa: no tiene que aparecer ninguno fuera de las páginas que nunca
   se indexan.
3. Abrir el resultado con el servidor de vista previa y recorrer la lista de
   verificación.
4. Comprobar en el sitio publicado que una dirección inexistente responde
   404.
