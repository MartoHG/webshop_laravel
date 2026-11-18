# Operación: entorno, backups y deploy

> Referencia de `respawn-sistemas`: lo que hace que un sistema entregado siga
> andando sin su autor. Las reglas verificables están en `checklist.md`. Los
> datos de cada proyecto se anotan en sus datos de entrega.

## Contenido

1. Entornos
2. Variables de entorno
3. Backups
4. Probar una restauración
5. Colas y tareas programadas
6. Deploy
7. Avisos de falla

## 1. Entornos

| Entorno      | Datos                  | Depuración | Indexación | Mercado Pago         |
| ------------ | ---------------------- | ---------- | ---------- | -------------------- |
| Local        | De demostración        | Activa     | No         | Credenciales de prueba |
| Vista previa | De demostración        | Apagada    | No         | Credenciales de prueba |
| Producción   | Reales                 | Apagada    | Sí         | Credenciales de producción |

- Producción nunca corre con datos de demostración ni con usuarios sembrados.
- La vista previa no comparte base ni archivos con producción.

## 2. Variables de entorno

- `.env` nunca entra al repositorio. `.env.example` lista todas las variables
  con valores de ejemplo que no sirven en ningún entorno.
- Las vistas y los Services leen la configuración con `config()`. `env()`
  aparece solo dentro de los archivos de `config/`.
- Un secreto que se filtró se cambia en el servicio que lo emitió. Borrarlo
  del repositorio no alcanza.
- Dónde están guardadas las variables de producción figura en los datos de
  entrega. Los valores, no.

## 3. Backups

| Qué                                   | Regla                                                           |
| ------------------------------------- | --------------------------------------------------------------- |
| Contenido                             | La base de datos completa y los archivos subidos                |
| Frecuencia                            | Una vez por día, por una tarea programada                       |
| Destino                               | Un lugar fuera del servidor del sitio y fuera de su cuenta de hosting |
| Retención                             | Siete diarios y cuatro semanales, como mínimo                   |
| Aviso                                 | Un backup que falla manda un correo                             |
| Acceso                                | El cliente puede llegar a sus backups sin quien desarrolló      |

- La herramienta que hace el backup se anota en el registro de decisiones.
- El código no se respalda acá: vive en el repositorio.
- Un backup en el mismo servidor que el sitio no cuenta como backup.

## 4. Probar una restauración

Un backup sirve cuando se restauró. Se prueba antes de entregar y después de
cada cambio en la forma de respaldar.

1. **Bajar** el último backup desde su destino.
2. **Restaurar** la base en una base aparte, nunca sobre la de producción.
3. **Levantar** el sistema local apuntando a esa base y a los archivos
   restaurados.
4. **Comprobar** que se puede entrar, que los listados tienen los registros
   recientes y que las fotos se ven.
5. **Anotar** la fecha y el resultado en los datos de entrega.

Si un paso falla, el backup no está probado y la entrega queda bloqueada.

## 5. Colas y tareas programadas

Los avisos por correo, el procesamiento de pagos, la conciliación y los
backups dependen de dos procesos que tienen que estar siempre en marcha.

| Proceso              | Qué corre                                        | Cómo se mantiene                                   |
| -------------------- | ------------------------------------------------ | -------------------------------------------------- |
| Trabajador de la cola | Jobs: correos, avisos de pago, imágenes         | Un supervisor que lo reinicia si se cae            |
| Tareas programadas   | Backups, conciliación de pagos, limpieza         | Una entrada de cron por minuto                     |

- Después de cada deploy se reinicia el trabajador de la cola, para que tome
  el código nuevo.
- Un job que falla todos sus intentos queda registrado y avisa.
- En un hosting que no permite procesos en segundo plano, la cola se procesa
  desde las tareas programadas. Esa limitación se anota en el registro de
  decisiones.

## 6. Deploy

Los pasos se escriben en el README del proyecto, en orden, y se siguen
iguales cada vez.

1. Activar el modo de mantenimiento.
2. Traer el código y instalar las dependencias sin las de desarrollo.
3. Compilar los recursos del frontend.
4. Correr las migraciones.
5. Regenerar las cachés de configuración, de rutas y de vistas, reiniciar la
   cola y salir del modo de mantenimiento.

- Antes de un deploy con migraciones se confirma que el backup del día
  existe.
- Una migración que borra o transforma datos se prueba antes sobre una copia
  restaurada.
- El primer deploy a producción crea el usuario del cliente con un link para
  que defina su contraseña. No siembra usuarios con contraseña conocida.

## 7. Avisos de falla

- Los errores de producción se registran con fecha y contexto, sin datos
  personales ni secretos.
- Un error que deja una función sin andar manda un aviso a una casilla que
  alguien lee.
- La casilla y quién la lee figuran en los datos de entrega.
- Los registros viejos se rotan: no llenan el disco.
