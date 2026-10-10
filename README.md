# El Refugio

Base Laravel del webshop de El Refugio. Las reglas funcionales y de arquitectura están en [el-refugio-decisiones.md](el-refugio-decisiones.md).

## Requisitos locales

- PHP 8.4 o superior (con la extensión bcmath) y Composer
- Node.js y npm
- MySQL y un usuario con permisos sobre las bases de desarrollo y pruebas

## Puesta en marcha

Antes de ejecutar las migraciones, crea dos bases vacías en MySQL, por ejemplo `el_refugio_local` y `el_refugio_test`. Configura un usuario local con permisos sobre ambas. Copia `.env.example` como `.env` y completa allí el host, puerto, nombre de la base de desarrollo y usuario. No guardes credenciales reales en el repositorio.

Luego ejecuta:

```bash
composer run setup
```

`setup` instala dependencias PHP y JavaScript, genera la clave de la aplicación, aplica las migraciones sobre la base configurada y compila los recursos. PHPUnit está configurado para usar `el_refugio_test`; ajusta también sus variables `DB_HOST`, `DB_PORT` y `DB_USERNAME`/`DB_PASSWORD` en el entorno al ejecutar pruebas si difieren de las de desarrollo. No apuntes la base de pruebas a datos reales.

Después de migrar, ejecuta `php artisan db:seed` para crear la categoría especial Ofertas mediante `CategoriaSeeder`.

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
- `routes/`: rutas de autenticación, cuenta, administración y catálogo JSON cargadas desde `routes/web.php`.

El backend incluye autenticación y administración de usuarios, productos y variantes, categorías (incluida Ofertas), imágenes de producto, ajuste e-commerce y catálogo JSON. Las imágenes se procesan en cola y se convierten a WebP. El catálogo público está disponible en `/catalogo`, `/catalogo/categorias` y `/catalogo/{slug}`; requiere que el administrador configure el ajuste e-commerce. Todavía no hay pantallas Blade para estos módulos.

Después de configurar MySQL y ejecutar las migraciones, crea el primer administrador con `php artisan usuario:crear-administrador`. El comando solicita los datos y la clave de forma interactiva; solo funciona mientras todavía no exista una cuenta administradora.

### Procesamiento de imágenes

En desarrollo, `.env.example` usa la cola `database` y el disco de imágenes `public`. Inicia el worker en otra terminal para procesar las imágenes:

```bash
php artisan queue:work
```

Con `IMAGENES_PRODUCTO_DISK=public`, crea el enlace público una vez:

```bash
php artisan storage:link
```

En producción configura un disco S3 compatible con `IMAGENES_PRODUCTO_DISK=s3` y completa `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET` y, si el proveedor lo requiere, `AWS_ENDPOINT`, `AWS_URL` y `AWS_USE_PATH_STYLE_ENDPOINT`. Ejecuta un worker permanente para procesar la cola también en producción.
