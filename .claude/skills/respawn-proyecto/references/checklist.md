# Lista de verificación de la capa proyecto (reglas A a D)

> Reglas verificables del `.ai/` de un proyecto Respawn. La usan
> `respawn-proyecto` al registrar y `review-respawn` al auditar. Los IDs son
> estables: no se reordenan ni se reúsan.

## Contenido

- Uso
- A. Estructura
- B. Decisiones
- C. Inventarios
- D. Entrega y estado

---

## Uso

1. **Leer del disco** el `CLAUDE.md` y todo el `.ai/` del proyecto.
2. **Evaluar cada regla** como «cumple», «no cumple» o «no aplica».
3. **Informar** cada regla que no cumple con su archivo y su línea.

**Bloqueantes: A1, A2, A4, B1, B3, C4, C6, C7 y D1.** El resto se informa
como recomendación.

## A. Estructura

- **A1.** `CLAUDE.md`: su título abre con `Proyecto Respawn` y declara la
  versión de la casa de la que salió la copia de las skills.
- **A2.** Archivos fijos: existen `brief.md`, `decisiones.md`, `estado.md` y
  `entrega.md`, en `.ai/` o en la ruta que declara el `CLAUDE.md`.
- **A3.** Skills declaradas: el `CLAUDE.md` lista qué skills de la casa
  aplican al proyecto, y coincide con el stack del `ADR-P-001`.
- **A4.** Stack registrado: existe `adrs/ADR-P-001` con el stack del
  proyecto. Si el stack no es el de la casa, el ADR dice por qué.
- **A5.** ADRs de proyecto: se llaman `ADR-P-NNN-descripcion.md`, con número
  secuencial que no se reúsa.
- **A6.** Sin archivos vacíos: no existe un inventario sin ítems.
- **A7.** Copia intacta: ningún archivo de `.claude/skills/respawn-*` ni de
  `.claude/skills/review-respawn` fue editado en el proyecto.

## B. Decisiones

- **B1.** Concepto: `decisiones.md` tiene la sección Concepto con un párrafo
  que nombra de qué dato del cliente sale la idea.
- **B2.** Registro: cada fila tiene fecha, tema, decisión y porqué.
- **B3.** Porqué concreto: cada porqué nombra un dato del cliente, del
  visitante o del concepto.
    - _Incorrecto:_ «Se ve más moderno.»
    - _Correcto:_ «El cartel del local usa esa misma letra desde que abrió.»
- **B4.** Una fila por decisión vigente: una decisión que cambió reescribe su
  fila; no hay dos filas que se contradigan.
- **B5.** Excepciones: cada fila nombra el patrón del catálogo, dónde aparece
  y cómo sale del concepto.

## C. Inventarios

- **C1.** Formato: cada ítem de deuda y de riesgo tiene todos sus campos, en
  el orden de `inventarios.md`.
- **C2.** Identificadores: únicos dentro del archivo y en orden ascendente.
- **C3.** Ubicación vigente: toda ruta citada existe en el proyecto.
- **C4.** Sin ítems resueltos: ningún ítem describe un desvío que el código
  ya no tiene.
- **C5.** Riesgos decididos: cada riesgo aceptado tiene Decisión y Condición
  de revisión, y la condición es un hecho comprobable.
- **C6.** Pendientes y marcadores: cada `[FALTA: …]` del proyecto tiene su
  fila en `pendientes-cliente.md`, y cada fila tiene su marcador.
- **C7.** Sin secretos: ningún archivo de la capa contiene contraseñas,
  claves ni tokens.

## D. Entrega y estado

- **D1.** Datos de entrega: antes de la auditoría, `entrega.md` tiene
  completos dominio, hosting, backups, accesos y manual, o dice «no aplica»
  con su motivo.
- **D2.** Estado al día: `estado.md` nombra una etapa de la tabla de etapas y
  un próximo paso que es una acción concreta.
- **D3.** Estado sin historial: `estado.md` tiene un solo resumen de sesión.
- **D4.** Propuestas para la casa: cada una dice qué costó, en qué skill vive
  y qué cambio se sugiere.
