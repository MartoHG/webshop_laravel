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

## Estado del backend

- `app/Http/Controllers`: endpoints de autenticación, cuenta y administración.
- `app/Http/Requests`: validación y autorización de entradas.
- `app/Servicios`: lógica de negocio.
- `app/Models/Usuario.php`: modelo de clientes y administradores en `usuarios`.
- `routes/autenticacion.php`, `routes/cuenta.php` y `routes/administracion.php`: rutas backend cargadas desde `routes/web.php`.

La fase backend incluye registro, inicio/cierre de sesión, verificación de correo, restablecimiento de clave, edición de perfil y administración básica de usuarios. La verificación y la recuperación tienen respuestas HTTP/JSON; todavía no hay pantallas Blade de autenticación, cuenta o administración.

Después de configurar MySQL y ejecutar las migraciones, crea el primer administrador con `php artisan usuario:crear-administrador`. El comando solicita los datos y la clave de forma interactiva; solo funciona mientras todavía no exista una cuenta administradora.

Todavía no se implementaron productos, pedidos ni pagos. Las migraciones iniciales ahora crean `usuarios` y las tablas auxiliares de sesiones y restablecimiento de clave.
