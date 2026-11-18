# Auditoría · {nombre del proyecto}

- **Fecha:** {AAAA-MM-DD}
- **Dirección:** {dirección auditada} ({producción o vista previa})
- **Paquete:** {paquete del brief}
- **Backend:** {Sí o No}
- **Versión de la casa:** {X.Y.Z}
- **Resultado:** {Con sello | Sin sello: N bloqueantes | Incompleta: N bloqueantes sin comprobar}

## Resumen

| Marca                          | Cantidad |
| ------------------------------ | -------- |
| ❌ Bloqueantes                 | {N}      |
| ❔ Sin comprobar               | {N}      |
| ⚠️ Recomendaciones             | {N}      |
| Bloqueantes cubiertos por un riesgo aceptado | {N} |

## Bloqueantes

### {skill} {ID} · {de qué se trata, en pocas palabras}

- **Dónde:** {página y elemento, o archivo y línea}
- **Qué se observó:** {la evidencia: medición, captura o texto}
- **Cómo se corrige:** {qué cambiar, según la regla}

## Sin comprobar

- **{skill} {ID}:** {por qué no se pudo comprobar} · {qué hace falta para
  comprobarlo}

## Recomendaciones

- **{skill} {ID}:** {dónde} · {qué se observó} · {cómo se corrige}

## Riesgos aceptados

| Riesgo   | Regla que cubre | Condición de revisión |
| -------- | --------------- | --------------------- |
| {RA-NN}  | {skill} {ID}    | {condición}           |

## Mediciones

| Página   | Rendimiento | Accesibilidad | Buenas prácticas | SEO  | LCP     | CLS    | Origen y fecha              |
| -------- | ----------- | ------------- | ---------------- | ---- | ------- | ------ | --------------------------- |
| {ruta}   | {N}         | {N}           | {N}              | {N}  | {N,N s} | {N,NN} | {Lighthouse o PageSpeed}, {AAAA-MM-DD} |

- **Links rotos:** {ninguno, o cada link con la página donde está}
- **Slop Score:** {valor y fecha, o «no medido»}. Es un dato: no aprueba ni
  bloquea.

## Recorrido completo

| Lista              | Reglas                                    |
| ------------------ | ----------------------------------------- |
| `respawn-proyecto` | {cada ID de la lista con su marca}        |
| `respawn-kickoff`  | {…}                                       |
| `respawn-design`   | {…}                                       |
| `respawn-web`      | {…}                                       |
| `respawn-sistemas` | {… o «no aplica: sin backend»}            |
| `review-respawn`   | {…}                                       |

## Plan de correcciones propuesto

| #   | Hallazgo        | Skill que corrige | Propuesta        |
| --- | --------------- | ----------------- | ---------------- |
| 1   | {skill} {ID}    | {skill}           | {qué se haría}   |
