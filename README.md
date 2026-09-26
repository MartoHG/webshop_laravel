# El Refugio Verano Edition

Base Laravel del webshop de El Refugio. Las reglas funcionales y de arquitectura están en [el-refugio-decisiones.md](el-refugio-decisiones.md).

## Requisitos locales

- PHP 8.3 o superior y Composer
- Node.js y npm
- MySQL y un usuario con permisos sobre las bases de desarrollo y pruebas

## Puesta en marcha

Antes de ejecutar las migraciones, crea dos bases vacías en MySQL, por ejemplo `el_refugio_local` y `el_refugio_test`. Configura un usuario local con permisos sobre ambas. Copia `.env.example` como `.env` y completa allí el host, puerto, nombre de la base de desarrollo y usuario. No guardes credenciales reales en el repositorio.

Luego ejecuta:

```bash
composer run setup
```

`setup` instala dependencias PHP y JavaScript, genera la clave de la aplicación, aplica las migraciones sobre la base configurada y compila los recursos. PHPUnit está configurado para usar `el_refugio_test`; ajusta también sus variables `DB_HOST`, `DB_PORT` y `DB_USERNAME`/`DB_PASSWORD` en el entorno al ejecutar pruebas si difieren de las de desarrollo. No apuntes la base de pruebas a datos reales.

Para iniciar la aplicación:

```bash
composer run dev
```

La aplicación queda disponible en `http://localhost:8000`. El archivo `.env`, las bases MySQL, `vendor/`, `node_modules/` y los recursos compilados son locales y no se versionan.

## Estructura inicial

- `app/Http/Controllers/Tienda` y `app/Http/Controllers/Administracion`: controladores HTTP por área.
- `app/Http/Requests`: validación y autorización de entradas.
- `app/Servicios`: lógica de negocio.
- `app/Models`: modelos Eloquent.
- `resources/views/tienda` y `resources/views/administracion`: vistas Blade por área.

Es solo el esqueleto del proyecto: todavía no se implementaron productos, pedidos ni pagos. El modelo `User` y las migraciones iniciales siguen siendo los del Laravel base; se adaptarán al dominio en la fase de autenticación.
