# ADR-015: La auditoría revisa lo legal solo en los sitios que cobran

**Referenciado en:** `review-respawn`

---

## Contexto

Las normas de defensa del consumidor obligan a quien vende a distancia: un
botón para arrepentirse de la compra, otro para dar de baja un servicio,
plazos de respuesta y datos de atención. ARCA pide además el logo de Data
Fiscal en la portada de quien vende. La falta de cualquiera se ve mirando la
web, y la sanción la recibe el cliente.

Una landing page o una web institucional no venden. Sumar en todo sitio
controles de otras normas, como el registro de bases de datos, agranda la
auditoría con cosas que no se comprueban mirando el sitio.

## Alternativas evaluadas

| Opción                                  | Por qué se descartó                                                        |
| --------------------------------------- | -------------------------------------------------------------------------- |
| Sin controles legales                   | Una falta visible en la web del cliente se entregaría sin que nadie la vea |
| Controles legales en todo sitio         | Una landing page no vende: suma controles que casi nunca aplican           |
| Solo en el paquete de tienda            | Un sistema que cobra una seña también vende a distancia                    |
| En todo sitio que cobra en línea        | — (es la decisión)                                                         |

## Decisión

**La auditoría revisa las obligaciones legales solo en los sitios que cobran
en línea, cualquiera sea el paquete.**

- Se revisan los botones de arrepentimiento y de baja, los plazos, los datos
  de atención y el logo de Data Fiscal, según la norma vigente.
- Se comprueban mirando el sitio y con una compra de prueba en el entorno de
  prueba.
- La auditoría no reemplaza a un abogado, y el informe para el cliente lo
  dice.
- La política de privacidad junto a un formulario sigue siendo una regla de
  todo sitio, en `respawn-web`.

## Consecuencias

### Ganamos

- Un cliente que vende no recibe una web con una falta que se ve a simple
  vista.
- Los sitios que no venden no cargan controles que no les corresponden.

### Perdemos / Compromisos

- Las normas cambian: la lista hay que revisarla cuando sale una nueva.
- Otras obligaciones, como la facturación o el registro de bases de datos,
  quedan fuera de la auditoría.

### Vigilancia requerida

- Un sitio que cobra auditado como si no cobrara.
- Una norma citada en la lista que fue derogada o reemplazada.
