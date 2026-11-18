// Pago con Mercado Pago. El servidor decide el resultado: después de cada envío pide el estado como fragmento
// HTML, así la página muestra siempre lo que quedó guardado. La tarjeta la tokeniza el Brick, nunca este archivo.

const MENSAJE_ERROR_PAGO = 'No pudimos procesar el pago. Revisá los datos e intentá de nuevo.';

let montado = null;
let enfocarResultado = false;

document.addEventListener('htmx:beforeSwap', (evento) => {
    if (evento.detail.target?.id !== 'pago-intento') {
        return;
    }

    desmontarBrick();

    if (evento.detail.xhr.status === 422) {
        evento.detail.shouldSwap = true;
        evento.detail.isError = false;
    }
});

document.addEventListener('htmx:afterSettle', () => {
    montarBrickSiHayContenedor();

    if (enfocarResultado) {
        const titulo = document.querySelector('#pago-resultado [data-foco]');

        if (titulo) {
            titulo.focus();
            enfocarResultado = false;
        }
    }
});

montarBrickSiHayContenedor();

function montarBrickSiHayContenedor() {
    const contenedor = document.getElementById('pago-brick');

    if (!contenedor || contenedor === montado?.contenedor) {
        return;
    }

    desmontarBrick();
    montado = { contenedor, controlador: null };
    montarBrick(montado);
}

async function montarBrick(referencia) {
    const datos = referencia.contenedor.dataset;

    try {
        const mercadoPago = new window.MercadoPago(datos.publicKey, { locale: 'es-AR' });
        const brick = mercadoPago.bricks();
        const controlador = datos.tipo === 'dinero_en_cuenta'
            ? await brick.create('wallet', 'pago-brick', configuracionCuenta(datos))
            : await brick.create('payment', 'pago-brick', configuracionTarjeta(datos));

        if (montado !== referencia) {
            await desmontar(controlador);

            return;
        }

        referencia.controlador = controlador;
    } catch {
        if (montado === referencia) {
            mostrarAviso('No pudimos cargar el formulario de Mercado Pago. Actualizá la página e intentá de nuevo.');
        }
    }
}

function configuracionCuenta(datos) {
    return { initialization: { preferenceId: datos.preferenceId } };
}

function configuracionTarjeta(datos) {
    const cuotas = Number(datos.cuotas);
    const medios = datos.tipo === 'credito'
        ? { creditCard: 'all', maxInstallments: cuotas, minInstallments: cuotas }
        : { debitCard: 'all' };

    return {
        initialization: { amount: Number(datos.importe) },
        customization: { paymentMethods: medios },
        callbacks: {
            onError: () => mostrarAviso('Hubo un problema con el formulario de Mercado Pago. Actualizá la página e intentá de nuevo.'),
            onSubmit: ({ selectedPaymentMethod, formData }) => enviarPago(datos, {
                metodo_seleccionado: selectedPaymentMethod,
                ...formData,
            }),
        },
    };
}

async function enviarPago(datos, cuerpo) {
    let respuesta;

    const idDispositivo = window.MP_DEVICE_SESSION_ID;
    const cuerpoConDispositivo = typeof idDispositivo === 'string' && idDispositivo !== ''
        ? { ...cuerpo, device_id: idDispositivo }
        : cuerpo;

    try {
        respuesta = await fetch(datos.urlTarjeta, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': tokenCsrf(),
            },
            body: JSON.stringify(cuerpoConDispositivo),
        });
    } catch {
        // Sin respuesta no se sabe si el cobro llegó a procesarse: el cliente tiene que mirar el estado antes de reintentar.
        const mensaje = 'No llegó la respuesta de la tienda. Antes de volver a pagar, actualizá la página para ver cómo está tu pedido.';

        mostrarAviso(mensaje);

        throw new Error(mensaje);
    }

    if (!respuesta.ok) {
        const mensaje = mensajeDeError(await respuesta.json().catch(() => null));

        mostrarAviso(mensaje);

        // Rechazar la promesa le indica al Brick que el envío no terminó y que el formulario sigue disponible.
        throw new Error(mensaje);
    }

    // Sin esperar: el Brick todavía está dentro de su propio envío.
    desmontarBrick();
    document.getElementById('pago-intento')?.replaceChildren();
    enfocarResultado = true;

    try {
        await window.htmx.ajax('GET', datos.urlEstado, { target: '#pago-resultado', swap: 'innerHTML' });
    } catch {
        enfocarResultado = false;
        mostrarAviso('No pudimos mostrar cómo sigue tu pago. Actualizá la página para ver el resultado.');
    }
}

async function desmontarBrick() {
    const anterior = montado;

    montado = null;

    if (anterior) {
        await desmontar(anterior.controlador);
    }
}

async function desmontar(controlador) {
    try {
        await controlador?.unmount();
    } catch {
        // El formulario ya no estaba en la página.
    }
}

function mensajeDeError(datos) {
    const primero = datos?.errors ? Object.values(datos.errors)[0]?.[0] : null;

    return primero ?? MENSAJE_ERROR_PAGO;
}

function mostrarAviso(mensaje) {
    const resultado = document.getElementById('pago-resultado');

    if (!resultado) {
        return;
    }

    const aviso = document.createElement('div');

    aviso.tabIndex = -1;
    aviso.className = 'rounded-[10px] border-2 border-naranja bg-naranja-claro p-5 text-lg font-semibold leading-snug text-grafito';
    aviso.textContent = mensaje;
    resultado.replaceChildren(aviso);
    aviso.focus();
}

function tokenCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}
