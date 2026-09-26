# El Refugio Verano Edition

Base Laravel del webshop de El Refugio. Las reglas funcionales y de arquitectura están en [el-refugio-decisiones.md](el-refugio-decisiones.md).

## Requisitos locales

- PHP 8.3 o superior y Composer
- Node.js y npm
- SQLite para el arranque local

## Puesta en marcha

```bash
composer run setup
composer run dev
```

`setup` instala dependencias PHP y JavaScript, crea `.env` y la base SQLite local, genera la clave de la aplicación, aplica las migraciones y compila los recursos. Para configurar otra base de datos, editar `.env` antes de ejecutar las migraciones.

La aplicación queda disponible en `http://localhost:8000`. El archivo `.env`, la base SQLite, `vendor/`, `node_modules/` y los recursos compilados son locales y no se versionan.

## Estructura inicial

- `app/Http/Controllers/Tienda` y `app/Http/Controllers/Administracion`: controladores HTTP por área.
- `app/Http/Requests`: validación y autorización de entradas.
- `app/Servicios`: lógica de negocio.
- `app/Models`: modelos Eloquent.
- `resources/views/tienda` y `resources/views/administracion`: vistas Blade por área.

Es solo el esqueleto del proyecto: todavía no se implementaron productos, pedidos ni pagos. El modelo `User` y las migraciones iniciales siguen siendo los del Laravel base; se adaptarán al dominio en la fase de autenticación.
