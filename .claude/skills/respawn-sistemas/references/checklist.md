# Lista de verificación de sistemas (reglas A a L)

> Reglas verificables de `respawn-sistemas`. Se recorre al cerrar cada módulo
> y la usa `review-respawn` en la auditoría. Los IDs son estables: no se
> reordenan ni se reúsan. El código de cada patrón está en `patrones.md`.

## Contenido

- Uso
- A. Transversales
- B. Modelos y migraciones
- C. Validación
- D. Services
- E. Controllers y rutas
- F. Autorización
- G. Frontend: Blade, Tailwind y HTMX
- H. Panel del cliente
- I. Seguridad
- J. Tiendas y Mercado Pago
- K. Operación
- L. Tests y siembra

---

## Uso

1. **Leer** los archivos del módulo de adentro hacia afuera: migración y
   modelo, Form Requests, Service, Controller y rutas, vistas y tests.
2. **Evaluar cada regla** como «cumple», «no cumple» o «no aplica».
3. **Correr** el formateador, el análisis estático y los tests del módulo.
4. **Informar** cada regla que no cumple con el archivo y la línea.

**Bloqueantes: A1, C5, D1, D6, E3, E8, F2, F3, G1, H1, H5, I1 a I4, I6, I8,
J1 a J5, K1 a K4 y L4.** El resto se informa como recomendación.

## A. Transversales

- **A1.** Tipos completos: todo parámetro, todo retorno y toda propiedad
  declaran su tipo. Un arreglo describe su forma en el PHPDoc.
    - _Incorrecto:_ `public function crear($datos)`.
    - _Correcto:_ `public function crear(array $datos): Producto`, con
      `@param array{nombre: string, precio: string} $datos`.
- **A2.** `declare(strict_types=1)` al comienzo de cada archivo PHP propio.
- **A3.** Código de dominio en castellano: tablas, columnas, modelos,
  métodos, variables y permisos. Quedan en inglés los verbos de un recurso y
  los nombres que impone el framework.
- **A4.** Mismo nombre de módulo en todas las capas: carpeta de Controllers,
  de Form Requests, de Servicios, de vistas y de tests.
- **A5.** Sin lógica en las vistas: una vista no consulta modelos ni calcula
  reglas de negocio.
- **A6.** Comentarios que explican una decisión, no lo que el código ya dice.
  Ningún comentario cita ADRs, reglas ni skills.
- **A7.** Formato y análisis: el formateador no deja cambios pendientes y el
  análisis estático no reporta errores nuevos.
- **A8.** Dependencias anotadas: cada paquete fuera del stack de la casa
  figura en el registro de decisiones con su motivo.

## B. Modelos y migraciones

- **B1.** Tablas en castellano y en plural; columnas en castellano, con
  nombres que se entienden sin contexto.
- **B2.** Clave foránea con `foreignId('{singular}_id')->constrained('{tabla}')`
  y su comportamiento al borrar siempre explícito.
- **B3.** Índice único compuesto con nombre explícito.
- **B4.** Estados y catálogos fijos como Enum con valor de texto, convertido
  en `casts()`. Ninguna cadena suelta en el código compara un estado.
- **B5.** Importes en `decimal`, convertidos con `decimal:2`. Ningún cálculo
  de plata usa punto flotante.
- **B6.** `casts()` como método, solo con conversiones reales.
- **B7.** Relaciones con tipo de retorno, declaradas solo cuando el módulo
  las usa.
- **B8.** Borrado lógico en todo registro que el cliente puede eliminar desde
  el panel.
- **B9.** Antes del primer deploy a producción se edita la migración
  original. Con datos reales en producción, solo migraciones nuevas.
- **B10.** Cada migración nueva tiene su `down()` o declara que no se
  revierte.
- **B11.** Cambio de esquema sincronizado: en el mismo cambio se actualizan
  el modelo, la factory, el seeder, los Form Requests y los tests.

## C. Validación

- **C1.** Una clase Form Request por mutación: `Guardar{Modelo}Request` y
  `Actualizar{Modelo}Request`.
- **C2.** `Rule::exists` y `Rule::unique` con la clase del modelo, nunca con
  el nombre de la tabla en una cadena. En una actualización se ignora el
  registro propio.
- **C3.** Mensajes en `lang/es/validation.php`, con el nombre legible de cada
  campo en `attributes`. Un Form Request no declara `messages()`.
- **C4.** Mensajes con voseo y con la palabra que usa el cliente para el
  campo.
- **C5.** Archivos: toda subida valida tipo real, extensión y tamaño máximo.
  No se aceptan formatos que ejecutan código en el navegador o en el
  servidor, incluido SVG.
- **C6.** Rule Object para una regla compleja o usada en más de un
  formulario, con su mensaje en `lang/`.
- **C7.** Un campo de catálogo valida contra el Enum, no contra una lista
  escrita a mano.

## D. Services

- **D1.** El Service nunca recibe un `Request`: recibe arreglos validados,
  valores simples y modelos.
- **D2.** Métodos de persistencia: `crear(array $datos): Modelo`,
  `actualizar(Modelo $modelo, array $datos): Modelo` y
  `eliminar(Modelo $modelo): void`.
- **D3.** Listado: `listar(array $filtros, int $pagina): LengthAwarePaginator`,
  con `withQueryString()`.
- **D4.** Página fuera de rango: si la página pedida supera a la última, el
  listado devuelve la última.
- **D5.** Una regla de negocio que falla lanza `ValidationException`, con la
  clave del campo o, si no corresponde a un campo, con el nombre del módulo.
- **D6.** La regla se comprueba en la mutación: lo que impide editar o
  eliminar vive en un método del Service, y `actualizar` y `eliminar` lo
  llaman siempre.
- **D7.** Toda operación que escribe en más de una tabla va en
  `DB::transaction`.
- **D8.** Relaciones cargadas por anticipado: ningún listado dispara una
  consulta por fila.
- **D9.** El Service no comprueba permisos por nombre. Sí acota qué registros
  ve cada rol.
- **D10.** SQL crudo siempre con parámetros. Una columna de orden que llega
  del pedido se valida contra una lista cerrada.
- **D11.** Dependencias por constructor, como `private readonly`.

## E. Controllers y rutas

- **E1.** El Controller recibe su Service por constructor y no consulta la
  base.
- **E2.** Cada acción autoriza antes de delegar.
- **E3.** Solo datos validados: una mutación pasa `$request->validated()`.
  Nunca `$request->all()` ni `$request->input()` sin filtrar.
- **E4.** Sin `try/catch` genérico: la `ValidationException` del Service
  sigue su curso y produce el 422.
- **E5.** `index` responde la vista completa y, con `HX-Request`, el fragment
  del listado. El nombre del fragment coincide entre la vista y el
  Controller.
- **E6.** Respuesta de cada mutación según el contrato de respuestas de la
  skill.
- **E7.** `HX-Trigger` armado con `json_encode` y `JSON_THROW_ON_ERROR`.
- **E8.** Rutas del panel dentro de un grupo con autenticación. Los modelos
  anidados se resuelven por la relación del padre, con `scopeBindings()`.
- **E9.** Rutas con nombre, direcciones en castellano y en kebab-case, y
  Controllers referenciados por clase.
- **E10.** Un recurso hijo se crea por la relación del padre que viene en la
  ruta. El formulario no manda la clave del padre.
- **E11.** Sin JSON: un Controller del panel responde vistas, fragments,
  redirecciones o `204`.

## F. Autorización

- **F1.** Permiso de la acción en el Controller o en el Form Request.
- **F2.** Pertenencia en una Policy: toda acción sobre un registro puntual
  comprueba que quien pide puede tocar ese registro.
- **F3.** Ninguna ruta recibe un identificador como entero para saltear la
  resolución del modelo y su Policy.
- **F4.** Permisos sembrados de forma idempotente, con nombres en castellano
  y en minúsculas: `ver productos`, `editar productos`.
- **F5.** El criterio de pertenencia se escribe una vez, como scope del
  modelo, y lo usan la Policy y el Service.
- **F6.** Un sistema con un único tipo de usuario no instala el paquete de
  permisos.

## G. Frontend: Blade, Tailwind y HTMX

- **G1.** Sin estado en el navegador: ningún script guarda en una variable un
  dato que decide lo que se ve.
- **G2.** Forma de cada formulario según su frecuencia: carga rápida, en el
  lugar o página propia, anotada en el registro de decisiones.
- **G3.** El contenedor del listado se vuelve a pedir a la dirección actual
  con `hx-get=""` y `hx-trigger="listadoCambio from:body"`.
- **G4.** Filtros y página en la dirección: el formulario de filtros y la
  paginación actualizan la dirección del navegador.
- **G5.** Formulario de carga rápida con `hx-swap="none"` y `novalidate`.
- **G6.** Un solo puente: los oyentes globales de HTMX están en un único
  archivo, cargado en el layout del panel.
- **G7.** El layout del panel tiene el `<dialog id="dialogo">`, la región
  `#avisos` y el `<meta name="csrf-token">`.
- **G8.** Eliminación con confirmación que nombra el registro.
- **G9.** Una página propia envía con un formulario común, muestra cada error
  con `@error` y conserva lo escrito con `old()`.
- **G10.** Un mensaje después de una redirección se imprime en la región de
  avisos del layout.
- **G11.** Componentes Blade para lo que se repite: campo con su rótulo y su
  error, botón, tabla.
- **G12.** Colores, tipografía y espacios desde el tema del proyecto.
- **G13.** Toda dirección que llega al historial responde también como página
  completa.

## H. Panel del cliente

- **H1.** Editable desde el panel: textos de las páginas, fotos, productos o
  servicios, precios, horarios y datos de contacto. Ninguno está escrito en
  una vista ni en el código.
- **H2.** Vocabulario del negocio: ningún rótulo, mensaje ni título muestra
  el nombre de una tabla, de un modelo o de una columna.
- **H3.** Campos obligatorios marcados y con ayuda donde el rótulo no
  alcanza.
- **H4.** Vista previa o link «Ver en el sitio» desde cada registro
  publicable.
- **H5.** Nada se pierde por error: lo eliminado va a una papelera de la que
  se recupera, o exige una confirmación que nombra el registro.
- **H6.** Publicar es un paso aparte: un registro se puede guardar sin
  publicarlo.
- **H7.** Fotos: el panel acepta la foto como venga, la achica y la convierte
  solo, y pide su texto alternativo.
- **H8.** Listados con búsqueda cuando superan una pantalla, y ordenados como
  le sirve al cliente.
- **H9.** El panel se usa desde el celular: a 360 px no hay desplazamiento
  horizontal y las acciones se alcanzan.
- **H10.** Manual de uso entregado, con las pantallas reales y una tarea por
  sección.
- **H11.** Cada rol ve solo lo que usa.

## I. Seguridad

- **I1.** Todo el panel exige sesión iniciada. No existe una ruta de
  administración fuera del grupo autenticado.
- **I2.** Sin usuarios de fábrica: ningún seeder crea en producción un
  usuario con una contraseña conocida.
- **I3.** Límite de tasa en el ingreso, en la recuperación de contraseña, en
  los formularios públicos, en el webhook y en toda exportación.
- **I4.** CSRF activo en toda ruta que muta. La única excepción es el
  webhook, que valida firma.
- **I5.** Contraseñas con `Password::defaults()` y almacenadas con hash.
- **I6.** Archivos privados fuera de la carpeta pública, servidos por un
  Controller que autoriza.
- **I7.** Nombre de archivo generado por el sistema: nunca se guarda con el
  nombre que mandó el navegador.
- **I8.** Sin datos sensibles en los registros del sistema ni en las
  cabeceras: contraseñas, tokens, documentos, teléfonos ni el pedido
  completo.
- **I9.** Salida escapada: `{{ }}` en las vistas. `{!! !!}` solo sobre
  contenido saneado por el servidor.
- **I10.** Sesión con cookie segura y solo HTTP en producción.
- **I11.** Dependencias sin avisos de seguridad sin tratar: `composer audit`
  y `npm audit` sin hallazgos de producción, o con su decisión anotada.
- **I12.** Datos personales mínimos: el sistema pide solo los que usa.

## J. Tiendas y Mercado Pago

- **J1.** El webhook valida la firma antes de hacer cualquier otra cosa y
  rechaza con 401 la que no coincide.
- **J2.** El estado de un pago se toma de la consulta a la API, nunca del
  cuerpo del aviso.
- **J3.** Aplicar un pago es idempotente: la misma notificación, repetida o
  fuera de orden, deja el pedido en el mismo estado.
- **J4.** Aplicación dentro de una transacción con el pedido bloqueado para
  escritura.
- **J5.** El importe cobrado se compara con el del pedido antes de marcarlo
  pagado. Si difiere, el pago queda en revisión.
- **J6.** El webhook responde enseguida y despacha un job.
- **J7.** Cada intento de pago lleva su clave de idempotencia y su referencia
  externa, que permite encontrarlo desde el aviso.
- **J8.** Conciliación programada: una tarea consulta los pagos pendientes de
  más de diez minutos y aplica su resultado.
- **J9.** Credenciales en el entorno; las de prueba y las de producción no se
  mezclan.
- **J10.** Precios y stock se vuelven a leer del servidor al crear el pedido.
  Nada de lo que manda el navegador define un importe.
- **J11.** El pedido guarda una copia del nombre y del precio de cada ítem al
  momento de la compra.
- **J12.** Contracargos y reembolsos dejan el pedido en revisión y avisan al
  administrador.
- **J13.** El cliente ve en el panel el estado de cada pedido y de cada pago
  con palabras, no con los códigos de Mercado Pago.

## K. Operación

- **K1.** Producción con `APP_ENV=production` y `APP_DEBUG=false`.
- **K2.** `.env` fuera del repositorio. `.env.example` lista cada variable,
  sin valores reales.
- **K3.** Backup automático diario de la base y de los archivos subidos, en
  un lugar fuera del servidor.
- **K4.** Restauración probada: el backup se restauró en una base aparte y la
  fecha figura en los datos de entrega.
- **K5.** Retención: se conservan al menos siete backups diarios y cuatro
  semanales.
- **K6.** Aviso de falla: un backup, un job o una tarea programada que falla
  manda un aviso a una casilla que alguien lee.
- **K7.** Cola y tareas programadas en marcha en producción, con reinicio
  automático.
- **K8.** Deploy escrito en el README: los mismos pasos, en el mismo orden.
- **K9.** Cachés de configuración, rutas y vistas generadas en cada deploy.
- **K10.** Herramientas de desarrollo ausentes en producción: instalación sin
  dependencias de desarrollo y sin rutas de depuración.
- **K11.** HTTPS forzado.
- **K12.** Zona horaria y configuración regional del negocio declaradas en la
  configuración.

## L. Tests y siembra

- **L1.** Tests con Pest, en `tests/Feature/{Modulo}` para HTTP y en
  `tests/Unit/{Modulo}` para Services, reglas y Policies.
- **L2.** Nombres en castellano que dicen el comportamiento.
- **L3.** Cada regla de negocio del Service tiene un test del caso válido y
  uno del que falla.
- **L4.** Cada acción protegida tiene un test de quién puede y de quién no,
  incluido el acceso al registro de otro.
- **L5.** Cada mutación con HTMX prueba su estado y su `HX-Trigger`.
- **L6.** Mercado Pago se prueba con respuestas simuladas: pago aprobado,
  rechazado, pendiente, aviso repetido, aviso con firma inválida e importe
  distinto.
- **L7.** Factories válidas: lo que genera una factory pasa las reglas del
  Form Request.
- **L8.** Seeders idempotentes para catálogos y permisos. Los datos de
  demostración no corren en producción.
- **L9.** Los tests no dependen de datos sembrados ni del orden en que
  corren.
- **L10.** Se corre el subconjunto del módulo tocado, no la suite entera, en
  un cambio puntual.
