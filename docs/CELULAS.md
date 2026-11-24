# Células

Registro actualizado el **24 de noviembre de 2026**. Estos cambios están en el código y en producción (FTP Hostinger).

## Nombre del módulo

El menú y la tarjeta del inicio se llaman **Células**. La etapa **Enviar** de la escalera no cambia: sigue siendo el cuarto peldaño.

La versión del menú lateral quedó en 14 para que la sesión reconstruya el nombre.

## Listado

La columna **Miembros** cuenta las personas activas asignadas a la célula, aunque no asistan. El líder no entra en ese número.

En cada fila las acciones son: **Celugrama**, reporte individual, **Reportar célula** (abre el Celugrama con el modal), Editar y Eliminar.

Una célula cerrada sigue en el listado con la marca **Cerrada**. La gente conserva esa célula en la ficha. La célula cerrada no entra en el reporte operativo.

## Celugrama (pantalla unificada)

El ícono del listado abre el **Celugrama** (`celulas/detalle`): una sola pantalla con ficha, resumen del trimestre, gráfica, miembros, asistencia e indicadores. Ocupa todo el ancho disponible del contenido.

### Qué muestra

- Ficha: apertura, dirección, día/hora, estado, ministerio, líder, pastor, líder de 12, anfitrión.
- KPIs del trimestre: promedio, semanas reportadas, invitados célula, invitados a iglesia, encuentro, pos-encuentro/UV, Cap. Destino, no realizadas.
- Gráfica de asistencia por semana.
- Tabla de miembros: indicador, proceso, asistencias del trimestre, Enc., Pos., Bautizo, Cap. Destino y acciones.
- Indicadores del mes (editables si hay permiso).

### Acciones por persona

| Acción | Qué hace | Permiso |
| --- | --- | --- |
| Seguimiento | Nota de seguimiento | Ver personas / Ganar / editar desde Discipular |
| Escalera rápida | Checklist de la escalera en modal | `puedeActualizarChecklistEscalera` |
| Editar ficha | Formulario de persona | Editar personas |
| Migrar | Mueve a otra célula del mismo ministerio | `asistencias:crear` o `asistencias:editar` |
| No se dispone | Marca no disponible, sale de la célula y pasa a inactivo | `asistencias:crear` o `asistencias:editar` |
| Eliminar | **Borra la persona de toda la base** (`personas/eliminar`), con aviso «¿Estás seguro de eliminar esta persona?» OK/Cancelar | `puedeEliminarPersonaDesdeDiscipular` (incluye `personas:eliminar`) |

### Reportar célula

Se hace en un modal dentro del Celugrama (POST a `asistencias/registrar`). Desde el listado, «Reportar célula» abre `celulas/detalle&id=…&reporte=1` y abre el modal.

### Guardar indicadores

Ruta `celulas/celugrama/guardar`. Requiere `asistencias:crear` o `celulas:editar`.

### Indicador de miembros (historial en esa célula)

| Indicador | Reuniones asistidas |
| --- | --- |
| Sin asistencia | Ninguna |
| Regular | 1 o 2 |
| Frecuente | 3 o más |

## Sobre

La semana es de lunes a domingo. La casilla abre en la **semana vencida** (la que ya pasó). Quien revisa puede elegir otra semana anterior; la fecha no pasa de hoy.

La marca se guarda en el lunes de esa semana. Si había una marca en otro día de la misma semana, se reemplaza por la del lunes.

La columna **Semanas sin entregar sobre** de cada célula cuenta las semanas que tienen reporte de asistencia, no tienen el sobre marcado y son posteriores a la última semana en que sí se marcó. Marcar el sobre no sustituye el reporte. Una célula que nunca reporta asistencia queda en 0.

La casilla la ven quienes tienen acceso total: administrador, Ganar, pastor y el rol Células. El líder de célula no la ve.

## Reporte operativo

**Asistentes** son las personas de la célula, sin el líder, que vinieron dentro del rango de fechas. Frecuente es 3 o más fechas de ese rango. Regular es 1 o 2. Quien no vino no entra.

En cada célula, las dos últimas columnas siguen siendo semanas. En el subtotal del ministerio y en el total general cuentan **cuántas células** tienen al menos una semana pendiente, y se leen como «N células».

## Permisos (alineación 24 nov 2026)

- Ver Celugrama: `celulas:ver`.
- Reportar / migrar / No se dispone: `asistencias:crear` (o editar).
- Guardar indicadores: `asistencias:crear` o `celulas:editar`.
- Escalera rápida: regla `puedeActualizarChecklistEscalera` (Ganar, editar desde Discipular, `asistencias:crear` o `celulas:ver`). La ruta `personas/actualizarChecklistEscalera` usa ese checker para no chocar con el RouteGuard.
- Eliminar persona: `puedeEliminarPersonaDesdeDiscipular`.

## Lo que se dejó igual

El seguimiento tras cuatro faltas no se programó. Quién puede abrir una célula sigue con el permiso `celulas:crear`. Si quien crea es líder de célula, queda como líder de la nueva.

## Lo que ya no se abre / se retiró

- `asistencias` y `asistencias/exportarExcel` redirigen al reporte de células o al listado de Células.
- `asistencias/registrar` (GET) redirige al Celugrama y abre el modal. El **POST** sigue activo para guardar el reporte.
- Eliminadas rutas y vistas: `asistencias/porCelula`, `asistencias/miembros-celula`, `asistencias/quitarDeCelula` (soft-remove). Ya no hay formulario suelto de asistencias.
- Se retiró del Celugrama la sección **Temas / texto bíblico** (el tema sigue pudiendo ir en el modal al reportar).
- Se retiraron de la página los scripts sueltos de diagnóstico de Teen y de marcar Ganar completo.

## Archivos principales (Celugrama)

- `views/celulas/detalle.php` — pantalla + modales (reporte, escalera, migrar, no se dispone).
- `views/celulas/partials/celugrama.php` — ficha, KPIs, gráfica, tabla, indicadores.
- `app/Controllers/CelulaController.php` — `detalle`, `guardarCelugrama`, armado del Celugrama.
- `app/Models/Celugrama.php` — temas/indicadores de trimestre.
- `app/Controllers/AsistenciaController.php` — registrar (POST), migrar, no se dispone; GET viejo redirige.
- `app/Config/routes.php` / `route_permissions.php` — rutas y permisos.
