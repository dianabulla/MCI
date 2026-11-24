# Detalles menores de la plataforma — 24 nov 2026

Implementación a partir del documento de feedback *Detalles menores de la plataforma de la Iglesia*. Desplegado a producción por FTP el mismo día.

## 1. Inicio → Programas (conteo por red/ministerio)

El badge **Programas / inscritos** del panel de control ya no cuenta toda la iglesia: usa `DataIsolation::generarFiltroPersonas()` en `EscuelaFormacionInscripcion::contarPersonasUnicasInscritas()`.

- Archivos: `HomeController.php`, `EscuelaFormacionInscripcion.php`

## 2. Seguimiento — guía de uso

Al abrir **En seguimiento** (Escalera del éxito) aparece una caja breve de ayuda: anotar avances y salir de la lista al tener líder y célula.

- Archivo: `views/personas/lista.php`

## 3. Exportar Excel con filtros de pantalla

Botones **Exportar Excel** en Por conectar, En seguimiento y Etapas. El export reutiliza `personas/exportarExcel` con los filtros actuales (ministerio, líder, buscar, etapa, red, perfil).

- Soporta `perfil=en_seguimiento` y `perfil=pendientes_conectar`.
- Archivos: `PersonaController.php`, `views/personas/lista.php`

## 4. Teléfono + escalera rápida G C D E

- Columna **Teléfono** en listados de conexión y etapas: chip con llamada y WhatsApp (prefijo `57` si hace falta).
- **G C D E** también en la tabla de etapas (mismo patrón de escalera rápida).

- Archivo: `views/personas/lista.php`

## 5. Cerrar ciclo UV tras Encuentro

En el listado de inscritos UV, quienes **culminaron encuentro** tienen el botón **Ciclo**:

1. Marca la inscripción con `Ciclo_Cerrado` / `Fecha_Cierre_Ciclo` (conserva historial).
2. Sale del listado del ciclo activo.
3. Limpia UV/Encuentro en la escalera y deja la persona en **Ganar** (no toca liderazgo ni Discipular).
4. **Inscribir UV** vuelve a mostrarse verde para el próximo ciclo.

Ruta: `escuelas_formacion/inscritos/cerrar-ciclo` → `EscuelaFormacionRegistroController::cerrarCicloInscripcionListado()`.

- Archivos: `routes.php`, `EscuelaFormacionRegistroController.php`, `EscuelaFormacionInscripcion.php`, `EscaleraAsistenciaSync.php`, `PersonaController.php` (meta checklist), `listado_inscritos.php`

## 6. Discipular — métricas en lugar de escalera no editable

En casillas de **liderazgo**, se oculta G/C/D/E (que no se podían editar) y se muestra:

- Personas a cargo (`red_total`)
- Equipo 1–12
- Células que dirige (`Celula::mapaNombresCelulasPorLideres`)

La escalera rápida queda solo para discípulos.

- Archivos: `MinisterioController.php`, `Celula.php`, `jerarquia_red_navegacion.php`

## Archivos desplegados (FTP)

```
app/Config/routes.php
app/Controllers/EscuelaFormacionRegistroController.php
app/Controllers/HomeController.php
app/Controllers/MinisterioController.php
app/Controllers/PersonaController.php
app/Helpers/EscaleraAsistenciaSync.php
app/Models/Celula.php
app/Models/EscuelaFormacionInscripcion.php
views/discipular/partials/jerarquia_red_navegacion.php
views/escuelas_formacion/listado_inscritos.php
views/personas/lista.php
```
