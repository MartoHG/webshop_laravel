import htmx from 'htmx.org';

window.htmx = htmx;

document.addEventListener('htmx:configRequest', (evento) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;

    if (token) {
        evento.detail.headers['X-CSRF-TOKEN'] = token;
    }
});

document.addEventListener('click', (evento) => {
    const paso = evento.target.closest('[data-paso]');

    if (paso) {
        cambiarCantidad(paso);

        return;
    }

    const miniatura = evento.target.closest('[data-miniatura]');

    if (miniatura) {
        mostrarMiniatura(miniatura);
    }
});

function cambiarCantidad(boton) {
    const campo = boton.closest('[data-cantidad]')?.querySelector('input[type="number"]');

    if (!campo) {
        return;
    }

    const minimo = Number(campo.min) || 1;
    const maximo = Number(campo.max) || 99;
    const actual = Number.parseInt(campo.value, 10) || minimo;

    campo.value = String(Math.min(maximo, Math.max(minimo, actual + Number(boton.dataset.paso))));
}

function mostrarMiniatura(miniatura) {
    const galeria = miniatura.closest('[data-galeria]');
    const principal = galeria?.querySelector('[data-imagen-principal]');

    if (!principal) {
        return;
    }

    principal.src = miniatura.dataset.url;
    principal.alt = miniatura.dataset.alt;

    galeria.querySelectorAll('[data-miniatura]').forEach((boton) => {
        if (boton === miniatura) {
            boton.setAttribute('aria-current', 'true');
        } else {
            boton.removeAttribute('aria-current');
        }
    });
}
