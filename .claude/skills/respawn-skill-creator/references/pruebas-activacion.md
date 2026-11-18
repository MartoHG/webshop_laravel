# Prueba de activación

> Referencia de `respawn-skill-creator`: cómo comprobar que la `description`
> de una skill hace que el agente la active cuando corresponde y la deje
> cuando no. La lista de verificación comprueba la forma; esta prueba, el
> comportamiento.

## Archivo de consultas

Cada skill tiene `evals/activacion.json`: una lista de objetos con dos claves.

| Clave            | Contenido                                               |
| ---------------- | ------------------------------------------------------- |
| `query`          | Un pedido como lo escribiría el usuario                 |
| `should_trigger` | `true` si la skill debe activarse; `false` si no        |

- Al menos doce consultas, la mitad negativas.
- Las positivas incluyen pedidos que no nombran la skill ni su tema con las
  palabras de la `description`.
- Las negativas son casos límite: comparten vocabulario con la skill pero
  son de una skill vecina de la casa.
- Sin clientes reales ni precios en las consultas.

## Procedimiento

1. **Juntar las `description`** de todas las skills de la casa.
2. **Asignar cada consulta** a una skill leyendo solo esas descripciones, sin
   el cuerpo. Lo hace una sesión nueva o un subagente que no participó de la
   edición.
3. **Comparar** la asignación con `should_trigger`.
4. **Ajustar la `description`** ante cada consulta mal asignada: sumar el
   disparador que falta o el límite negativo hacia la skill vecina.
5. **Repetir** con todas las consultas hasta que ninguna falle.

Si no se puede decidir de qué skill es una consulta con las reglas vigentes,
preguntar al usuario antes de tocar la `description`.

## Antipatrones

| Antipatrón                                             | Resolución                                                         |
| ------------------------------------------------------ | ------------------------------------------------------------------ |
| Consultas genéricas («hacé una web»)                   | Pedidos con contexto: tipo de negocio, paquete, pantalla, archivo  |
| Negativas obvias («escribí una función en Python»)     | Negativas que comparten vocabulario con la skill                   |
| Ajustar la `description` a una sola consulta           | Generalizar al tipo de pedido que la consulta representa           |
| Quien editó la skill juzga sus propias consultas       | Sesión nueva o subagente sin el contexto de la edición             |
| Sumar disparadores hasta que la skill se activa siempre | Revisar si las negativas de las skills vecinas siguen pasando      |
