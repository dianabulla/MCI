# Eventos

Revisión del 14 de octubre de 2026. Módulo: `EventoController`, modelos `Evento` y `EventoModulo`, vistas en `views/eventos/`.

## Cómo funciona

El módulo tiene dos partes:

1. **Reuniones** (tabla `evento`): eventos con nombre, fecha, hora, lugar, imagen y video. Se publican en `eventos/proximos` y se comparten con `eventos/compartir&id=N`. Salen del listado público solos cuando pasa la fecha y hora del evento. Si no tiene hora, vale hasta las 23:59 de ese día.
2. **Contenidos de módulos** (tabla `evento_modulo_contenido`): publicaciones de Universidad de la vida, Capacitación destino y Otros, con título, párrafo, imagen y video. Se ven en `eventos/universidad-vida/publico`, `eventos/capacitacion-destino/publico` y `eventos/otros/publico`.

El panel interno exige permiso `eventos:ver`. Crear, editar y eliminar exigen `eventos:crear`, `eventos:editar` y `eventos:eliminar`. Gestionar contenido de módulos acepta `eventos:gestionar_contenido_publico` o `eventos:editar`. Las páginas públicas no piden sesión.

## Caducidad (pendiente del documento del cliente)

**Problema:** los contenidos de módulos tenían el campo "Publicar hasta", pero la página pública lo ignoraba a propósito (comentario "mostrar contenido publicado aunque su rango de fechas haya pasado"). Por eso un anuncio viejo, como el encuentro de hombres de abril con el valor de $180.000, seguía publicado.

**Arreglo:**
- La página pública y el resumen del panel usan `EventoModulo::getByModuloPublico`, que filtra activo, "Publicar desde" y "Publicar hasta". Un contenido con "hasta" de hoy se sigue viendo hoy y desaparece mañana.
- El campo se llama ahora "Caduca el / Publicar hasta (opcional)" y explica que el contenido se baja solo.
- En la lista del panel, cada contenido muestra "Caducado (no se ve en público)" o "Programado" según corresponda, para que se pueda renovar o borrar.
- Vacío significa que no caduca nunca.

**Efecto al subir a producción:** los contenidos que ya tengan "Publicar hasta" vencido dejan de verse en público en ese momento. No se borran: se pueden reactivar cambiando la fecha.

Probado con cinco casos (sin fecha, hasta ayer, hasta hoy, desde mañana, inactivo).

## Otros arreglos

- Crear y editar un evento ahora validan en el servidor que haya nombre, fecha válida y hora válida. Antes solo lo validaba el navegador. Si falla al crear, se borran los archivos recién subidos.
- El enlace para compartir un evento (`eventos/compartir`) ahora también comprueba la hora; antes solo la fecha, así que un evento del día seguía compartible después de terminar.

## Revisado sin hallazgos

- Las vistas públicas escapan todo el texto (`htmlspecialchars`).
- Subida de archivos: solo imágenes (jpg, jpeg, png, webp, gif) y videos (mp4, webm, mov, m4v) con nombre aleatorio. No se aceptan SVG ni scripts.
- Eliminar y duplicar por enlace quedan cubiertos por la revisión de origen (`OriginGuard`) de la parte de Administración.
- Todos los roles ven todos los eventos (`DataIsolation::generarFiltroEventos` devuelve `1=1`). Es decisión de diseño; no hay aislamiento por red.

## Pendiente

- Los eventos no tienen fecha de fin: un evento de varios días solo tiene una fecha y hora, y deja de ser público cuando pasa esa hora. Si se necesita, agregar fecha de fin.
- Reuniones no tiene campo de caducidad propio: caduca por su fecha. Si la iglesia quiere publicarlas después de ocurridas, hay que definirlo.
- En el panel interno, las reuniones pasadas siguen en la lista sin marca de "finalizado".
- Pagos: este módulo no maneja pagos. Los pagos están en Comunidad (`comunidad/pagos`), Talleres y Programas, y se revisarán aparte.
