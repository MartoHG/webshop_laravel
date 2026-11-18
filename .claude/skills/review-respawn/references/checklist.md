# Lista de verificación de la auditoría (reglas A a D)

> Reglas propias de `review-respawn`. Las demás reglas que se auditan viven
> en la lista de cada skill dueña. Los IDs son estables: no se reordenan ni
> se reúsan.

## Contenido

- Uso
- A. Mediciones
- B. Sitios que cobran
- C. Alcance
- D. Informe

---

## Uso

1. **Medir y mirar** cada página pública con el procedimiento de
   `navegador.md`.
2. **Evaluar cada regla** como «cumple», «no cumple», «sin comprobar» o «no
   aplica».
3. **Anotar** cada resultado en el informe, con su evidencia.

**Bloqueantes: A1 a A6, B1 a B8, C1, D1 y D2.** El resto se informa como
recomendación.

## A. Mediciones

- **A1.** Cobertura: se midió cada página pública del sitio en modo celular,
  no solo la portada `*(Ver ADR-005)*`.
- **A2.** Rendimiento de Lighthouse: 90 o más en un sitio sin backend; 80 o
  más en las páginas públicas de un sitio con backend `*(Ver ADR-005)*`.
- **A3.** Accesibilidad, Buenas prácticas y SEO de Lighthouse: 90 o más en
  cada página `*(Ver ADR-005)*`.
- **A4.** LCP: hasta 2,5 s en cada página `*(Ver ADR-005)*`.
- **A5.** CLS: hasta 0,1 en cada página `*(Ver ADR-005)*`.
- **A6.** Links rotos: ninguno. Cada link interno y externo de cada página
  lleva a una página que existe `*(Ver ADR-005)*`.
- **A7.** Valor en el límite: un puntaje a menos de 5 puntos de su umbral, un
  LCP a menos de 0,3 s o un CLS a menos de 0,02 se midió tres veces, y vale
  la mediana.
- **A8.** Origen anotado: cada valor lleva la página, la fecha y si salió de
  Lighthouse en la máquina o de PageSpeed Insights.
- **A9.** Margen: un valor entre el umbral y el máximo se informa como
  recomendación, con la página `*(Ver ADR-005)*`.

## B. Sitios que cobran

Aplican a todo sitio que cobra en línea, cualquiera sea el paquete
`*(Ver ADR-015)*`.

- **B1.** Botón de arrepentimiento: un link con el texto
  «BOTÓN DE ARREPENTIMIENTO» se ve al entrar a la portada, sin abrir menús ni
  iniciar sesión (Disposición 954/2025, art. 1).
- **B2.** Sin cuenta: el botón de arrepentimiento y el de baja se usan sin
  crear una cuenta ni iniciar sesión. Si piden verificar la identidad, piden
  solo datos de la compra que el comprador ya tiene, como el número de pedido
  y el correo (Disposición 954/2025, arts. 1 y 4; Disposición 3/2026).
- **B3.** Código de la solicitud: al pedir el arrepentimiento o la baja, el
  sistema le informa al comprador un código de su solicitud por el mismo
  medio, dentro de las 24 horas (Disposición 954/2025, art. 5).
- **B4.** Aviso del plazo: la página de confirmación y el correo de la compra
  dicen que hay diez días corridos para arrepentirse, contados desde la
  entrega o la compra, lo que ocurra último (Ley 24.240, art. 34).
- **B5.** Botón de baja: si se vende un servicio que se paga en forma
  periódica, un link con el texto «BOTÓN DE BAJA DE SERVICIO» cumple las
  condiciones de B1 a B3 y se ve en un lugar destacado (Disposición 954/2025,
  art. 4).
- **B6.** Constancia de baja: después de pedir la baja, el comprador recibe
  sin cargo una constancia dentro de las 72 horas (Ley 24.240, art. 10 ter).
- **B7.** Atención al consumidor: el sitio muestra el teléfono o el canal de
  atención, el área responsable y el horario. Si la atención es solo por
  teléfono o en línea, el horario cubre al menos ocho horas por día hábil, de
  lunes a viernes (Disposición 954/2025, art. 6).
- **B8.** Data Fiscal: la portada muestra el logo del formulario 960/D de
  ARCA con su link a la constancia.
- **B9.** Exceptuados: un producto sin derecho a arrepentimiento figura en el
  registro de decisiones del proyecto con el artículo y el inciso que lo
  exceptúan (Código Civil y Comercial, art. 1116; Disposición 954/2025,
  art. 3). Si todo lo que se vende está exceptuado, B1 a B4 no aplican.

## C. Alcance

- **C1.** Alcance cumplido: cada página, sección y función que el brief lista
  en «Incluye» existe en el sitio y funciona.
- **C2.** Nada de más sin acuerdo: cada función del sitio que el brief no
  incluye tiene su decisión en el registro de decisiones.

## D. Informe

- **D1.** Resultado correcto: el resultado del informe sale de la tabla de
  resultado y sello de la skill `*(Ver ADR-014)*`.
- **D2.** Recorrido completo: el informe muestra cada regla de cada lista que
  aplica, con su marca. Ninguna lista que aplica falta.
- **D3.** Evidencia: cada regla que no cumple dice dónde está, qué se
  observó y cómo se corrige, citando la skill y la regla.
- **D4.** Motivos: cada regla sin comprobar o que no aplica dice por qué.
- **D5.** Riesgos a la vista: cada bloqueante cubierto por un riesgo aceptado
  figura en la sección de riesgos, con el identificador del riesgo.
- **D6.** Datos de la corrida: el informe dice la fecha, la dirección
  auditada, si es producción o vista previa, el paquete y la versión de la
  casa.
- **D7.** Slop Score: figura con su valor y su fecha, o como «no medido».
- **D8.** Informe para el cliente: existe solo con sello y no contiene
  identificadores de reglas, nombres de herramientas ni jerga. Si el sitio
  cobra, aclara que la revisión no reemplaza a un abogado.
