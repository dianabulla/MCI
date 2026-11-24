# Pendientes por ubicar (Por conectar)

Registro del 24 de noviembre de 2026. Desplegado a producción por FTP el mismo día.

Objetivo: que los líderes de célula / 12 / pastor **vean** a quienes les falta ubicar y puedan **asignar célula** sin salir de la Escalera.

## Quién aparece

Personas con **ministerio** a las que les falta **líder**, **célula**, o ambos (lista **Por conectar**).

No entran: marcados «No se dispone», ni quienes ya están en **En seguimiento** (salen de Por conectar al guardar nota).

## Visibilidad (campana + listado)

Se usa `DataIsolation::generarFiltroPersonasPendienteConsolidar()` (mismo criterio que Almas ganadas), no solo el anclaje de red:

| Rol | Qué ve |
| --- | --- |
| Acceso total | Toda la iglesia |
| Líder 144 | Su anclaje de red |
| Pastor / líder 12 / líder de célula | Su anclaje **o** personas de su ministerio **sin líder** |

Así un líder ve pendientes de su ministerio aunque aún no estén anclados a su red.

Archivos: `views/layout/header.php` (conteo campana), `PersonaController::index` (lista).

## Campana

En la topbar el ítem se llama **Pendientes por ubicar** y enlaza a:

`personas&perfil=pendientes_conectar`

Texto de ayuda: «Tienen ministerio y les falta líder o célula. Ábrelos y usa Ubicar.»

Archivo: `views/layout/_topbar_notificaciones.php`.

## Ubicar inline

En cada fila de **Por conectar**, si el usuario puede editar (`AuthController::puedeEditarPersonasConsulta()`):

1. Elige una célula del desplegable (células visibles por `generarFiltroCelulas()`, filtradas por ministerio de pantalla si aplica).
2. Pulsa **Ubicar**.
3. POST a `personas/ubicarRapido` → `PersonaController::ubicarRapido()`.

Efecto en la ficha:

- Asigna `Id_Celula`.
- Completa líder y ministerio desde la célula (`completarLiderYMinisterioDesdeCelula`), sin pisar valores ya elegidos.
- Actualiza `Fecha_Asignacion_Lider` si la columna existe.
- Marca checklist Ganar: líder+ministerio y «ubicado en célula»; recalcula `Proceso` si aplica.
- Encola notificación de cambio de asignación.
- Vuelve a la Escalera con `perfil=pendientes_conectar`.

Ruta: `app/Config/routes.php` → `personas/ubicarRapido`.

## KPI

Si hay pendientes, encima de la lista:

- Total
- Promedio de días desde `Fecha_Registro`
- Más antiguo (días)
- Conteos **7+** y **14+** días

La lista **Por conectar** se abre sola si hay pendientes o si `perfil=pendientes_conectar`.

## Permisos

Asignar / reasignar en Almas ganadas y Ubicar en Escalera usan `puedeEditarPersonasConsulta()` (OR de permisos personas + consulta), no solo el módulo ganar.

Archivo alineado: `views/personas/ganar.php`.

## Cómo probar en producción

1. Entrar como líder de célula/12 con ministerio que tenga fichas sin líder o sin célula.
2. Campana: debe mostrar **Pendientes por ubicar** con número > 0 si aplica.
3. Abrir el enlace → Escalera con Por conectar abierta y franja KPI.
4. En una fila, elegir célula → **Ubicar**.
5. Confirmar que desaparece de Por conectar (o deja de faltar lo asignado) y que líder/ministerio quedaron de la célula.

## Archivos desplegados (FTP)

```
app/Config/routes.php
app/Controllers/PersonaController.php
views/layout/header.php
views/layout/_topbar_notificaciones.php
views/personas/lista.php
views/personas/ganar.php
docs/PENDIENTES_POR_UBICAR.md
docs/DETALLES_MENORES_2026-11-24.md
docs/ESCALERA_ALMAS_GANADAS.md
```
