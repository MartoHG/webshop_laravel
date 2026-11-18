# Diccionario de idioma

> Referencia de `respawn-skill-creator`. Vale para las skills, los ADRs y
> todo documento del repositorio. Los textos visibles de una web o un sistema
> tienen además sus propias reglas en `respawn-design`.

## Contenido

1. Voz
2. Términos fijos
3. Palabras en inglés permitidas
4. Prefijos
5. Palabras bloqueadas

## 1. Voz

- Castellano rioplatense con voseo: _tenés_, _podés_, _escribí_, _revisá_.
- Sin tuteo (_tienes_, _escribe_) ni trato de usted (_tiene_, _escriba_).
- Las reglas se escriben en imperativo o en impersonal: «Leer del disco»,
  «La paleta sale del cliente».
- Signos de apertura y de cierre en preguntas y exclamaciones.
- Comillas angulares (« ») para citar pedidos y frases.

## 2. Términos fijos

Un concepto, un término. Estos no se alternan con sinónimos:

| Término                  | Significado                                                                   |
| ------------------------ | ----------------------------------------------------------------------------- |
| la casa                  | El repositorio `respawn-skills` y sus reglas                                  |
| el proyecto              | El repositorio de un trabajo para un cliente                                  |
| capa casa, capa proyecto | Las dos capas del sistema                                                     |
| proyecto Respawn         | Proyecto inicializado con `respawn-init`                                      |
| el cliente               | El negocio que contrata                                                       |
| el visitante             | Quien entra a la web del cliente                                              |
| el usuario               | Quien trabaja con el agente                                                   |
| paquete                  | Cada producto de la lista de Respawn                                          |
| concepto                 | La idea de diseño de un proyecto, sacada del mundo del cliente                |
| registro de decisiones   | El archivo de decisiones del proyecto                                         |
| inventario               | Archivo de memoria con ítems de formato fijo                                  |
| bloqueante               | Hallazgo o regla que impide cerrar o entregar                                 |
| recomendación            | Hallazgo que no impide entregar                                               |
| auditoría                | La revisión de entrega de `review-respawn`                                    |
| sello                    | Haber pasado la auditoría sin bloqueantes                                     |
| panel                    | La parte del sistema donde el cliente carga y edita su contenido              |
| pendiente                | Dato que falta y debe dar el cliente; en el texto, `[FALTA: …]`               |

## 3. Palabras en inglés permitidas

Solo en estos casos:

1. **Nombres propios del stack y de herramientas:** Astro, Laravel, Blade,
   Tailwind, HTMX, Pest, Lighthouse, Mercado Pago, y sus clases, comandos,
   archivos y atributos.
2. **Términos del estándar de skills:** `skill` (femenino: la skill),
   `frontmatter`, `kebab-case`, `name`, `description`.
3. **Términos sin reemplazo de uso corriente**, palabra por palabra:

- `slop` — patrones que delatan una web hecha con valores de fábrica.
- `landing page` — la expresión completa; `landing` suelto, solo como nombre
  del paquete.
- `brief` — el documento de relevamiento del proyecto.
- `stack` — conjunto de tecnologías de un proyecto.
- `backend`, `frontend` — las dos mitades de un sistema.
- `framework` — base tecnológica sobre la que se construye.
- `build` — la compilación de un sitio y su resultado.
- `middleware` — capa que procesa un pedido antes de la ruta.
- `job` — trabajo que corre en segundo plano, en la cola.
- `stock` — existencias de un producto.
- `slug` — la parte de una dirección que identifica a un registro.
- `checklist` — lista de verificación; se prefiere la forma en castellano.
- `ADR` — registro de una decisión de arquitectura.
- `link`, `links` — sustantivo. El verbo es «enlazar».
- `token`, `tokens` — variables de diseño: color, tipografía, espaciado.
- `layout` — estructura de una página o vista base.
- `hover` — estado al pasar el cursor.
- `hero` — el primer bloque de una página.
- `CTA` — llamado a la acción.
- `SEO`, `OG`, `schema` — términos de posicionamiento y metadatos.
- `hosting`, `deploy`, `backup` — alojamiento, publicación y copia de
  seguridad; se admiten las dos formas.
- `webhook` — aviso de un servicio externo al sistema.
- `commit`, `PR` — términos de git.
- `script` — archivo ejecutable de `scripts/`.
- `request` — petición HTTP.
- `placeholder` — texto de ejemplo dentro de un campo de formulario.
- `DOM` — la página tal como la arma el navegador, la que se mide.

## 4. Prefijos

Los prefijos van pegados a la base, sin guion: `antipatrón`, `prellenado`,
`reescribir`, `autocarga`. El guion queda solo ante mayúscula o número y en
los términos del stack, que conservan su escritura.

## 5. Palabras bloqueadas

| No                         | Sí                                         |
| -------------------------- | ------------------------------------------ |
| repo                       | repositorio                                |
| default, por default       | predeterminado, de fábrica                 |
| hardcodear, hardcodeado    | escribir a mano, valor fijo                |
| linkear                    | enlazar                                    |
| checkear, chequear         | verificar, comprobar                       |
| commitear, pushear         | hacer commit, subir                        |
| deployar                   | publicar                                   |
| testear                    | probar                                     |
| triggers                   | disparadores                               |
| trigger (verbo), triggerea | disparar, activa                           |
| warnings                   | avisos                                     |
| output                     | salida, resultado                          |
| paths                      | rutas                                      |
| fallback                   | alternativa                                |
| preview                    | vista previa                               |
| template                   | plantilla                                  |
| boilerplate                | de relleno                                 |
| features                   | funciones                                  |
| responsive                 | adaptado al celular, adaptable             |
| mobile                     | celular                                    |
| performance                | rendimiento                                |
| copy                       | textos                                     |
| look, look and feel        | aspecto                                    |
| issue                      | problema                                   |
| fix, fixear                | corrección, corregir                       |
| tip                        | consejo                                    |
| user, users                | usuario, visitante o cliente, según el caso |

`performance` se conserva solo como nombre de la categoría de Lighthouse.
