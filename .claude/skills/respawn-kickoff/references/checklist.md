# Lista de verificación del kickoff (reglas A a D)

> Reglas verificables de `respawn-kickoff`. Se recorre al cerrar el
> relevamiento y la usa `review-respawn` para comprobar que el proyecto se
> entregó contra un alcance escrito. Los IDs son estables.

## Contenido

- Uso
- A. Relevamiento
- B. Paquete y alcance
- C. Estimación
- D. Cierre

---

## Uso

1. **Leer** el brief, el registro de decisiones, el `ADR-P-001` y los
   pendientes del cliente.
2. **Evaluar cada regla** como «cumple», «no cumple» o «no aplica».
3. **Informar** cada regla que no cumple, con el archivo y el campo.

**Bloqueantes: A1, A2, B1, B3, B4, B6 y D1.** El resto se informa como
recomendación.

## A. Relevamiento

- **A1.** Brief completo: cada campo del brief tiene su dato o un
  `[FALTA: …]`. Ninguno está vacío ni con el texto de la plantilla.
- **A2.** Sin datos inventados: cada dato del negocio salió del usuario, del
  cliente o de una fuente que se puede nombrar.
- **A3.** Acción objetivo única, con su canal y quién lo atiende.
- **A4.** Diferencial concreto: «Qué lo distingue» es un hecho comprobable,
  no un adjetivo.
- **A5.** Tareas con frecuencia: cada cosa que cambia después de publicar
  tiene quién la cambia y cada cuánto.
- **A6.** Faltantes con dueño: cada `[FALTA: …]` figura en los pendientes del
  cliente y dice quién lo consigue.
- **A7.** Ramas resueltas: cada rama que se abrió tiene sus respuestas o sus
  faltantes.

## B. Paquete y alcance

- **B1.** Paquete nombrado como figura en la web de Respawn.
- **B2.** Paquete leído: lo que incluye se tomó de la web en el momento del
  kickoff, no de memoria.
- **B3.** «Incluye» nombra cada página, sección y función acordada.
- **B4.** «No incluye» nombra cada cosa que se habló y quedó afuera.
- **B5.** Dominio y hosting figuran como aparte, con quién los contrata y a
  nombre de quién.
- **B6.** Pedidos fuera de alcance resueltos: cada pedido que se salió del
  paquete tiene su decisión en el registro: cambio de paquete, etapa
  posterior, alternativa más chica o exclusión.
- **B7.** Sin importes: ni el brief ni ningún archivo del proyecto contiene
  precios ni el valor hora.
- **B8.** Obligaciones anotadas: si el proyecto cobra en línea, junta datos
  personales o tiene suscripciones, el alcance lo dice.
- **B9.** Mantenimiento definido: contratado o no, y qué pasa con los cambios
  después de entregar.

## C. Estimación

- **C1.** Una fila por módulo, con qué hace en una oración.
- **C2.** Supuestos escritos en cada fila.
- **C3.** Partidas fijas presentes: base, diseño, tests, puesta en
  producción, backups, manual, auditoría y rondas de cambios.
- **C4.** Sin número suelto: ninguna fila con un faltante en sus supuestos
  lleva horas.
- **C5.** El total es la suma de las filas.

## D. Cierre

- **D1.** Stack registrado: el `ADR-P-001` dice el stack y sale de la
  pregunta del backend. Si es una excepción, dice por qué.
- **D2.** `CLAUDE.md` completo: cliente, paquete, stack, acción objetivo y
  skills que aplican, sin texto de plantilla.
- **D3.** Skills coherentes: un proyecto sin backend no lista
  `respawn-sistemas`.
- **D4.** Estado actualizado: etapa y próximo paso concretos.
- **D5.** Alcance entregado: el usuario recibió el texto del alcance para el
  cliente.
