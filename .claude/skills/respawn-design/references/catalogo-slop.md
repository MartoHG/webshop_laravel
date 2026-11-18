# Catálogo de slop

> Referencia de `respawn-design`: los patrones que delatan una web hecha con
> valores de fábrica. Todo patrón de este catálogo está prohibido por defecto.
> Los IDs son estables: no se reordenan ni se reúsan.

## Contenido

1. Cómo se usa
2. Patrones sin excepción
3. Estructura
4. Tipografía
5. Color
6. Componentes
7. Íconos
8. Imágenes
9. Movimiento
10. Textos

## 1. Cómo se usa

Un patrón no delata por feo: delata porque aparece igual en negocios que no
tienen nada en común. Lo que se ve es que nadie eligió.

- **Al diseñar:** antes de proponer una pantalla, revisar que ninguna decisión
  sea un patrón de este catálogo.
- **En la pasada de slop:** recorrer las ocho categorías sobre la pantalla
  terminada y anotar cada patrón presente con su ID.
- **Ante un patrón presente**, solo hay dos salidas: quitarlo, o justificarlo
  con el concepto y anotarlo en las excepciones del proyecto.

### Prueba de intercambio

Una justificación vale si deja de ser cierta al cambiar el cliente por otro
de su mismo rubro.

| Justificación                                                              | ¿Vale? |
| -------------------------------------------------------------------------- | ------ |
| «La letra monoespaciada es la de la pantalla de un arcade, que es el concepto» | Sí: otro servicio técnico no la tendría |
| «El fondo oscuro le da un aire tecnológico»                                | No: vale para cualquier negocio de tecnología |
| «Queda más prolijo»                                                        | No: no nombra al cliente ni al concepto |
| «Así salió»                                                                | No     |

### Columnas

- **Por qué delata:** qué ve un visitante atento cuando el patrón aparece.
- **En cambio:** la decisión que el patrón está reemplazando.

## 2. Patrones sin excepción

Estos no son decisiones de estilo: son engaños al visitante o defectos.
Ningún concepto los justifica y no admiten fila de excepción.

**Sin excepción: C9, K16, F3, F7, F8, M8, X8, X9, X10, X11 y X20.**

## 3. Estructura

| ID  | Patrón | Por qué delata | En cambio |
| --- | ------ | -------------- | --------- |
| E1  | Esqueleto de plantilla: portada, logos, tres beneficios, testimonios, precios y cierre, en ese orden | Cualquier negocio entra en el mismo molde sin cambiar una sección | Ordenar las secciones según las preguntas que trae el visitante del brief |
| E2  | Hero centrado con título, bajada y dos botones | Es la primera pantalla de casi toda web generada | Abrir con lo que solo este negocio puede mostrar y una sola acción |
| E3  | Tres tarjetas iguales con ícono, título y dos renglones | Tres ideas distintas reciben la misma forma y el mismo peso | Dar a cada cosa la forma que pide su contenido; si son parejas, una lista |
| E4  | Todo centrado | Centrar es no decidir dónde empieza la lectura | Elegir un eje de lectura y romperlo solo donde algo deba destacarse |
| E5  | Todas las secciones con el mismo armado: título, bajada y grilla | La página se lee como un formulario repetido | Variar el armado según lo que la sección tiene para decir |
| E6  | El mismo espacio entre todo | Sin diferencias de aire no se ve qué va con qué | Poco espacio dentro de un grupo, mucho entre grupos; más arriba de un título que abajo |
| E7  | Todo pesa lo mismo | No hay nada que mirar primero | Decidir qué es lo más importante de cada pantalla y hacerlo evidente |
| E8  | Tarjetas dentro de tarjetas | Contenedores que encierran contenedores sin separar nada | Agrupar con espacio o con una línea; una sola superficie por grupo |
| E9  | Más de dos filas seguidas de imagen y texto alternados | El zigzag es el relleno habitual de una página larga | Cortar con una sección de otro armado o juntar el contenido |
| E10 | Grilla de mosaico con celdas de tamaños variados | Se usa por moda y obliga a inventar contenido para llenar celdas | Tantas piezas como contenido real haya, con el tamaño que cada una merece |
| E11 | Cifra enorme con una etiqueta chica debajo | Es la fórmula de métricas de toda página de producto | Mostrar el dato dentro de una oración que diga qué significa, si el dato es real |
| E12 | Secciones numeradas 01, 02, 03 sin que haya una secuencia | El número decora: sacarlo no cambia nada | Numerar solo pasos que el visitante sigue en orden |
| E13 | Etiqueta chica en mayúsculas arriba de cada título | Repite lo que el título ya dice y marca un ritmo de plantilla | Dejar que el título hable solo; etiquetar solo cuando clasifica de verdad |
| E14 | Hero a pantalla completa con flecha o aviso de bajar | Esconde el contenido y explica algo que todos saben hacer | Dejar ver el comienzo de lo que sigue |
| E15 | Menú flotante en forma de píldora | Es la barra de moda, igual en todos lados | Un menú con la forma del resto del sitio y los links que el sitio tiene |
| E16 | Ventana modal para algo que no interrumpe | Tapa la página para mostrar lo que podía estar en ella | Mostrarlo en su lugar; modal solo para confirmar algo que no tiene vuelta |
| E17 | Tres planes con el del medio destacado | Es la tabla de precios de un servicio por suscripción, aplicada a cualquier cosa | Mostrar los precios con la forma en que el negocio realmente cobra |
| E18 | Pie con columnas de links a páginas que no existen o repiten el menú | Infla el sitio para que parezca más grande | Un pie con los datos de contacto reales y lo que el visitante busca al final |
| E19 | Preguntas frecuentes que ningún cliente hizo | Son texto de relleno con forma de ayuda | Usar las preguntas que el negocio recibe; si no hay, no hay sección |
| E20 | Estilo prestado: diario, terminal, neobrutalismo o revista sin relación con el cliente | Es un disfraz elegido por gusto, no por el negocio | Sacar el estilo del mundo del cliente: su local, su oficio, sus objetos |
| E21 | Todo el contenido en una columna angosta al centro | Desperdicia la pantalla y trata igual a un párrafo y a una galería | Ancho de lectura para el texto; el resto usa el ancho que necesita |
| E22 | Sección que está porque «siempre va»: valores, misión, por qué elegirnos | No dice nada que distinga a este negocio | Quitarla, o reemplazarla por un hecho comprobable del negocio |

## 4. Tipografía

| ID  | Patrón | Por qué delata | En cambio |
| --- | ------ | -------------- | --------- |
| T1  | La fuente que viene puesta: Inter, Roboto, Poppins, Geist, Space Grotesk, Plus Jakarta Sans o la del sistema | Es la que queda cuando nadie elige | Elegir un par con carácter, sacado del mundo del cliente |
| T2  | Serif de alto contraste para parecer editorial o de categoría | Es el atajo habitual para simular oficio | Serif solo si el cliente ya la usa o el concepto la pide |
| T3  | Una palabra del título en cursiva, en otro color o en otra familia | Es el gesto de énfasis de toda plantilla | Énfasis por peso o tamaño dentro de la misma familia, y solo si la palabra lo merece |
| T4  | Monoespaciada para dar aire técnico | Se usa como disfraz y no para código, datos o medidas | Monoespaciada donde hay columnas de datos o código |
| T5  | Tamaños elegidos uno por uno, sin escala | Los saltos entre tamaños no siguen ninguna relación | Definir una escala con pocos pasos y usar solo esos |
| T6  | Título de cuatro renglones o más | El tamaño se eligió antes que el texto | Ajustar tamaño y texto juntos hasta que entre en dos o tres renglones |
| T7  | Letras apretadas hasta tocarse, o muy abiertas en un párrafo | El espaciado se usa como efecto y cuesta leer | Ajustar el espaciado solo en títulos grandes y en poca cantidad |
| T8  | Mayúscula Inicial En Cada Palabra | Es una convención del inglés que en castellano no existe | Mayúscula solo al comienzo y en nombres propios |
| T9  | Texto de lectura chico o renglones de más de 75 caracteres | Se priorizó que entre todo sobre que se pueda leer | Cuerpo de 16 px o más y renglones de 45 a 75 caracteres |
| T10 | Una familia y un peso para todo | Título y párrafo se distinguen apenas | Dos o tres niveles claros de tamaño y de peso |
| T11 | Párrafos en mayúsculas o justificados | Las mayúsculas cansan y el justificado deja ríos blancos | Mayúsculas solo en rótulos cortos; texto alineado a la izquierda |
| T12 | Datos separados por puntos medios en todos lados | Es el separador decorativo de la época | Como mucho uno por renglón; para más datos, renglones o columnas |
| T13 | Una fuente pesada del sistema como voz del sitio | Es lo primero que había instalado | Conseguir y alojar una fuente con el carácter que se busca |
| T14 | Título o párrafo que termina con una palabra suelta | Nadie miró cómo corta el texto real | Revisar los cortes con el texto definitivo en celular y en escritorio |

## 5. Color

| ID  | Patrón | Por qué delata | En cambio |
| --- | ------ | -------------- | --------- |
| C1  | Degradado de violeta a azul | Es la firma visual de lo generado | Colores planos que salgan del cliente |
| C2  | La paleta de fábrica del framework usada como marca | El índigo o el azul que viene puesto no es de nadie | Derivar la paleta del logo, del local o de los productos |
| C3  | Fondo negro con un solo acento neón o ácido | Es el uniforme de lo «tecnológico» | Elegir claro u oscuro según dónde y cómo se usa el sitio |
| C4  | Fondo crema con serif y acento terracota | Es el uniforme de lo «artesanal» | Buscar los colores reales del oficio, que rara vez son crema |
| C5  | Grises neutros sin temperatura | Vienen de una escala genérica y no acompañan al color de marca | Teñir los grises hacia el color principal |
| C6  | Texto con degradado | Es adorno sobre lo que debería leerse | Énfasis por peso o tamaño |
| C7  | Botón o franja de cierre con degradado | Es el cierre de página de toda plantilla | Color plano de la paleta |
| C8  | Halos, manchas difusas y bordes que brillan | Rellenan con luz lo que no tiene contenido | Quitarlos; si falta algo ahí, falta contenido |
| C9  | Texto gris claro que no llega al contraste AA | No se puede leer | Subir el contraste hasta 4,5:1 en texto común |
| C10 | Un color distinto para cada etiqueta o estado, sin sistema | Los colores no significan nada | Pocos colores con un significado fijo cada uno |
| C11 | El acento cambia de una sección a otra | No hay una paleta, hay colores sueltos | Un acento para todo el sitio |
| C12 | Secciones que alternan fondo claro y oscuro sin motivo | Parece otro sitio a mitad de página | Un solo tema; variar el tono dentro de la misma familia |

## 6. Componentes

| ID  | Patrón | Por qué delata | En cambio |
| --- | ------ | -------------- | --------- |
| K1  | Tarjeta redondeada con sombra suave como contenedor de todo | Es el contenedor que no exige pensar | Tarjeta solo cuando el contenido es una unidad que se elige o se compara |
| K2  | Borde grueso de color a un costado o arriba | Convierte un bloque común en una alerta | Si destaca, que destaque por contenido o por posición |
| K3  | Borde fino junto con una sombra enorme | Dos recursos para marcar el mismo límite | Elegir uno |
| K4  | Vidrio esmerilado como adorno | El desenfoque no cumple ninguna función | Fondo sólido; desenfoque solo si hay algo detrás que deba intuirse |
| K5  | Esquinas redondeadas al máximo en todo | Botones, campos y tarjetas se vuelven píldoras por omisión | Elegir un radio y sostenerlo |
| K6  | Radios mezclados sin regla | Cada componente salió de un lugar distinto | Una regla de radios escrita y cumplida en todo el sitio |
| K7  | Todos los botones con el mismo peso, o dos botones para lo mismo | No se sabe cuál es la acción principal | Una acción principal por pantalla y un solo rótulo por intención |
| K8  | Componentes de una librería tal como vienen | El sitio tiene la cara de la librería | Ajustar radios, colores, sombras y tipografía a las decisiones del proyecto |
| K9  | Gráficos, barras y anillos sin datos reales | Simulan información para llenar un hueco | Quitarlos; un gráfico va solo con datos del negocio |
| K10 | Ventana, teléfono o terminal dibujados con rectángulos grises | Simulan un producto que no se muestra | Captura real, o nada |
| K11 | Ruedita de carga en lugar de la forma del contenido | No dice qué viene ni cuánto falta | Reservar el lugar con la forma de lo que va a aparecer |
| K12 | Punto que late junto a «en vivo» o «disponible» | Anuncia un estado que nadie actualiza | Mostrar el estado solo si es real y cambia |
| K13 | Carrusel que avanza solo | El visitante pierde lo que estaba leyendo | Mostrar pocas piezas fijas, o un carrusel que maneja el visitante |
| K14 | Formulario que pide más de lo necesario, o con el ejemplo como única etiqueta | Cada campo de más es un visitante menos | Pedir lo mínimo, con la etiqueta siempre visible arriba del campo |
| K15 | Estado vacío o de error genérico | «No hay elementos» no dice qué hacer | Decir qué pasó y cuál es el paso siguiente, con palabras del negocio |
| K16 | Sello de la herramienta o de la plantilla con la que se hizo | Avisa que la web no es del negocio | Quitarlo |
| K17 | Insignias y etiquetas superpuestas a las fotos | Tapan la foto con metadatos decorativos | Epígrafe debajo de la foto, si aporta |

## 7. Íconos

| ID  | Patrón | Por qué delata | En cambio |
| --- | ------ | -------------- | --------- |
| I1  | Ícono dentro de un cuadradito redondeado arriba de cada título | Es la cabecera de tarjeta de toda plantilla | Título solo, o una imagen real |
| I2  | Emojis como íconos o dentro de los títulos | Cambian según el dispositivo y no pertenecen a ningún sistema | Íconos de una familia, dibujados con el mismo trazo |
| I3  | Íconos de una librería tal como vienen, o de varias mezcladas | El trazo de fábrica se reconoce a la distancia | Una sola familia, con el trazo y el tamaño ajustados al sitio |
| I4  | Flecha al final de cada link y de cada botón | Decora algo que ya se entiende como link | Flecha solo cuando indica una dirección real |
| I5  | Chispa, rayo o cohete para decir «moderno» o «rápido» | Son los símbolos comodín de la época | Mostrar el hecho en vez del símbolo |
| I6  | Tildes verdes como viñeta de toda lista | Convierten cualquier lista en una lista de ventajas | Viñeta común; tilde solo si algo está incluido frente a algo que no |
| I7  | Íconos que no agregan información | Ilustran una palabra que ya se entiende | Quitarlos |

## 8. Imágenes

| ID  | Patrón | Por qué delata | En cambio |
| --- | ------ | -------------- | --------- |
| F1  | Foto de banco de imágenes: gente sonriendo con una computadora, apretón de manos | No es el negocio y el visitante lo sabe | Fotos reales del local, de las personas y del trabajo, aunque sean simples |
| F2  | Ilustración genérica: isométrica, personajes planos o formas abstractas | Sirve para cualquier rubro | Ilustración propia del concepto, o fotos reales |
| F3  | Imagen generada que simula ser el local, el equipo o los productos | Le muestra al visitante algo que no existe | Pedir la foto real y dejar el pendiente con su marcador |
| F4  | Fondo decorativo: aurora, cuadrícula, puntos, rayas o grano | Rellena una superficie que no tiene nada | Fondo liso; una textura solo si es un material del concepto |
| F5  | Foto tapada por un velo oscuro para poner texto encima | La foto deja de verse y el texto sigue sin leerse bien | Texto al lado de la foto, o una foto elegida con lugar para el texto |
| F6  | Fotos recortadas en manchas o formas irregulares | El recorte llama más la atención que la foto | Recorte recto, con una proporción fija |
| F7  | Imagen rota o imagen de relleno que quedó publicada | El sitio está sin terminar | Reemplazarla por la real o quitar el bloque |
| F8  | Logos de clientes o de marcas que no son reales | Es prueba social inventada | Solo logos reales y con permiso; si no hay, no hay sección |
| F9  | Fotos reales puestas sin criterio: tamaños, encuadres y luz dispares | Se ve que se subieron como llegaron | Fijar proporción, encuadre y tratamiento común para todas |
| F10 | Avatares con iniciales y un «+2.000» | Simula una comunidad | Quitarlo |

## 9. Movimiento

| ID  | Patrón | Por qué delata | En cambio |
| --- | ------ | -------------- | --------- |
| M1  | Cada sección aparece al llegar a ella | El mismo efecto en todo no señala nada | Un solo momento animado por página, donde importa |
| M2  | Todas las tarjetas se elevan al pasar el cursor | Sugiere que todo se puede abrir | Respuesta al cursor solo en lo que se puede accionar |
| M3  | Rebotes y elásticos | Agregan demora y juguetean con acciones comunes | Curvas que arrancan rápido y frenan suave |
| M4  | Cifras que cuentan hacia arriba | Hacen esperar para leer un número | Mostrar el número |
| M5  | Cosas que flotan o giran, o una cinta de logos que no para | Movimiento sin causa que distrae de leer | Quieto, salvo que el movimiento explique algo |
| M6  | Cursor de máquina de escribir en el título | Simula que el texto se está escribiendo | Texto fijo |
| M7  | `transition: all`, o animar tamaños y posiciones | Anima lo que no se quiso animar y se traba | Animar propiedades nombradas; preferir `transform` y `opacity` |
| M8  | Contenido oculto hasta que corre su animación | Si la animación falla, el contenido no aparece | El contenido es visible de entrada; la animación solo lo acompaña |
| M9  | Cursor personalizado | Reemplaza una herramienta del visitante por un adorno | Cursor del sistema |

## 10. Textos

| ID  | Patrón | Por qué delata | En cambio |
| --- | ------ | -------------- | --------- |
| X1  | Botón que no dice qué pasa: «Empezar», «Saber más», «Enviar» | Sirve para cualquier botón de cualquier sitio | Decir la acción y el canal: «Escribinos por WhatsApp» |
| X2  | Titular comodín: «Todo lo que necesitás en un solo lugar», «Llevá tu negocio al siguiente nivel» | Se puede pegar en la web de la competencia | Decir qué hace este negocio, para quién y dónde |
| X3  | Bajada que repite el título con otras palabras | Ocupa lugar sin sumar un dato | Que la bajada agregue el dato que al título le falta |
| X4  | Beneficios intercambiables: calidad, compromiso, experiencia | Todos los negocios dicen lo mismo | Un hecho comprobable en lugar de cada adjetivo |
| X5  | Tríada de adjetivos: «Rápido. Simple. Seguro.» | Es un ritmo de eslogan, sin contenido | Una oración con un hecho |
| X6  | Contraste forzado: «No es X. Es Y.» | Convierte cada idea en un eslogan | Decir directamente qué es |
| X7  | Raya larga en cada oración | Es el tic de puntuación más reconocible del texto generado | Punto, coma o dos puntos |
| X8  | Cifras que nadie midió: «+500 clientes», «99 % de satisfacción» | Es un dato inventado | Solo cifras que el cliente puede respaldar |
| X9  | Testimonios o reseñas inventados | Es prueba social falsa | Reseñas reales con permiso, o ninguna |
| X10 | Texto de relleno o datos ficticios publicados | El sitio está sin terminar | Marcador `[FALTA: …]` hasta tener el texto real |
| X11 | Urgencia falsa: cuenta regresiva que se reinicia, «últimos lugares» | Presiona con algo que no es cierto | Decir un plazo solo si existe |
| X12 | Hablar como sistema o en castellano neutro: «Su solicitud fue procesada», «Contáctanos» | Nadie del negocio habla así | La voz del negocio, con voseo |
| X13 | Emojis y signos de entusiasmo en los títulos | Reemplazan contenido por tono | Título en palabras |
| X14 | Títulos de sección por omisión: «Nuestros servicios», «Sobre nosotros», «¿Por qué elegirnos?» | Son los rótulos de la plantilla | Titular con lo que la sección dice |
| X15 | Instrucciones obvias: «Hacé clic en el botón para continuar» | Explica la interfaz en vez de mejorarla | Un botón cuyo rótulo alcanza |
| X16 | Error con disculpa genérica: «¡Ups! Algo salió mal» | No dice qué pasó ni qué hacer | Nombrar el problema y el paso para resolverlo |
| X17 | Vocabulario de humo: innovador, soluciones integrales, potenciar, de vanguardia, líderes | Palabras que suenan a algo y no dicen nada | El sustantivo concreto: qué se vende, qué se arregla, qué se entrega |
| X18 | Rótulos poéticos o de falso artesano: «Desde el taller», «Notas de campo» | Es pose | El nombre llano de la sección |
| X19 | Micro texto debajo de cada botón o título | Aclara lo que nadie preguntó | Quitarlo; si hace falta, el texto principal está mal |
| X20 | Actividad fingida: «Actualizado hace 2 minutos», «3 personas están viendo esto» | Es un dato falso mostrado como real | Quitarlo |
