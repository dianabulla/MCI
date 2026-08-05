# Sesión por inactividad y caché de assets

## Sesión (30 minutos de inactividad)

- Tras **30 minutos** sin peticiones al servidor, la sesión se cierra automáticamente.
- El usuario vuelve al login con el mensaje de sesión expirada.
- Las peticiones AJAX reciben JSON con `session_expired: true` y código 401.

## Caché de CSS/JS (sin borrar caché del navegador)

Al desplegar una actualización:

1. Edita `public/assets/.build` y pon una versión nueva (fecha o número), por ejemplo: `20260530-2`
2. Sube los archivos PHP/CSS/JS modificados.

Las páginas cargan `styles.css`, `main.js`, etc. con `?v=<versión>`. Al cambiar `.build`, el navegador descarga los archivos nuevos.

Si no existe `.build`, la versión se calcula por la fecha de modificación más reciente de `public/assets/css` y `public/assets/js`.

También puedes definir la variable de entorno `APP_ASSET_VERSION` en el servidor.

## Producción (Hostinger)

Sube como mínimo:

- `app/Helpers/SessionManager.php`
- `app/Helpers/AssetVersion.php`
- `app/Config/config.php`
- `public/index.php`
- `app/Controllers/AuthController.php`
- `views/layout/header.php`
- `views/layout/footer.php`
- `views/auth/login.php`
- `public/assets/js/main.js`
- `public/assets/.build` (actualizado en cada despliegue)
- `public/assets/.htaccess` (opcional, Apache)
