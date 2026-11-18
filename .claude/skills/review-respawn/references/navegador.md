# Mirar y medir en el navegador

> Referencia de `review-respawn`: cómo se recorre cada página del sitio, cómo
> se mide y qué cuenta como evidencia. Los ejemplos usan marcadores
> (`{dirección}`, `{página}`).

## Contenido

1. Antes de empezar
2. Recorrido de una página
3. Medir el DOM
4. Links
5. Lighthouse
6. Compra de prueba
7. Trampas conocidas
8. Al terminar

---

## 1. Antes de empezar

- **Lista de páginas:** las de `sitemap.xml` más una dirección que no existe,
  para ver la página de error. Si el sitio tiene panel, sus pantallas se
  recorren aparte y no se miden con Lighthouse.
- **Navegador:** el que tenga el agente, con herramienta para ejecutar
  JavaScript en la página y para fijar el tamaño de la ventana.
- **Producción o vista previa:** se anota cuál es. En una vista previa, el
  `noindex` es lo esperado y las reglas de producción quedan sin comprobar.
- **Versión publicada:** se confirma que la dirección sirve el último build,
  comparando un texto o un archivo que cambió en el último commit.

## 2. Recorrido de una página

Cada página se recorre en este orden:

1. **Cargar limpia a 360 px de ancho**, sin caché, y leer la consola.
2. **Medir** el desborde horizontal y lo que pida la lista en uso.
3. **Mirar** la página entera con las listas de diseño y de sitio.
4. **Repetir** a 1280 px de ancho, como escritorio.
5. **Juntar** los links de la página para comprobarlos aparte.

- Con teclado y con el JavaScript desactivado se recorre lo que la lista de
  respawn-web pide así.
- Una animación de entrada se deja terminar antes de mirar. Si el sitio
  respeta la preferencia de menos movimiento, se mira también con ella
  activada.

## 3. Medir el DOM

La captura sirve para mirar el conjunto. La evidencia es lo que devuelve el
DOM:

| Qué se comprueba            | Qué se mide                                                         |
| --------------------------- | ------------------------------------------------------------------- |
| Desborde horizontal         | `document.documentElement.scrollWidth` contra `clientWidth`         |
| Tamaño y posición           | `getBoundingClientRect()` del elemento                              |
| Colores, márgenes, fuentes  | `getComputedStyle()` del elemento                                   |
| Contraste                   | Los dos colores calculados del texto y de su fondo                  |
| Zonas táctiles              | Alto y ancho del control, o de su zona de toque                     |
| Rótulos de formularios      | Que cada `label[for]` apunte a un `id` existente                    |

- Ante una medición que contradice la captura, vale la medición.
- Un elemento que no se puede medir, como un texto sobre una foto, se
  informa con la captura y queda como «sin comprobar» si la regla es
  bloqueante.

## 4. Links

1. **Juntar** las direcciones de todos los `a[href]` de cada página.
2. **Pedir** cada dirección una sola vez, siguiendo las redirecciones.
3. **Clasificar** cada respuesta con la tabla.

| Respuesta                                                   | Resultado                                                  |
| ----------------------------------------------------------- | ---------------------------------------------------------- |
| 200 después de las redirecciones                            | Cumple                                                     |
| 404, 410 o un error 5xx                                     | Link roto                                                  |
| 403 o 429 de un sitio externo                               | Abrirlo en el navegador: si carga, cumple                  |
| Ancla `#{id}` dentro de la página                           | Cumple si existe un elemento con ese `id`                  |
| `wa.me`, `tel:` o `mailto:`                                 | No se pide: su formato es de la lista de respawn-web       |

## 5. Lighthouse

Se mide cada página en modo celular, con las cuatro categorías.

| La máquina tiene                    | Camino                                                                                   |
| ----------------------------------- | ---------------------------------------------------------------------------------------- |
| Chrome o Chromium                   | `npx lighthouse {dirección} --output=json --output-path={carpeta temporal}/{página}.json` |
| Ninguno de los dos                  | Pedirle al usuario el resultado de PageSpeed Insights de cada página, pestaña celular     |

- De cada página se anotan los cuatro puntajes, el LCP y el CLS.
- El resultado se guarda en una carpeta temporal del agente, nunca en el
  repositorio.
- Un valor cerca del umbral se mide tres veces y vale la mediana; el margen
  está en la lista de verificación.
- Si no se pudo medir ni el usuario pasó el resultado, la medición queda sin
  comprobar.

## 6. Compra de prueba

Solo en sitios que cobran. Comprueba lo que no se ve en la portada: el
código de la solicitud, el aviso del plazo y la constancia de baja.

1. **Usar el entorno de prueba**, con las credenciales de prueba de Mercado
   Pago. Nunca una compra real.
2. **Comprar** un producto y guardar la página de confirmación y el correo.
3. **Pedir el arrepentimiento** desde el botón, sin iniciar sesión.
4. **Guardar** la respuesta: el código de la solicitud y el medio por el que
   llegó.

- Si no hay entorno de prueba, lo que solo se ve comprando queda sin
  comprobar y se le pregunta al usuario cómo seguir.
- En producción se comprueba solo lo que se ve sin comprar: los botones, los
  datos de atención y el logo de Data Fiscal.

## 7. Trampas conocidas

| Trampa                            | Síntoma                                                        | Qué hacer                                                         |
| --------------------------------- | -------------------------------------------------------------- | ----------------------------------------------------------------- |
| Captura recortada o vacía         | La captura no muestra lo que dice el DOM                       | Medir; la captura no es evidencia                                 |
| Animación a medio camino          | Un elemento aparece corrido o transparente                     | Esperar a que termine, o mirar con menos movimiento activado      |
| Versión vieja en el servidor      | La página no tiene el último cambio                            | Confirmar la versión publicada antes de seguir                    |
| Referencias de elementos caducas  | Un elemento leído antes apunta a otro después de recargar      | Volver a buscar el elemento después de cada carga                 |
| Puntaje de Lighthouse que varía   | Dos corridas dan resultados distintos                          | Tres corridas y la mediana                                        |

## 8. Al terminar

- Devolver el navegador al tamaño de escritorio.
- Borrar los archivos temporales de la medición.
- Comprobar con `git status` que el repositorio del proyecto no cambió.
