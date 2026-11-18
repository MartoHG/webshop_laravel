---
name: respawn-proyecto
description: >
    Mantiene la memoria de un proyecto Respawn: el .ai/ con el brief, el
    registro de decisiones, los ADRs de proyecto, la deuda técnica, los
    riesgos aceptados, los problemas conocidos, los pendientes del cliente,
    los datos de entrega y el estado. Usar siempre que haya que registrar o
    consultar algo del proyecto: "anotá esta decisión", "dejalo como deuda",
    "esto lo aceptamos así", "falta que el cliente mande las fotos",
    "registrá el stack", "¿dónde estábamos?", "¿qué quedó pendiente?"; al
    posponer un hallazgo, al cerrar una sesión de trabajo y al retomar el
    proyecto. No usar para crear el .ai/ por primera vez (es de
    respawn-init), para relevar al cliente (respawn-kickoff), para decidir qué
    diseño o qué código corresponde (respawn-design, respawn-web,
    respawn-sistemas) ni para cambiar reglas de la casa
    (respawn-skill-creator).
---

# Respawn Proyecto — la memoria de cada proyecto

## Contenido

0. Convenciones
1. Mapa de la capa proyecto
2. Dónde va cada cosa
3. Cuándo se escribe y quién decide
4. Inventarios
5. Decisiones y ADRs de proyecto
6. Estado: cerrar y retomar
7. Lo aprendido que es de la casa
8. Antipatrones
9. Fuentes de esta skill
10. Referencias

---

## 0. Convenciones

Esta skill es la dueña del formato de la capa proyecto `*(Ver ADR-013)*`. Las
demás skills de la casa deciden qué se hace; esta define dónde queda escrito.

- **La memoria vive en el repositorio**, versionada con el código. Nada que
  importe queda solo en la conversación.
- **Los archivos dicen lo vigente.** Lo resuelto se borra y el historial lo
  guarda git.
- **Sin secretos:** ningún archivo guarda contraseñas, claves, tokens ni
  datos personales de terceros.
- **Ruta de la capa:** `.ai/` en la raíz del proyecto. Si el `CLAUDE.md` del
  proyecto declara otra ruta para un archivo, vale la que declara.
- **Leer antes de escribir:** todo archivo de la capa se lee del disco antes
  de modificarlo.
- **Ante un dato que no encaja en ningún archivo:** preguntar al usuario
  dónde va.

Reglas verificables de la capa: [references/checklist.md](references/checklist.md).

---

## 1. Mapa de la capa proyecto

```
{proyecto}/
├── CLAUDE.md                  ← marca Proyecto Respawn, versión de la casa, skills que aplican
├── .claude/skills/            ← copia de las skills de la casa (solo lectura)
└── .ai/
    ├── brief.md               ← cliente, visitante, acción objetivo, material, paquete y alcance
    ├── decisiones.md          ← concepto, excepciones al catálogo de slop y registro de decisiones
    ├── estado.md              ← etapa, próximo paso y bloqueos
    ├── entrega.md             ← dominio, hosting, backups, accesos y manual
    ├── adrs/                  ← ADR-P-NNN: decisiones difíciles de revertir
    ├── deuda-tecnica.md       ← DT-NN: desvíos a corregir
    ├── riesgos-aceptados.md   ← RA-NN: desvíos que se conservan a propósito
    ├── problemas-conocidos.md ← trampas del entorno, con causa y solución
    ├── pendientes-cliente.md  ← lo que falta que el cliente entregue
    ├── auditoria.md           ← informe vigente de la auditoría de entrega
    └── auditoria-cliente.md   ← versión del informe para el cliente, solo con sello
```

- Los cuatro primeros archivos de `.ai/` y `adrs/` existen siempre; los crea
  [respawn-init](../respawn-init/SKILL.md).
- Los cuatro inventarios existen solo mientras tienen ítems: se crean con el
  primer ítem y se borran al quedar vacíos.
- Los dos informes existen desde la primera auditoría. Los escribe
  [review-respawn](../review-respawn/SKILL.md) y esta skill no los edita.
- Toda plantilla vive en `assets/` de esta skill.

---

## 2. Dónde va cada cosa

| Lo que hay que guardar                                             | Archivo                         |
| ------------------------------------------------------------------ | ------------------------------- |
| Quién es el cliente, quién visita, qué acción se busca             | `brief.md`                      |
| Qué entra en el trabajo y qué queda afuera                         | `brief.md`                      |
| La idea de diseño del proyecto                                     | `decisiones.md`, Concepto       |
| Un patrón del catálogo de slop que el concepto justifica           | `decisiones.md`, Excepciones    |
| Una decisión de diseño, contenido, alcance o técnica               | `decisiones.md`, Registro       |
| El stack, o una decisión que cuesta revertir                       | `adrs/ADR-P-NNN`                |
| Un desvío de una regla de la casa que se va a corregir             | `deuda-tecnica.md`              |
| Un desvío que el usuario decide conservar                          | `riesgos-aceptados.md`          |
| Un error del entorno o del deploy que costó entender               | `problemas-conocidos.md`        |
| Un texto, una foto o un acceso que el cliente todavía no entregó   | `pendientes-cliente.md`         |
| A nombre de quién quedan dominio y hosting, y cómo son los backups | `entrega.md`                    |
| En qué etapa está el proyecto y qué sigue                          | `estado.md`                     |
| El resultado de la auditoría de entrega                            | `auditoria.md`                  |
| Una contraseña, una clave o un token                               | Ningún archivo del repositorio  |

Qué decisión de diseño corresponde tomar la define
[respawn-design](../respawn-design/SKILL.md); qué regla técnica se desvió, la
skill dueña de esa regla. Esta skill solo define el registro.

---

## 3. Cuándo se escribe y quién decide

| Momento                                                        | Registro                          | Quién decide                          |
| -------------------------------------------------------------- | --------------------------------- | ------------------------------------- |
| El usuario acuerda una decisión                                | Fila en el Registro               | El agente la anota tras el acuerdo    |
| El usuario acuerda usar un patrón del catálogo de slop         | Fila en Excepciones               | El agente la anota tras el acuerdo    |
| Se elige el stack o algo difícil de revertir                   | `ADR-P-NNN`                       | Decisión explícita del usuario        |
| Se detecta un desvío que no se corrige en la tarea en curso    | Ítem de deuda                     | El agente, con aviso                  |
| El usuario pospone un hallazgo de la auditoría                 | Ítem de deuda                     | El agente, con aviso                  |
| El usuario decide conservar un desvío                          | Ítem de riesgo aceptado           | Decisión explícita del usuario        |
| Falta un dato real para completar un texto o una pantalla      | Fila de pendiente y marcador      | El agente, con aviso                  |
| Un error del entorno llevó más de un intento resolverlo        | Fila de problema conocido         | El agente, con aviso                  |
| Cambia algo de dominio, hosting, backups o accesos             | `entrega.md`                      | El agente, con aviso                  |
| Termina una sesión de trabajo                                  | `estado.md`                       | El agente                             |

- **Aviso:** una línea en la respuesta, con el identificador, el archivo y el
  título. Ejemplo: «Registré DT-03 en `.ai/deuda-tecnica.md`: imágenes del
  catálogo sin tamaño declarado.»
- **Decisión explícita:** el agente plantea el registro y espera el sí del
  usuario antes de escribir `*(Ver ADR-013)*`.
- **Faltante:** donde iría el dato se escribe el marcador visible
  `[FALTA: {qué falta}]`, nunca un texto inventado.
- El agente nunca clasifica por su cuenta un desvío como riesgo aceptado: sin
  decisión del usuario, es deuda.

---

## 4. Inventarios

Los cuatro inventarios comparten el ciclo de vida `*(Ver ADR-013)*`:

- Un ítem resuelto se borra en el mismo cambio que lo resuelve.
- Los identificadores son estables: no se renumeran ni se reúsan.
- Los ítems se ordenan por identificador.
- Un inventario vacío se borra.
- Un ítem cuya ubicación ya no existe se revisa: o se resolvió y se borra, o
  se actualiza la ubicación.

Formato de cada ítem, criterio de severidad y ejemplos:
[references/inventarios.md](references/inventarios.md). Qué severidad de
deuda impide entregar lo define
[review-respawn](../review-respawn/SKILL.md).

---

## 5. Decisiones y ADRs de proyecto

`decisiones.md` tiene tres partes, en este orden:

1. **Concepto:** la idea de diseño del proyecto, en un párrafo.
2. **Excepciones al catálogo de slop:** una fila por patrón presente, con
   dónde aparece y cómo sale del concepto `*(Ver ADR-011)*`.
3. **Registro:** una fila por decisión vigente, con fecha, tema, decisión y
   porqué.

- Una decisión que cambia reescribe su fila y actualiza la fecha.
- El porqué nombra un dato del cliente, del visitante o del concepto. «Queda
  mejor» no es un porqué.

Un `ADR-P-NNN` se reserva para lo difícil de revertir `*(Ver ADR-009)*`:

| Decisión                                              | Registro            |
| ----------------------------------------------------- | ------------------- |
| Stack del proyecto                                    | `ADR-P-001`, siempre |
| Stack distinto al de la casa                          | `ADR-P-001`, con el porqué del desvío |
| Proveedor de pago, de hosting o de correo             | `ADR-P-NNN`         |
| Modelo de datos que condiciona el resto del sistema   | `ADR-P-NNN`         |
| Tipografía, paleta, textos, orden de secciones        | Registro de decisiones |

Plantillas: [assets/decisiones.md](assets/decisiones.md) y
[assets/ADR-P-NNN.md](assets/ADR-P-NNN.md).

---

## 6. Estado: cerrar y retomar

`estado.md` se reescribe entero; no acumula historial.

**Al cerrar una sesión:**

1. Actualizar la etapa si cambió.
2. Escribir el próximo paso como una acción concreta.
3. Listar los bloqueos y de quién depende cada uno.
4. Resumir la sesión en hasta tres líneas.

**Al retomar el proyecto:**

1. Leer `estado.md` y, si existe, `pendientes-cliente.md`.
2. Informar al usuario en hasta tres líneas: etapa, próximo paso y bloqueos.
3. Preguntar si se sigue por el próximo paso anotado.

| Etapa         | Significa                                                    |
| ------------- | ------------------------------------------------------------ |
| Relevamiento  | Se está completando el brief                                 |
| Diseño        | Hay brief; se acuerdan concepto y decisiones                 |
| Desarrollo    | Hay concepto acordado; se construyen las pantallas           |
| Auditoría     | El trabajo está completo y se audita para entregar           |
| Entregado     | Pasó la auditoría sin bloqueantes y el cliente lo recibió    |
| Mantenimiento | Entregado y con mantenimiento contratado                     |

---

## 7. Lo aprendido que es de la casa

Cuando una regla de la casa faltó, estuvo mal o hizo perder trabajo, el
cambio es de la casa, no del proyecto `*(Ver ADR-001)*`.

1. No editar la copia de la skill en `.claude/skills/`.
2. Anotar la propuesta en `estado.md`, bajo «Para la casa»: qué costó, en qué
   skill vive y qué cambio se sugiere.
3. Avisar al usuario en una línea.

La propuesta se borra de `estado.md` cuando el usuario la lleva a la casa o
la descarta.

---

## 8. Antipatrones

| Antipatrón                                                        | Resolución                                                               |
| ----------------------------------------------------------------- | ------------------------------------------------------------------------ |
| Rellenar un dato que falta con un texto verosímil                 | Marcador `[FALTA: …]` y fila en `pendientes-cliente.md`                  |
| Dejar un ítem resuelto «por las dudas»                            | Borrarlo; git guarda el historial                                        |
| Registrar como riesgo aceptado un desvío que nadie decidió        | Registrarlo como deuda y plantear la decisión al usuario                 |
| Guardar una contraseña en `entrega.md`                            | Anotar que el acceso existe y por qué medio se entregó                   |
| Acordar una decisión en la conversación y no anotarla             | Fila en el Registro en la misma respuesta                                |
| Un porqué que no nombra al cliente ni al concepto («se ve mejor») | Reescribirlo con el dato que lo sostiene, o no tomar la decisión         |
| Abrir un ADR de proyecto por una tipografía                       | Fila en el Registro; el ADR es para lo difícil de revertir               |
| Agregar entradas a `estado.md` debajo de las anteriores           | Reescribir el archivo entero                                             |
| Corregir una regla en la copia de la skill del proyecto           | Anotar la propuesta «Para la casa»                                       |
| Dejar un inventario vacío con solo el título                      | Borrar el archivo                                                        |

---

## 9. Fuentes de esta skill

| Fuente                                             | Qué aportó                                                                                   |
| -------------------------------------------------- | -------------------------------------------------------------------------------------------- |
| Sistema de skills de Senda (laravel-htmx-patterns) | Inventarios de deuda técnica y de riesgos aceptados con IDs estables, y problemas conocidos por síntoma, causa y solución |
| Architecture Decision Records (Michael Nygard)     | Registro de una decisión con su contexto y sus consecuencias                                 |

---

## 10. Referencias

| Archivo                                                                    | Contenido                                                                           |
| -------------------------------------------------------------------------- | ----------------------------------------------------------------------------------- |
| [references/inventarios.md](references/inventarios.md)                     | Formato de deuda técnica, riesgos aceptados, problemas conocidos y pendientes       |
| [references/checklist.md](references/checklist.md)                         | Reglas verificables de la capa proyecto                                             |
| [assets/CLAUDE.md](assets/CLAUDE.md)                                       | Plantilla del `CLAUDE.md` de un proyecto Respawn                                    |
| [assets/brief.md](assets/brief.md)                                         | Plantilla del brief                                                                 |
| [assets/decisiones.md](assets/decisiones.md)                               | Plantilla del concepto, las excepciones y el registro de decisiones                 |
| [assets/estado.md](assets/estado.md)                                       | Plantilla del estado                                                                |
| [assets/entrega.md](assets/entrega.md)                                     | Plantilla de los datos de entrega                                                   |
| [assets/ADR-P-NNN.md](assets/ADR-P-NNN.md)                                 | Plantilla de ADR de proyecto                                                        |
| [assets/deuda-tecnica.md](assets/deuda-tecnica.md)                         | Plantilla del inventario de deuda técnica                                           |
| [assets/riesgos-aceptados.md](assets/riesgos-aceptados.md)                 | Plantilla del inventario de riesgos aceptados                                       |
| [assets/problemas-conocidos.md](assets/problemas-conocidos.md)             | Plantilla de problemas conocidos                                                    |
| [assets/pendientes-cliente.md](assets/pendientes-cliente.md)               | Plantilla de pendientes del cliente                                                 |
| [evals/activacion.json](evals/activacion.json)                             | Consultas de activación de esta skill                                               |
| [../respawn-init/SKILL.md](../respawn-init/SKILL.md)                       | Creación del `.ai/` y copia de las skills                                           |
| [../respawn-design/SKILL.md](../respawn-design/SKILL.md)                   | Qué decisiones de diseño son obligatorias y cuándo vale una excepción               |
| [../review-respawn/SKILL.md](../review-respawn/SKILL.md)                   | Qué se exige de la capa proyecto para entregar                                      |
