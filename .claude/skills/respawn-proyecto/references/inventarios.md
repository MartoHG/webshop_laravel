# Formato de los inventarios

> Referencia de `respawn-proyecto`: cómo se escribe cada ítem de la memoria
> del proyecto. Las plantillas para crear cada archivo viven en `assets/`.

## Contenido

1. Reglas comunes
2. Deuda técnica
3. Riesgos aceptados
4. Problemas conocidos
5. Pendientes del cliente

## 1. Reglas comunes

- **Identificador:** prefijo y número de dos cifras (`DT-01`, `RA-01`). El
  número siguiente es el mayor que existió más uno, aunque haya huecos.
- **Encabezado:** `## DT-NN. Título`, con punto después del identificador. El
  título dice el problema, no la solución.
- **Ubicación:** ruta desde la raíz del proyecto, con línea cuando es código
  (`` `src/pages/index.astro:42` ``). Si no es un archivo, se nombra el lugar:
  panel del hosting, registro del dominio, cuenta de Mercado Pago.
- **Regla:** la skill dueña y la regla desviada, en palabras
  (`respawn-web`: contraste AA de texto).
- **Fechas:** se admiten donde el formato las pide. Van en formato
  `AAAA-MM-DD`.
- **Sin casillas de verificación:** un ítem existe o se borra.

### Severidad

Se evalúa de alta a baja y toma el primer nivel que corresponde.

| Severidad | Criterio                                                                                         |
| --------- | ------------------------------------------------------------------------------------------------ |
| Alta      | El visitante o el cliente no puede hacer algo, se exponen o se pierden datos, o la regla desviada es bloqueante en la auditoría |
| Media     | Funciona, pero queda frágil, difícil de mantener o inconsistente a la vista                      |
| Baja      | No afecta el uso ni el mantenimiento: nombres, comentarios, orden                                |

## 2. Deuda técnica

Archivo: `.ai/deuda-tecnica.md`. Un desvío de una regla de la casa que se va
a corregir.

Campos de cada ítem, en este orden:

- **Severidad:** alta, media o baja.
- **Regla:** skill dueña y regla desviada.
- **Ubicación:** dónde está el desvío.
- **Problema:** qué pasa y en qué situación se nota.
- **Resolución esperada:** qué hay que hacer para borrar el ítem.

Ejemplo:

```markdown
## DT-02. Fotos de la galería sin tamaño declarado

- **Severidad:** media
- **Regla:** `respawn-web`: imágenes con ancho y alto declarados.
- **Ubicación:** `src/components/Galeria.astro:18`
- **Problema:** las seis fotos cargan sin `width` ni `height` y la página
  salta al terminar de cargarlas.
- **Resolución esperada:** declarar el tamaño de cada foto y volver a medir
  el CLS de la página.
```

- No es deuda lo que no desvía ninguna regla: una mejora deseada va al
  próximo paso de `estado.md` o se descarta.
- No es deuda un dato que falta del cliente: es un pendiente.

## 3. Riesgos aceptados

Archivo: `.ai/riesgos-aceptados.md`. Un desvío que el usuario decide
conservar. Exige su decisión explícita.

Campos de cada ítem, en este orden:

- **Severidad:** alta, media o baja.
- **Regla:** skill dueña y regla desviada.
- **Ubicación:** dónde está el desvío.
- **Motivo:** por qué se conserva.
- **Mitigación:** qué reduce el impacto mientras exista.
- **Decisión:** quién decidió, por su rol (el desarrollador, el cliente).
  Sin nombres propios.
- **Condición de revisión:** el hecho comprobable que obliga a decidir de
  nuevo.

Ejemplo:

```markdown
## RA-01. Mapa incrustado que baja el rendimiento del contacto

- **Severidad:** media
- **Regla:** `respawn-web`: JS de terceros solo donde hace falta.
- **Ubicación:** `src/pages/contacto.astro:57`
- **Motivo:** el cliente pidió ver el local en el mapa sin salir de la web.
- **Mitigación:** el mapa carga recién cuando el visitante llega a esa
  sección.
- **Decisión:** el cliente, al revisar la pantalla de contacto.
- **Condición de revisión:** el rendimiento de la página baja del umbral de
  la auditoría, o el local cambia de dirección.
```

- Si se cumple la condición de revisión, el usuario decide de nuevo:
  renovar, pasar a deuda o corregir.
- Si el desvío desaparece del código, el ítem se borra.
- Un patrón del catálogo de slop justificado por el concepto no es un riesgo
  aceptado: es una excepción y va en `decisiones.md`.

## 4. Problemas conocidos

Archivo: `.ai/problemas-conocidos.md`. Lo que costó entender del entorno, de
las herramientas o del deploy, para no pagarlo dos veces.

Una sección `##` por área (Entorno local, Deploy, Herramientas, Servicios
externos) y, dentro, una tabla:

```markdown
## Deploy

| Síntoma                                  | Causa                                             | Solución                                        |
| ---------------------------------------- | ------------------------------------------------- | ----------------------------------------------- |
| La vista previa publicada aparece en Google | La variable que activa `noindex` no está definida en ese entorno | Definirla en el panel del hosting y volver a publicar |
```

- El síntoma se escribe como se ve: el mensaje de error o lo que pasa en
  pantalla.
- Una fila se borra cuando la causa deja de existir.
- No lleva identificadores: se busca por síntoma.
- Un defecto del código del proyecto no va acá: es deuda.

## 5. Pendientes del cliente

Archivo: `.ai/pendientes-cliente.md`. Todo dato real que falta y solo el
cliente puede dar.

```markdown
| Falta                       | Dónde va                          | Pedido al cliente |
| --------------------------- | --------------------------------- | ----------------- |
| Horarios de atención        | `src/components/Pie.astro:12`     | {AAAA-MM-DD}      |
| Fotos del local (mínimo 4)  | Sección «El local» de la portada  | No                |
```

- Cada fila tiene su marcador `[FALTA: …]` en el lugar donde iría el dato, y
  cada marcador tiene su fila.
- «Pedido al cliente» lleva la fecha del pedido, o «No» si todavía no se le
  pidió.
- La fila se borra cuando el dato real reemplaza al marcador.
- Un acceso que falta (hosting, dominio, redes) también es un pendiente.
