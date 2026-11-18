# El panel del cliente

> Referencia de `respawn-sistemas`: qué tiene que poder hacer el cliente solo,
> cómo se lo protege de romper el sitio y qué lleva el manual. Las reglas
> verificables están en `checklist.md`; los textos del panel, en la skill
> `respawn-design`.

## Contenido

1. La prueba del panel
2. Qué edita el cliente
3. Qué no edita el cliente
4. Protecciones
5. Fotos
6. Inicio del panel
7. Manual de uso
8. Usuarios y accesos

## 1. La prueba del panel

Un panel está terminado cuando el cliente puede hacer sus tareas de todos los
días sin escribirle a nadie. Antes de entregar, se recorre cada tarea de la
lista del brief como si la hiciera el cliente:

1. Entrar al panel desde el celular.
2. Hacer la tarea de punta a punta sin mirar el código ni la base.
3. Ver el resultado en el sitio.
4. Deshacer un error cometido a propósito.

Si en algún paso hizo falta saber algo que solo sabe quien programó, el panel
no está terminado.

## 2. Qué edita el cliente

| Paquete              | El cliente edita desde el panel                                                                 |
| -------------------- | ----------------------------------------------------------------------------------------------- |
| Web institucional    | Textos de cada página, fotos, servicios o catálogo, horarios, datos de contacto y redes         |
| Tienda               | Lo anterior, más productos, categorías, precios, stock, fotos de producto y estado de los pedidos |
| Sistema a medida     | Los registros de cada módulo que opera, sus catálogos y sus usuarios                            |

- La lista exacta de tareas sale del brief y se anota en el registro de
  decisiones.
- Un dato que el cliente va a cambiar alguna vez no se escribe en una vista:
  se guarda en la base y se edita desde el panel.
- Los datos del negocio viven en un solo registro de configuración, y de ahí
  los toman el sitio, los datos estructurados y los links de contacto.

## 3. Qué no edita el cliente

| No se expone                                            | Por qué                                              |
| ------------------------------------------------------- | ---------------------------------------------------- |
| Estructura de las páginas, colores y tipografía         | Son decisiones de diseño registradas                 |
| Direcciones de las páginas fijas                        | Cambiarlas rompe links y posicionamiento             |
| Código, HTML suelto o estilos                           | Un error deja el sitio roto y es un riesgo de seguridad |
| Configuración técnica: correo, pagos, entorno           | Vive en el entorno, no en el panel                   |

Si el cliente necesita cambiar algo de esta lista, es un trabajo de
mantenimiento, no una pantalla más.

## 4. Protecciones

- **Guardar y publicar son pasos distintos.** Un registro a medio cargar no
  aparece en el sitio.
- **Lo eliminado se recupera.** Va a una papelera, o no se elimina sin una
  confirmación que nombra el registro.
- **Lo que está en uso no se elimina:** el panel explica por qué y qué se
  puede hacer en cambio.
- **Los campos obligatorios están marcados** y el error dice cómo corregirlo.
- **El texto largo se edita como texto,** con negrita, listas y links, sin
  HTML a la vista. Lo que se guarda se sanea en el servidor.
- **Un precio vacío o en cero no se publica** sin una confirmación.
- **El panel avisa lo que hizo:** cada guardado muestra su aviso.
- **La sesión vencida no hace perder lo escrito:** el panel avisa y pide
  recargar.

## 5. Fotos

El cliente sube la foto como la tiene: del celular, pesada y con cualquier
proporción.

- El panel acepta JPG, PNG y WebP de hasta el tamaño que fija la validación,
  y lo dice junto al campo.
- Al subir, el sistema genera las versiones que usa el sitio. El cliente no
  recorta ni convierte nada fuera del panel.
- Cada foto pide su texto alternativo, con una ayuda: «Contá en una frase qué
  se ve en la foto».
- La proporción en que se muestra la define el diseño. El panel enseña cómo
  va a quedar recortada.
- Donde el orden importa, el cliente lo cambia con botones de subir y bajar.

## 6. Inicio del panel

La primera pantalla responde lo que el cliente viene a hacer, no muestra
estadísticas de adorno.

| Paquete            | Qué muestra el inicio                                                    |
| ------------------ | ------------------------------------------------------------------------ |
| Web institucional  | Accesos a las tareas frecuentes y lo que quedó sin publicar              |
| Tienda             | Pedidos que esperan una acción, productos sin stock y accesos frecuentes |
| Sistema a medida   | Lo pendiente del día para el rol que entró                               |

Un número aparece solo si el cliente toma una decisión con él.

## 7. Manual de uso

El manual es un entregable del proyecto y se escribe con el panel terminado.

- **Una sección por tarea,** titulada como la diría el cliente: «Cambiar un
  precio», «Sumar una foto a la galería».
- **Cada sección:** dónde se entra, los pasos numerados, una captura de la
  pantalla real y cómo ver el resultado en el sitio.
- **Una sección de problemas comunes:** qué hacer si no se puede entrar, si
  una foto no sube o si algo se eliminó por error.
- **Sin términos técnicos.** Usa las mismas palabras que los rótulos del
  panel.
- **Formato:** un documento que el cliente abre desde el celular. Dónde queda
  guardado se anota en los datos de entrega.
- El manual no explica cómo funciona el sistema por dentro.

## 8. Usuarios y accesos

- Cada persona entra con su propio usuario. No hay un usuario compartido.
- La contraseña inicial la define la persona con un link de un solo uso. No
  se manda una contraseña por mensaje.
- El dueño del negocio puede crear y dar de baja usuarios sin ayuda, cuando
  el sistema tiene más de uno.
- Quién tiene cada acceso y por qué medio se entregó figura en los datos de
  entrega. La contraseña, nunca.
- El usuario de quien desarrolló se conserva solo si hay mantenimiento
  contratado, y figura en los datos de entrega.
