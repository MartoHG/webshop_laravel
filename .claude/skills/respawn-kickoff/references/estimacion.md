# Estimación de un sistema a medida

> Referencia de `respawn-kickoff`: cómo se arma la tabla de módulos y horas
> de un sistema. La tabla va al brief, sin importes. Las horas las decide el
> usuario; el agente propone y dice en qué se apoya.

## Contenido

1. Qué es un módulo
2. Cómo se desglosa
3. Partidas que no son módulos
4. Supuestos
5. Lo que agranda un módulo
6. Formato de la tabla
7. Después de entregar

## 1. Qué es un módulo

Un módulo es una cosa que el cliente administra, con todo lo que necesita
para funcionar: su tabla, su listado, sus formularios, sus reglas y sus
permisos.

| Es un módulo                                        | No es un módulo                                   |
| --------------------------------------------------- | ------------------------------------------------- |
| Productos, turnos, clientes, pedidos                | «El backend», «la base de datos», «el diseño»     |
| Un reporte que alguien usa para decidir             | Una pantalla suelta de otro módulo                |
| Una integración con un servicio externo             | Un campo más en un formulario                     |

## 2. Cómo se desglosa

1. **Listar las tareas** del bloque «Quién actualiza» y de las ramas del
   relevamiento.
2. **Agrupar** las tareas por la cosa que administran: cada grupo es un
   módulo.
3. **Escribir qué hace** cada módulo en una oración, con las palabras del
   cliente.
4. **Separar** lo que tiene reglas propias: un cálculo, un estado que cambia,
   un aviso, un límite.
5. **Sumar** las partidas que no son módulos.

- Un módulo con una regla de negocio compleja se parte en dos filas: lo
  básico y la regla.
- Lo que el cliente «quizás quiera más adelante» no entra en la tabla: va a
  «No incluye».

## 3. Partidas que no son módulos

Estas filas existen en todo sistema y son las que más se olvidan. Se incluyen
siempre, con sus horas.

| Partida                              | Qué cubre                                                               |
| ------------------------------------ | ----------------------------------------------------------------------- |
| Base del proyecto                    | Ingreso, layout del panel, tokens, roles y estructura inicial           |
| Diseño                               | Concepto, decisiones y revisión pantalla por pantalla                   |
| Páginas públicas                     | El sitio que ve el visitante, si el sistema lo tiene                    |
| Carga inicial de contenido           | Subir los datos reales, si la hace Respawn                              |
| Tests                                | Reglas de negocio, permisos y pagos                                     |
| Puesta en producción                 | Servidor, entorno, cola, tareas programadas y dominio                   |
| Backups y restauración probada       | Configurar, programar y restaurar una vez                               |
| Manual de uso                        | Una sección por tarea, con capturas                                     |
| Auditoría y correcciones             | Recorrer la auditoría de entrega y corregir lo que salga                |
| Rondas de cambios                    | Las rondas acordadas en el alcance                                      |

- Una partida que no aplica se deja en la tabla con cero horas y su motivo.
  Así se ve que se pensó.

## 4. Supuestos

Cada fila declara lo que se dio por cierto. Si el supuesto cambia, cambian
las horas, y eso queda dicho de antemano.

- **Cantidad:** cuántos registros, cuántos usuarios, cuántas pantallas.
- **Reglas:** qué reglas de negocio se conocen y cuáles faltan definir.
- **Material:** qué entrega el cliente y en qué estado.
- **Terceros:** qué servicios externos se usan y que su cuenta ya existe.

| Incorrecto                    | Correcto                                                                    |
| ----------------------------- | --------------------------------------------------------------------------- |
| «Turnos: {horas}»             | «Turnos: un profesional, turnos de duración fija, sin pago previo: {horas}» |
| «Reportes: {horas}»           | «Reportes: uno, de ventas por mes, para ver en pantalla: {horas}»           |

## 5. Lo que agranda un módulo

Antes de proponer las horas de una fila se recorre esta lista. Cada sí suma,
y se nombra en el supuesto.

- ¿Tiene estados que cambian y reglas sobre quién los cambia?
- ¿Maneja plata, fechas u horarios?
- ¿Lo usan dos roles que ven cosas distintas?
- ¿Sube archivos o fotos?
- ¿Manda avisos por correo o por mensaje?
- ¿Se conecta con un servicio externo?
- ¿Necesita carga rápida o edición en el lugar?
- ¿Tiene que funcionar bien desde el celular con muchos datos?
- ¿Hay datos existentes para traer?

Una fila con tres o más sí se parte en dos.

## 6. Formato de la tabla

```markdown
| Módulo o partida     | Qué hace                                        | Supuestos                               | Horas   |
| -------------------- | ----------------------------------------------- | --------------------------------------- | ------- |
| {módulo}             | {una oración, en palabras del cliente}          | {cantidad, reglas, material, terceros}  | {horas} |
| Base del proyecto    | Ingreso, layout del panel, roles                | {cuántos roles}                         | {horas} |
| **Total**            |                                                 |                                         | {suma}  |
```

- Las horas son un número por fila. Si el agente no puede sostener un número,
  propone un mínimo y un máximo y dice qué dato cierra la diferencia.
- Una fila con `[FALTA: …]` en los supuestos no lleva horas.
- El total es la suma de las filas, sin un margen escondido. Si el usuario
  quiere un margen, va en su propia fila.
- La tabla no lleva importes ni el valor hora.

## 7. Después de entregar

Al cerrar el proyecto, las horas reales se anotan junto a las estimadas en el
brief. Una diferencia grande en una partida se anota además en el estado,
como propuesta para la casa: sirve para estimar mejor el próximo sistema.
