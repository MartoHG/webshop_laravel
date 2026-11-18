/**
 * Puente HTMX del panel. Único lugar donde viven los oyentes globales:
 * cabeceras de cada pedido, diálogo de carga rápida, errores de validación y avisos.
 *
 * El layout del panel tiene que tener:
 *   <meta name="csrf-token" content="...">
 *   <dialog id="dialogo"></dialog>
 *   <div id="avisos" role="status" aria-live="polite"></div>
 *
 * El servidor avisa lo que pasó con la cabecera HX-Trigger:
 *   {"aviso": {"tipo": "exito", "texto": "El producto se guardó."}}
 *   {"cerrarDialogo": true}
 *   {"listadoCambio": true}   (lo escucha el contenedor del listado, sin JS)
 */

const MENSAJES = {
  419: 'La sesión venció. Recargá la página y volvé a intentar.',
  403: 'No tenés permiso para hacer eso.',
  429: 'Hiciste demasiados intentos. Esperá un momento y probá de nuevo.',
  red: 'No hay conexión. Revisá internet y probá de nuevo.',
  otro: 'No se pudo completar la acción. Probá de nuevo en un momento.',
};

/**
 * Muestra un aviso en la región de avisos del layout.
 * @param {'exito' | 'error' | 'info'} tipo Tipo de aviso; define la clase CSS y si interrumpe al lector de pantalla.
 * @param {string} texto Mensaje a mostrar.
 * @returns {void}
 */
function mostrarAviso(tipo, texto) {
  const region = document.getElementById('avisos');
  if (!region || !texto) return;

  const aviso = document.createElement('div');
  aviso.className = `aviso aviso--${tipo}`;
  if (tipo === 'error') aviso.setAttribute('role', 'alert');

  const mensaje = document.createElement('p');
  mensaje.textContent = texto;

  const cerrar = document.createElement('button');
  cerrar.type = 'button';
  cerrar.className = 'aviso__cerrar';
  cerrar.textContent = 'Cerrar';
  cerrar.addEventListener('click', () => aviso.remove());

  aviso.append(mensaje, cerrar);
  region.append(aviso);

  // Los errores quedan hasta que la persona los cierra; el resto se va solo.
  if (tipo !== 'error') setTimeout(() => aviso.remove(), 6000);
}

/**
 * Quita de un formulario los errores de validación que dejó un envío anterior.
 * @param {HTMLElement} formulario Formulario a limpiar.
 * @returns {void}
 */
function limpiarErrores(formulario) {
  formulario.querySelectorAll('.error-campo').forEach((error) => error.remove());
  formulario.querySelectorAll('[aria-invalid="true"]').forEach((campo) => {
    campo.removeAttribute('aria-invalid');
    campo.removeAttribute('aria-describedby');
  });
}

/**
 * Pinta los errores de validación junto a cada campo. La clave de cada error es el
 * nombre del campo en notación de puntos ("items.0.precio" es name="items[0][precio]").
 * Un error sin campo en el formulario se muestra como aviso.
 * @param {HTMLElement} formulario Formulario que se envió.
 * @param {Record<string, string[]>} errores Errores por campo, como los devuelve Laravel.
 * @returns {void}
 */
function mostrarErrores(formulario, errores) {
  limpiarErrores(formulario);
  let primero = null;

  Object.entries(errores).forEach(([clave, mensajes], indice) => {
    const nombre = clave.replace(/\.([^.]+)/g, '[$1]');
    const campo =
      formulario.querySelector(`[name="${CSS.escape(nombre)}"]`) ??
      formulario.querySelector(`[name="${CSS.escape(nombre)}[]"]`);

    if (!campo) {
      mostrarAviso('error', mensajes[0]);
      return;
    }

    const error = document.createElement('p');
    error.className = 'error-campo';
    error.id = `error-campo-${indice}`;
    error.textContent = mensajes[0];

    campo.setAttribute('aria-invalid', 'true');
    campo.setAttribute('aria-describedby', error.id);
    (campo.closest('[data-campo]') ?? campo).after(error);
    primero ??= campo;
  });

  primero?.focus();
}

// Cada pedido de HTMX pide JSON ante un error y lleva el token CSRF.
document.body.addEventListener('htmx:configRequest', (evento) => {
  evento.detail.headers['Accept'] = 'application/json, text/html, */*';
  const token = document.querySelector('meta[name="csrf-token"]')?.content;
  if (token) evento.detail.headers['X-CSRF-TOKEN'] = token;
});

// Un envío nuevo arranca sin los errores del anterior.
document.body.addEventListener('htmx:beforeRequest', (evento) => {
  const formulario = evento.detail.elt.closest?.('form');
  if (formulario) limpiarErrores(formulario);
});

// Respuestas de error: 422 pinta los campos; el resto muestra un aviso.
document.body.addEventListener('htmx:responseError', (evento) => {
  const { xhr, elt } = evento.detail;

  if (xhr.status === 422) {
    let errores = {};
    try {
      errores = JSON.parse(xhr.responseText).errors ?? {};
    } catch {
      errores = {};
    }
    const formulario = elt.closest('form');
    if (formulario && Object.keys(errores).length > 0) {
      mostrarErrores(formulario, errores);
    } else {
      mostrarAviso('error', Object.values(errores)[0]?.[0] ?? MENSAJES.otro);
    }
    return;
  }

  mostrarAviso('error', MENSAJES[xhr.status] ?? MENSAJES.otro);
});

document.body.addEventListener('htmx:sendError', () => mostrarAviso('error', MENSAJES.red));

// Avisos que manda el servidor en HX-Trigger.
document.body.addEventListener('aviso', (evento) => {
  mostrarAviso(evento.detail.tipo ?? 'info', evento.detail.texto ?? evento.detail.value);
});

// Diálogo de carga rápida: se abre cuando HTMX le carga contenido y se vacía al cerrarse.
const dialogo = document.getElementById('dialogo');

if (dialogo) {
  /**
   * Cierra el diálogo y lo vacía. Se vacía acá y no solo en el evento "close" del
   * navegador, que llega después y no siempre: así el contenido viejo nunca queda en la página.
   * El vaciado espera un turno: HTMX dispara los eventos de HX-Trigger sobre el formulario,
   * y si se lo quita en el acto, los eventos que faltan (el aviso) no llegan al body.
   * @returns {void}
   */
  const cerrarDialogo = () => {
    if (dialogo.open) dialogo.close();
    setTimeout(() => {
      if (!dialogo.open) dialogo.innerHTML = '';
    }, 0);
  };

  document.body.addEventListener('htmx:afterSwap', (evento) => {
    if (evento.detail.target === dialogo && dialogo.innerHTML.trim() !== '' && !dialogo.open) {
      dialogo.showModal();
    }
  });

  document.body.addEventListener('cerrarDialogo', cerrarDialogo);

  dialogo.addEventListener('click', (evento) => {
    if (evento.target.closest('[data-cerrar-dialogo]')) cerrarDialogo();
  });

  // Cierre con Escape: lo hace el navegador y avisa con "close".
  dialogo.addEventListener('close', cerrarDialogo);
}
