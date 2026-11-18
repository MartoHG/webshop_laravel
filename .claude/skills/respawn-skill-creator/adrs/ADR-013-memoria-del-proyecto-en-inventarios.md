# ADR-013: La memoria del proyecto vive en inventarios dentro de su `.ai/`

**Referenciado en:** `respawn-proyecto`, `review-respawn`

---

## Contexto

Un proyecto de cliente se trabaja en muchas sesiones, a veces con semanas de
pausa. Lo que se decidió, lo que quedó pendiente, lo que se pospuso y lo que
costó descubrir se pierde si queda en la conversación o en la memoria de quien
desarrolla. Sin registro, un desvío pospuesto se confunde con una regla, y un
faltante del cliente se tapa con relleno.

## Alternativas evaluadas

| Opción                                              | Por qué se descartó                                                 |
| --------------------------------------------------- | ------------------------------------------------------------------- |
| Memoria del agente o de la herramienta              | No viaja con el repositorio ni la ve otra persona                   |
| Un único archivo de notas libres                    | Sin formato no se puede verificar ni saber qué sigue vigente        |
| Tareas en una herramienta externa                   | Separa el registro del código que describe                          |
| Inventarios con formato fijo en el `.ai/` del proyecto | — (es la decisión)                                               |

## Decisión

**Cada proyecto guarda su memoria en archivos de formato fijo dentro de
`.ai/`, versionados con el código. `respawn-proyecto` es la dueña de ese
formato.**

- Cada tipo de dato tiene un solo archivo: decisiones, deuda técnica, riesgos
  aceptados, problemas conocidos, pendientes del cliente, datos de entrega y
  estado.
- Los inventarios describen lo vigente. Un ítem resuelto se borra; el historial
  lo guarda git.
- Los identificadores de un inventario son estables y no se reúsan.
- Deuda, pendientes y problemas conocidos los registra el agente con un aviso
  de una línea. Un riesgo aceptado y un ADR de proyecto exigen una decisión
  explícita del usuario.
- Ningún inventario guarda credenciales ni datos personales.

## Consecuencias

### Ganamos

- Un proyecto se retoma leyendo su `.ai/`.
- La auditoría distingue un desvío registrado de uno ignorado.

### Perdemos / Compromisos

- Mantener los inventarios es trabajo en cada sesión.
- Un inventario desactualizado engaña más que la falta de inventario.

### Vigilancia requerida

- Ítems cuya ubicación ya no existe en el código.
- Desvíos conocidos que no figuran en ningún inventario.
- Texto de relleno donde debería haber un pendiente registrado.
