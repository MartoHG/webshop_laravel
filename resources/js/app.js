import htmx from 'htmx.org';

window.htmx = htmx;

document.addEventListener('htmx:configRequest', (evento) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;

    if (token) {
        evento.detail.headers['X-CSRF-TOKEN'] = token;
    }
});
