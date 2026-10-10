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

Después de migrar, ejecuta `php artisan db:seed`. Los seeders son idempotentes y crean la categoría especial Ofertas, las reglas de pago iniciales (todas inactivas y con 0%), los beneficios de la home y los datos de contacto iniciales.

Para iniciar la aplicación:

```bash
composer run dev
```

La aplicación queda disponible en `http://localhost:8000`. El archivo `.env`, las bases MySQL, `vendor/`, `node_modules/` y los recursos compilados son locales y no se versionan.

## Estado

El backend de la V1 está casi completo (falta terminar la parte B del contenido de la home, ver [PROXIMOS-PASOS.md](PROXIMOS-PASOS.md)) y responde JSON. Todavía no hay pantallas Blade: el diseño aprobado está en [diseno/boceto/](diseno/boceto/LEEME.md) y las vistas son la próxima fase.

Arquitectura: `routes/*.php` (cargadas desde `routes/web.php`) → `app/Http/Controllers` → `app/Http/Requests` (validación y autorización) → `app/Servicios` (lógica de negocio) → `app/Models`.

| Módulo | Qué incluye |
|---|---|
| Usuarios | Registro, login, verificación de email, recuperación de clave, perfil, administradores. |
| Catálogo | Productos, variantes por color, categorías y Ofertas, imágenes WebP en cola, catálogo público. |
| Precios | Ajuste e-commerce, reglas de pago por medio y cuotas, actualización masiva con vista previa e historial. |
| Carrito y checkout | Carrito en sesión (también para invitados), confirmación idempotente, pedidos con snapshot. |
| Pedidos | Coordinación de envío, links de WhatsApp, edición y reemplazo, cancelación, historial del cliente. |
| Pagos | Mercado Pago con Checkout Bricks, link de pago sin login, webhook firmado, conciliación, revisión de pagos. |
| Emails | Pedido creado, pago aprobado y alertas automáticos; no contactado, recordatorio y despachado manuales. |
| Contenido | Portada y categorías destacadas con foto. En curso: beneficios, datos de contacto y horarios, datos de la home y retiro en el local. |
| Auditoría | Registro inmutable de las acciones sensibles del panel. |

Después de configurar MySQL y ejecutar las migraciones, crea el primer administrador con `php artisan usuario:crear-administrador`. El comando solicita los datos y la clave de forma interactiva; solo funciona mientras todavía no exista una cuenta administradora.

Para correr las pruebas:

```bash
php artisan test --compact
```

### Colas y tareas programadas

Las imágenes, los emails y las notificaciones de Mercado Pago se procesan en la cola. En desarrollo, dejá corriendo el worker en otra terminal:

```bash
php artisan queue:work
```

La conciliación de pagos pendientes (`pagos:conciliar`) corre cada 15 minutos con el scheduler. En desarrollo:

```bash
php artisan schedule:work
```

En producción hacen falta un worker permanente y el cron de `schedule:run` cada minuto.

### Mercado Pago

Completá en `.env` `MERCADOPAGO_ACCESS_TOKEN`, `MERCADOPAGO_PUBLIC_KEY` y `MERCADOPAGO_WEBHOOK_SECRET` (credenciales de prueba en desarrollo). El webhook es `POST /webhooks/mercadopago` y necesita una URL pública con HTTPS: en desarrollo, usá un túnel. Sin la clave secreta, el webhook rechaza todas las notificaciones.

### Emails

En desarrollo `MAIL_MAILER=log` deja los emails escritos en `storage/logs/laravel.log` en lugar de enviarlos.

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
