---
name: respawn-sistemas
description: >
    Arquitectura de los proyectos Respawn con backend: paneles, tiendas y
    sistemas a medida en Laravel, Blade, Tailwind y HTMX. Usar siempre que se
    cree, modifique o revise un módulo o un CRUD, un Controller, un Form
    Request, un Service, un Rule Object, un modelo, una migración, permisos,
    seeders o tests de Pest; el panel que usa el cliente y su manual; un
    listado con filtros, una carga rápida o una fila editable con HTMX; la
    integración con Mercado Pago y sus webhooks; subida de archivos, límites
    de tasa, backups, variables de entorno o deploy. No usar para decidir cómo
    se ve o qué dice una pantalla (es de respawn-design), para las reglas de
    las páginas públicas como SEO, accesibilidad y rendimiento (respawn-web)
    ni para auditar antes de entregar (review-respawn).
---

# Respawn Sistemas — paneles, tiendas y sistemas a medida

## Contenido

0. Convenciones
1. Principios rectores
2. Stack
3. Capas
4. Modelos y migraciones
5. Validación y autorización
6. Frontend: Blade, Tailwind y HTMX
7. El panel del cliente
8. Tiendas y Mercado Pago
9. Seguridad
10. Operación: entorno, backups y deploy
11. Tests
12. Antipatrones
13. Fuentes de esta skill
14. Referencias

---

## 0. Convenciones

Esta skill vale para todo proyecto con backend `*(Ver ADR-003)*`. Las reglas
verificables viven en [references/checklist.md](references/checklist.md) y el
código de cada patrón, en [references/patrones.md](references/patrones.md).
Este cuerpo los introduce.

- **Un proyecto legado** conserva su stack; de esta skill toma las capas, la
  seguridad, el panel y la operación.
- **Un desvío que no se corrige en la tarea** se registra como deuda con
  [respawn-proyecto](../respawn-proyecto/SKILL.md).
- **El código existente se toma con pinzas:** un módulo que no cumple las
  reglas se corrige; no se copia como patrón.
- **Ante una decisión de negocio que el brief no resuelve:** preguntar al
  usuario. El agente no inventa reglas del negocio del cliente.

---

## 1. Principios rectores

> **El servidor decide todo lo que se ve.** Cada estado visible es una
> respuesta HTML del servidor; ninguna variable de JavaScript guarda estado.

> **El cliente maneja su negocio sin llamar a nadie.** Lo que cambia seguido
> se edita desde el panel, con las palabras del negocio.

Antes de escribir código nuevo se recorre esta escalera y se frena en el
primer escalón que resuelve:

1. ¿Lo pide un requerimiento real del brief? Si es por las dudas, no va.
2. ¿Ya existe en el proyecto? Se reutiliza.
3. ¿Lo resuelve Laravel, HTML o Tailwind tal como vienen?
4. ¿Se resuelve en el Service o en el Controller que ya está, sin clases
   nuevas?
5. Recién entonces, código nuevo, con las líneas indispensables.

---

## 2. Stack

| Pieza                     | Rol                                                                                   |
| ------------------------- | ------------------------------------------------------------------------------------- |
| Laravel                   | Framework. Controllers que orquestan, Form Requests que validan y Services con la lógica |
| MySQL                     | Base de datos de producción                                                           |
| Blade                     | Vistas. Una misma vista sirve la página completa y, con `@fragment`, su parte recargable |
| Tailwind                  | Estilos, con el tema del proyecto. Sin la paleta de fábrica                           |
| HTMX                      | Interacción sin recargar: listados, carga rápida y acciones de una fila               |
| Pest                      | Tests                                                                                 |
| spatie/laravel-permission | Roles y permisos, cuando el sistema tiene dos o más roles                             |
| Pint y Larastan           | Formato y análisis estático, solo en desarrollo                                       |

Toda otra dependencia se anota en el registro de decisiones con su motivo. Un
panel con un único tipo de usuario no instala el paquete de permisos: le
alcanza con la autenticación.

---

## 3. Capas

Todo se organiza por módulo, con el mismo nombre en todas las capas.

```
app/
├── Http/Controllers/{Modulo}/   ← orquestan: autorizan, delegan y responden
├── Http/Requests/{Modulo}/      ← validan la forma de lo que entra
├── Servicios/{Modulo}/          ← lógica de negocio y acceso a datos
├── Rules/                       ← reglas de validación reutilizables
├── Models/                      ← modelos Eloquent
├── Policies/                    ← pertenencia: de quién es cada registro
├── Enums/                       ← estados y catálogos fijos
└── Jobs/                        ← trabajo en segundo plano
```

| Capa         | Hace                                                          | Nunca hace                                        |
| ------------ | ------------------------------------------------------------- | ------------------------------------------------- |
| Controller   | Autoriza, toma lo validado, llama al Service y responde       | Consultar la base ni decidir reglas de negocio    |
| Form Request | Valida presencia, tipo, formato y largo                       | Consultar reglas que dependen del estado          |
| Service      | Reglas de negocio, consultas y transacciones                  | Recibir un `Request` ni comprobar permisos        |
| Rule Object  | Una validación compleja o que se usa en más de un formulario  | Mensajes escritos a mano en el código             |

**Tipos completos, sin excepción:** todo parámetro, todo retorno y toda
propiedad llevan su tipo.

Reglas por capa: [references/checklist.md](references/checklist.md).
Plantillas: [references/patrones.md](references/patrones.md).

---

## 4. Modelos y migraciones

- **El código de dominio va en castellano:** tablas, columnas, modelos,
  métodos y permisos. Los verbos de un recurso (`index`, `store`, `update`)
  y lo que impone el framework quedan como son.
- **Estados y catálogos fijos son Enums,** nunca cadenas sueltas en el código.
- **La plata es `decimal`** y se calcula sin punto flotante.

| Momento del proyecto                                | Cómo se cambia el esquema                                              |
| --------------------------------------------------- | ---------------------------------------------------------------------- |
| Antes del primer deploy a producción                | Se edita la migración original y se recrea la base                     |
| Con datos reales en producción                      | Solo migraciones nuevas; una migración aplicada no se toca             |

Convenciones de nombres, claves foráneas e índices:
[references/checklist.md](references/checklist.md).

---

## 5. Validación y autorización

La validación tiene tres niveles y cada error sale por el mismo canal:

| Qué se valida                                              | Dónde                                             |
| ---------------------------------------------------------- | ------------------------------------------------- |
| Forma: presencia, tipo, largo, formato                     | Form Request                                      |
| Regla compleja o repetida                                  | Rule Object                                       |
| Regla de negocio que depende del estado                    | Service, con `ValidationException`                |

La autorización también se reparte:

| Pregunta                               | Dónde                               |
| -------------------------------------- | ----------------------------------- |
| ¿Puede hacer esta acción?              | Controller o Form Request           |
| ¿Este registro es suyo?                | Policy                              |
| ¿Qué registros puede ver?              | Service, al armar la consulta       |

Una verificación hecha para mostrar u ocultar un botón es comodidad, no
seguridad: la regla se vuelve a comprobar en el Service en cada mutación.

---

## 6. Frontend: Blade, Tailwind y HTMX

### Página propia o carga rápida

Un diálogo sirve para agilizar una carga que se repite. Para lo que se hace
de vez en cuando, estorba.

| La tarea…                                                                    | Forma                                   |
| ---------------------------------------------------------------------------- | --------------------------------------- |
| Se repite muchas veces seguidas o a diario, y su formulario es corto         | Carga rápida: diálogo sobre el listado  |
| Cambia un solo dato de una fila: precio, stock, publicado                    | En el lugar, sobre la fila              |
| Es poco frecuente, o su formulario es largo: fotos, texto extenso, muchos campos | Página propia                       |

- La frecuencia sale del brief: quién actualiza el contenido y cada cuánto.
- Sin ese dato, el formulario va en página propia.
- La forma elegida para cada módulo se anota en el registro de decisiones.

### Contrato de respuestas

| Interacción                         | Pedido                                    | Respuesta                                                         |
| ----------------------------------- | ----------------------------------------- | ----------------------------------------------------------------- |
| Listar, filtrar, buscar y paginar   | `GET` al listado                          | Página completa; con `HX-Request`, el fragment del listado        |
| Abrir una carga rápida              | `GET` con destino en el diálogo           | El formulario                                                     |
| Guardar desde la carga rápida       | `POST` o `PUT`, sin intercambio           | `204` con `HX-Trigger`: `listadoCambio`, `cerrarDialogo` y `aviso` |
| Cambiar un dato de una fila         | `PATCH` con destino en la fila            | El fragment de la fila, con `HX-Trigger`: `aviso`                 |
| Eliminar                            | `DELETE` con confirmación, sin intercambio | `204` con `HX-Trigger`: `listadoCambio` y `aviso`                |
| Guardar desde una página propia     | `POST` o `PUT` común, sin HTMX            | Redirección con mensaje de sesión                                 |
| Regla o validación que falla        | Cualquiera de las mutaciones              | `422`; el puente pinta el error junto al campo o como aviso       |

- **Los filtros y la página viven en la dirección.** El listado se vuelve a
  pedir a sí mismo cuando escucha `listadoCambio`, y así conserva lo que la
  persona estaba viendo.
- **Un solo puente:** los oyentes globales de HTMX viven en un único archivo,
  copiado de [assets/puente-htmx.js](assets/puente-htmx.js). Abre y cierra el
  diálogo, pinta los errores y muestra los avisos.
- **Las páginas públicas funcionan sin JavaScript;** el panel puede depender
  de HTMX. Las reglas de las páginas públicas son de
  [respawn-web](../respawn-web/SKILL.md).

Plantillas de cada interacción: [references/patrones.md](references/patrones.md).

---

## 7. El panel del cliente

El panel se entrega para que el cliente lo use solo. Se construye con cuatro
reglas de base:

- **Todo lo que cambia seguido se edita desde el panel:** textos, fotos,
  productos, precios, horarios y datos de contacto. Nada de eso queda escrito
  en el código.
- **Habla como el negocio.** Ningún nombre de tabla, de modelo ni de campo
  técnico llega a la pantalla.
- **No deja romper el sitio.** El cliente no puede dejar una página sin
  título ni un producto sin precio, y lo que borra se puede recuperar.
- **Se entrega con su manual,** escrito con las pantallas reales.

Qué tiene que poder hacer el cliente, las protecciones y el manual:
[references/panel-cliente.md](references/panel-cliente.md). El diseño del
panel es de [respawn-design](../respawn-design/SKILL.md).

---

## 8. Tiendas y Mercado Pago

Una tienda cobra de verdad: un pago aplicado dos veces o un pedido pagado que
no se registra son errores con plata del cliente.

- **El aviso de Mercado Pago no es la verdad:** avisa que algo cambió. El
  estado del pago se consulta a la API y recién entonces se aplica.
- **Aplicar un pago es idempotente:** recibir diez veces el mismo aviso deja
  el pedido igual que recibirlo una.
- **El webhook responde enseguida** y el trabajo va a una cola.
- **Una conciliación programada** recupera los pagos cuyo aviso nunca llegó.
- **El importe se verifica** contra el del pedido antes de darlo por pagado.

Flujo completo, firma del aviso, estados y pruebas:
[references/mercado-pago.md](references/mercado-pago.md). Los botones legales
de una tienda los exige [review-respawn](../review-respawn/SKILL.md).

---

## 9. Seguridad

| Frente             | Lo que se cuida                                                                    |
| ------------------ | ---------------------------------------------------------------------------------- |
| Acceso             | Todo el panel detrás de la autenticación; contraseñas con la política del framework |
| Entrada            | Solo datos validados llegan a un modelo                                            |
| Pertenencia        | Nadie accede al registro de otro cambiando un número en la dirección               |
| Archivos           | Tipo real, tamaño y formato controlados; lo privado no se sirve desde la carpeta pública |
| Límites de tasa    | En el ingreso, en los formularios públicos, en el webhook y en las exportaciones   |
| Datos personales   | Se guarda lo mínimo y no se escribe en los registros del sistema                   |
| Secretos           | Solo en el entorno; nunca en el repositorio ni en la memoria del proyecto          |

Reglas con su detalle: [references/checklist.md](references/checklist.md).

---

## 10. Operación: entorno, backups y deploy

Un sistema entregado sigue andando sin su autor: se respalda solo, avisa
cuando falla y se puede restaurar.

- **Backup automático y diario** de la base y de los archivos subidos, a un
  lugar fuera del servidor.
- **Una restauración probada** antes de entregar, con su fecha anotada en los
  datos de entrega.
- **Entorno de producción cerrado:** sin modo de depuración y sin
  herramientas de desarrollo.
- **Deploy repetible:** los mismos pasos, escritos, cada vez.

Pasos y reglas: [references/operacion.md](references/operacion.md).

---

## 11. Tests

Se escriben con Pest y cubren decisiones, no el framework.

| Se prueba                                                        | No se prueba                                   |
| ---------------------------------------------------------------- | ---------------------------------------------- |
| Cada regla de negocio del Service, en su caso válido y en el que falla | Que Laravel valide un `required`         |
| Cada permiso y cada pertenencia: quién puede y quién no          | Pantallas de solo maquetado                    |
| El contrato de respuestas de cada mutación                       | Lo mismo dos veces, en el Service y por HTTP   |
| Todo el recorrido de un pago, con avisos repetidos y fuera de orden | La API de Mercado Pago en vivo              |

Convenciones: [references/checklist.md](references/checklist.md).

---

## 12. Antipatrones

| Antipatrón                                                              | Resolución                                                                 |
| ----------------------------------------------------------------------- | -------------------------------------------------------------------------- |
| Service que recibe el `Request`                                         | El Controller le pasa lo validado y los modelos de la ruta                 |
| Consulta a la base en un Controller o en una vista                      | Llevarla al Service                                                        |
| `$request->all()` hacia un modelo                                       | `$request->validated()`                                                    |
| Parámetro o retorno sin tipo                                            | Agregar el tipo; si es un arreglo, describir su forma                      |
| Estado guardado en una variable de JavaScript                           | Pedirle al servidor el HTML del estado nuevo                               |
| Diálogo para un formulario que se usa una vez por mes                   | Página propia                                                              |
| Página propia para cargar cuarenta productos seguidos                   | Carga rápida sobre el listado                                              |
| Devolver el listado entero como respuesta de un guardado                | `204` y `listadoCambio`: el listado se vuelve a pedir con sus filtros      |
| Oyentes de HTMX repartidos por las vistas                               | Un solo puente                                                             |
| Texto del sitio escrito en una vista                                    | Campo editable desde el panel                                              |
| «Categoría_id» o «slug» como rótulo de un campo del panel               | La palabra que usa el cliente                                              |
| Dar por pagado un pedido con lo que dice el aviso                       | Consultar el pago a la API y verificar el importe                          |
| Procesar el aviso dentro del webhook                                    | Responder y despachar un job                                               |
| Backup que nadie restauró nunca                                         | Restaurarlo en otra base y anotar la fecha                                 |
| Editar una migración que ya corrió en producción                        | Migración nueva                                                            |
| Instalar un paquete para algo que el framework ya hace                  | Recorrer la escalera y usar lo que viene                                   |

---

## 13. Fuentes de esta skill

| Fuente                                                       | Qué aportó                                                                                         |
| ------------------------------------------------------------ | -------------------------------------------------------------------------------------------------- |
| Sistema de skills de Senda (laravel-htmx-patterns)           | Capas, el Service sin `Request`, validación y autorización en tres niveles, y el servidor como dueño del estado |
| Tienda de El Refugio                                         | Flujo de Mercado Pago: firma, job, aplicación idempotente y conciliación; convenciones de nombres  |
| Documentación de Laravel                                     | Form Requests, Policies, `@fragment`, colas, tareas programadas y límites de tasa                  |
| Documentación de HTMX                                        | `HX-Trigger`, `HX-Request`, pedidos a la dirección actual e intercambios                           |
| Documentación de Mercado Pago para desarrolladores           | Formato del aviso, firma, reintentos y clave de idempotencia                                       |
| OWASP: guías de autenticación, subida de archivos y sesiones | Controles de seguridad del panel                                                                   |

---

## 14. Referencias

| Archivo                                                        | Contenido                                                                              |
| -------------------------------------------------------------- | -------------------------------------------------------------------------------------- |
| [references/checklist.md](references/checklist.md)             | Reglas verificables por capa, frontend, panel, seguridad, tiendas, operación y tests   |
| [references/patrones.md](references/patrones.md)               | Código de cada patrón: capas, listado, carga rápida, fila editable y página propia     |
| [references/panel-cliente.md](references/panel-cliente.md)     | Qué edita el cliente, protecciones del panel y manual de uso                           |
| [references/mercado-pago.md](references/mercado-pago.md)       | Flujo de cobro, firma del aviso, estados, conciliación y pruebas                       |
| [references/operacion.md](references/operacion.md)             | Entorno, backups con restauración probada, colas, tareas programadas y deploy          |
| [assets/puente-htmx.js](assets/puente-htmx.js)                 | Puente HTMX del panel, para copiar al proyecto                                         |
| [evals/activacion.json](evals/activacion.json)                 | Consultas de activación de esta skill                                                  |
| [../respawn-design/SKILL.md](../respawn-design/SKILL.md)       | Diseño y textos del panel                                                              |
| [../respawn-web/SKILL.md](../respawn-web/SKILL.md)             | Reglas de las páginas públicas                                                         |
| [../respawn-proyecto/SKILL.md](../respawn-proyecto/SKILL.md)   | Registro de decisiones, deuda y datos de entrega                                       |
| [../review-respawn/SKILL.md](../review-respawn/SKILL.md)       | Auditoría de entrega y requisitos legales de una tienda                                |
