# Células

Registro actualizado el **26 de noviembre de 2026**. Estos cambios están en el código y en producción (FTP Hostinger).

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
- KPIs del trimestre (solo movimientos del periodo seleccionado).
- Gráfica de asistencia por semana.
- Tabla de miembros: indicador, proceso, asistencias del trimestre, Enc., Pos., Bautizo, Cap. Destino y acciones (Sí/— = estado actual de la persona).
- Indicadores del mes: **solo lectura**, calculados solos.

### KPIs e indicadores (26 nov 2026)

Nadie edita invitados, no realizadas, encuentro ni pos-encuentro en el Celugrama. Todo sale de la plataforma:

| Dato | Cómo se calcula |
| --- | --- |
| Promedio / semana | Suma de asistentes de semanas **reportadas** ÷ cantidad de semanas reportadas (puede salir decimal, p. ej. 4.7) |
| Semanas reportadas | Semanas del trimestre con reporte de asistencia |
| Inv. célula | Personas ganadas en el mes/trimestre con origen célula (`Tipo_Reunion` / `Origen_Ganar`) |
| Inv. iglesia | Ganados del mes/trimestre con origen iglesia (Domingo, Somos Uno, Otros, etc.; no Migrados) |
| No realizadas | Semanas del mes/trimestre sin reporte |
| Encuentro | Personas de la célula que **completaron** encuentro en ese mes/trimestre (clases UV 5+6 o programa encuentro, con fecha de clase o inscripción) |
| Pos-encuentro / UV | Primera clase UV 7–10 en el periodo; si no hay, fecha de inscripción a Universidad de Vida |
| Cap. Destino | Primera inscripción a Cap. Destino (cualquier nivel) en el trimestre |

**Importante:** Encuentro / pos-encuentro del resumen y de la tabla mensual **no** repiten el total histórico de la célula en todos los meses. Solo cuentan en el mes (y en el trimestre) donde ocurrió el movimiento.

La gráfica **Asistencia por semana**: barra azul = semana reportada; gris = sin reporte. El número es el total de asistentes de esa semana.

La ruta `celulas/celugrama/guardar` ya no guarda indicadores manuales (solo queda por si hay temas).

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

## Reporte individual por célula (24 nov 2026 · verificado 26 nov)

Reporte **nuevo y aparte** del detalle/Celugrama. No modifica la lógica de listados ni del reporte operativo.

**Verificación 26 nov 2026 (prod, célula Amparo/Doris):** las semanas de ganados en 0 son coherentes (nadie nuevo ese mes en la célula). Formación (encuentro / bautismo / Cap. Destino) sale de `ProgresoFormacionFlujo` sobre integrantes activos sin el líder.

### Cómo abrirlo

| Desde | Acción |
| --- | --- |
| Listado de células | Ícono de hoja / reporte por fila |
| Detalle / Celugrama | Botón **Reporte** |
| URL | `?url=celulas/reporte-individual&id=ID_CELULA` |

### Cabecera de la célula

1. **Fecha de apertura**
2. **Fecha de cierre** si está cerrada; si sigue activa, **tiempo abierta** (años/meses/días)
3. **Personas ganadas · mes actual**: cuadrícula compacta tipo Excel con una columna por semana (lun–dom) que toca el mes, el número de ganados por semana, la semana en curso marcada, y **Total del mes**

#### Cómo cuenta “personas ganadas” en este reporte

- Persona **nueva**: `Es_Antiguo = 0`
- Fecha: `DATE(Fecha_Registro)` dentro de la semana (recortada al mes si la semana cruza de mes)
- Debe estar **asignada hoy a esa célula**: `Id_Celula = Id de la célula del reporte`
- Estado activo (`Activo` o vacío)

No separa origen iglesia vs célula; es “nuevos registrados en esa semana que hoy están en esta célula”.

#### Relación con el Dashboard Ganar (pendiente de cliente)

**Misma idea de negocio** (personas nuevas por `Fecha_Registro`), **pero no es el mismo cálculo** que el dashboard general.

| | Reporte individual de célula | Dashboard Ganar |
| --- | --- | --- |
| Agrupa por | Célula (`Id_Celula`) | Líder / red (líder de 12) |
| Qué muestra | Solo ubicados en **esa** célula | GI, GC, UC, fono, visita, totales |
| Semana | Semanas del **mes actual** (lun–dom) | Suele usar la **semana vencida** |
| Origen ganado en | No clasifica | Sí (iglesia / célula / otros) |

**Pendiente con el cliente (24 nov 2026):** decidir si se deja así (vista por célula) o se alinea con el dashboard (por ejemplo solo GC, misma semana vencida, u otra regla). Hasta nueva instrucción no se cambia el conteo.

### Formación de integrantes (embudo)

Sobre los miembros activos (sin el líder): de N → cuántos fueron a encuentro, cuántos bautizados, cuántos en Cap. Destino (N1 / N2 / N3). Los hechos salen de `ProgresoFormacionFlujo` (inscripciones, asistencias de escuela y escalera).

### Tabla «Detalle por integrante»

Columnas:

| Columna | Contenido |
| --- | --- |
| Integrante | Solo el nombre (sin documento ni teléfono) |
| Tiempo activo | Desde la primera asistencia en esa célula; si no hay, desde `Fecha_Registro` |
| Desde | Fecha de inicio usada para el tiempo activo |
| Fecha retiro | Fecha en que salió; `—` si sigue activa |
| Observación retiro | Motivo legible; `—` si sigue activa |
| Encuentro / Bautizado / Cap. Destino / Nivel | Estado formativo |

Los retirados aparecen en la misma tabla con marca **Retirado**. El pie muestra activos, retiros y totales de formación (solo sobre activos).

### Exportación Excel

- Por célula: botón **Descargar Excel** → `celulas/reporte-individual/exportar`
- Por ministerio: en el listado, botón **Excel reporte** de cada ministerio (o filtrando por ministerio) → `celulas/reporte-individual/exportar-ministerio`

El archivo es CSV con `;` y BOM (mismo patrón del sistema). El Excel del ministerio trae bloque de resumen por célula y luego detalle por integrante.

Los valores del Excel se **normalizan a ASCII** en `BaseController::sanitizeExportValue` (sin tildes, sin ñ, sin flechas ni símbolos raros) para que abran limpios en Excel.

### Historial de salidas (`persona_salida_celula`)

Tabla creada automáticamente por `PersonaSalidaCelula`. Se registra al salir:

| Motivo | Cuándo | Observación típica |
| --- | --- | --- |
| `no_se_dispone` | No se dispone (Celugrama / escalera) | La observación que escribe el usuario |
| `transferida` | Migrar a otra célula del mismo ministerio | `Transferida a: [nombre]` |
| `eliminada` | Quitar de la célula | Texto libre o «Eliminada de la celula» |

Solo quedan guardadas las salidas **desde el despliegue de esta función**. Las anteriores no tenían historial.

### Rutas y permisos

| Ruta | Permiso |
| --- | --- |
| `celulas/reporte-individual` | `celulas:ver` |
| `celulas/reporte-individual/exportar` | `celulas:exportar_datos` o `celulas:ver` |
| `celulas/reporte-individual/exportar-ministerio` | `celulas:exportar_datos` o `celulas:ver` |

### Archivos del reporte individual

- `views/celulas/reporte_individual.php` — pantalla
- `app/Controllers/CelulaController.php` — `reporteIndividual`, `exportarReporteIndividual`, `exportarReporteIndividualMinisterio`, armado de datos
- `app/Models/PersonaSalidaCelula.php` — historial de salidas
- `app/Controllers/AsistenciaController.php` / `PersonaController.php` — registran la salida al migrar / no se dispone / quitar
- `app/Controllers/BaseController.php` — normalización ASCII del Excel
- Enlaces en `views/celulas/lista.php` y `views/celulas/detalle.php`

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
- Eliminadas vistas sueltas: `asistencias/porCelula`, `asistencias/miembros-celula`. Ya no hay formulario suelto de asistencias.
- `asistencias/quitarDeCelula` sigue disponible como API de salida (registra historial) cuando se usa desde el flujo de célula.
- Se retiró del Celugrama la sección **Temas / texto bíblico** (el tema sigue pudiendo ir en el modal al reportar).
- Se retiraron de la página los scripts sueltos de diagnóstico de Teen y de marcar Ganar completo.

## Archivos principales (Celugrama + reporte)

- `views/celulas/detalle.php` — Celugrama + modales (reporte, escalera, migrar, no se dispone) + enlace al reporte individual.
- `views/celulas/partials/celugrama.php` — ficha, KPIs, gráfica, tabla, indicadores.
- `views/celulas/reporte_individual.php` — reporte individual por célula.
- `views/celulas/lista.php` — accesos a Celugrama, reporte individual y Excel por ministerio.
- `app/Controllers/CelulaController.php` — `detalle`, `guardarCelugrama`, reporte individual y exportaciones.
- `app/Models/Celugrama.php` — temas/indicadores de trimestre.
- `app/Models/PersonaSalidaCelula.php` — historial de retiros.
- `app/Controllers/AsistenciaController.php` — registrar (POST), migrar, no se dispone, quitar; GET viejo redirige.
- `app/Config/routes.php` / `route_permissions.php` — rutas y permisos.
