# Lista de verificación de diseño (reglas A a D)

> Reglas verificables de `respawn-design`. Se recorre al cerrar cada pantalla
> y la usa `review-respawn` en la auditoría. Los IDs son estables: no se
> reordenan ni se reúsan.

## Contenido

- Uso
- A. Proceso
- B. Decisiones
- C. Slop
- D. Textos

---

## Uso

1. **Leer** el registro de decisiones del proyecto y los tokens.
2. **Mirar** la pantalla en 360 px de ancho y en escritorio.
3. **Evaluar cada regla** como «cumple», «no cumple» o «no aplica».
4. **Informar** cada regla que no cumple con la pantalla, el elemento y, si
   es código, el archivo y la línea.

**Bloqueantes: A1, A2, B2, B3, C2, C3, D2 y D3.** El resto se informa como
recomendación.

## A. Proceso

- **A1.** Concepto acordado: el registro de decisiones tiene el concepto
  escrito, y es anterior a la primera pantalla.
- **A2.** Decisiones antes del código: tipografía, paleta, espaciado,
  imágenes, movimiento y voz tienen cada una su fila en el registro.
- **A3.** Una pantalla por vez: cada pantalla se mostró al usuario y tuvo su
  aprobación antes de empezar la siguiente.
- **A4.** Dos pasos: el movimiento se agregó después de aprobar la pantalla
  quieta.
- **A5.** Crítica antes del ajuste: cada pantalla mostrada vino acompañada de
  su crítica, no solo de la captura.

## B. Decisiones

- **B1.** Tokens en un solo lugar: colores, familias, tamaños, espacios y
  radios se declaran una vez y el resto del código los usa por nombre.
- **B2.** Paleta propia: ningún color viene de la paleta de fábrica del
  framework.
    - _Incorrecto:_ `bg-indigo-600`, `text-slate-500`.
    - _Correcto:_ `bg-marca`, `text-tinta`, declarados en el tema.
- **B3.** Color con origen: cada color de la paleta tiene su porqué en el
  registro, y el porqué nombra un dato del cliente.
- **B4.** Sin colores sueltos: ningún color escrito a mano fuera de los
  tokens.
- **B5.** Tipografía declarada: como mucho dos familias, y ninguna aparece
  en el código sin estar en los tokens.
- **B6.** Escala tipográfica: todo tamaño de letra sale de la escala.
- **B7.** Escala de espaciado: todo margen y relleno sale de la escala.
- **B8.** Un radio, o una regla de radios escrita en el registro.
- **B9.** Imágenes reales: cada foto es del cliente o tiene anotado su
  origen. Ninguna simula ser el negocio.
- **B10.** Movimiento con propósito: cada animación responde a una acción,
  muestra un cambio de estado, orienta o explica. El propósito figura en el
  registro.
- **B11.** Respuesta rápida: toda animación de interfaz dura menos de 300 ms.

## C. Slop

- **C1.** Pasada hecha: la pantalla se recorrió contra las ocho categorías
  del catálogo y el resultado se informó.
- **C2.** Sin patrones sueltos: cada patrón presente tiene su fila en las
  excepciones del proyecto.
- **C3.** Sin patrones sin excepción: ningún patrón de la lista sin
  excepción del catálogo está presente.
- **C4.** Excepción que resiste el intercambio: cada justificación deja de
  ser cierta al cambiar el cliente por otro de su rubro.
- **C5.** Excepciones acotadas: el proyecto tiene menos excepciones que
  decisiones en su registro.

## D. Textos

- **D1.** Voz: todos los textos usan el voseo, o el trato que el registro
  declara.
- **D2.** Sin hechos inventados: cada cifra, testimonio, precio, horario,
  nombre y logo sale del material del cliente.
- **D3.** Marcadores: donde falta un dato hay un `[FALTA: …]` visible, no un
  texto de relleno.
- **D4.** Botones: cada rótulo dice qué pasa al tocarlo.
- **D5.** Un rótulo por intención en todo el sitio.
- **D6.** Una acción principal por pantalla.
- **D7.** Mayúsculas tipo oración en títulos, botones y menús.
- **D8.** Sin raya larga en ningún texto visible.
- **D9.** Título de portada: dice qué hace el negocio, para quién y dónde,
  entre el título y la bajada.
