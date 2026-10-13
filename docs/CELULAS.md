# Células

Registro al 13 de octubre de 2026. Estos cambios están en el código y en la página.

## Nombre del módulo

El menú y la tarjeta del inicio se llaman **Células**. La etapa **Enviar** de la escalera no cambia: sigue siendo el cuarto peldaño.

La versión del menú lateral quedó en 14 para que la sesión reconstruya el nombre.

## Listado

La columna **Miembros** cuenta las personas activas asignadas a la célula, aunque no asistan. El líder no entra en ese número.

En cada fila las acciones son Ver, Reportar célula, Editar y Eliminar.

Una célula cerrada sigue en el listado con la marca **Cerrada**. La gente conserva esa célula en la ficha. La célula cerrada no entra en el reporte operativo.

## Ver

La ficha muestra los miembros, sin acciones por persona. El indicador usa las asistencias de toda la historia en esa célula:

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

## Reportar célula

La lista no tiene columna Estado. Las acciones son En seguimiento, eliminar de la célula, No se dispuso y migrar a otra célula del mismo ministerio.

| Grupo | Reuniones asistidas en esta célula |
| --- | --- |
| Casi no van | Menos de 3 |
| Frecuentes | 3 o más |

En seguimiento abre la nota de la persona. Si ya tiene la marca, el botón se ve activo. No hay paso automático a seguimiento por faltas.

## Reporte operativo

**Asistentes** son las personas de la célula, sin el líder, que vinieron dentro del rango de fechas. Frecuente es 3 o más fechas de ese rango. Regular es 1 o 2. Quien no vino no entra.

En cada célula, las dos últimas columnas siguen siendo semanas. En el subtotal del ministerio y en el total general cuentan **cuántas células** tienen al menos una semana pendiente, y se leen como «N células».

## Lo que se dejó igual

El seguimiento tras cuatro faltas no se programó. Quién puede abrir una célula sigue con el permiso `celulas:crear`. Si quien crea es líder de célula, queda como líder de la nueva.

## Lo que ya no se abre

La ruta `asistencias` ya no muestra el listado viejo: lleva al reporte de células o al listado de Células. Se retiraron de la página los scripts sueltos de diagnóstico de Teen y de marcar Ganar completo.
