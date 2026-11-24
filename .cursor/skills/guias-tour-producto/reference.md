# Referencia técnica — tours MCI Madrid

## Stack

- Librería: **Driver.js** (`window.driver.js.driver`).
- Script: `public/assets/js/product-tour.js`
- Estilos: `public/assets/css/product-tour.css`
- Carga: `views/layout/footer.php` solo si hay `data-product-tour-role` en body.
- Rol: `views/layout/header.php` → `body[data-product-tour-role="maestro|discipulo"]`

## Anclas

Preferir:

```html
<div data-tour="hub-evaluaciones">...</div>
```

Nombres: `kebab-case`, estables, prefijo de contexto si hace falta (`maestro-`, `disc-`, `sidebar-`).

El builder usa `document.querySelector` y **omite** pasos cuyo selector no existe en la página actual. Diseña tours que se degradan bien entre pantallas del mismo flujo (solo se muestran los pasos presentes).

## Persistencia (regla de oro)

Clave:

```text
mci_product_tour_{role}_{huellaFlujo}_done
```

La **huella del flujo** se calcula sola a partir de `element` + `title` (en orden) de las defs del rol. No hace falta `TOUR_VERSION` manual.

| Cambio en defs | ¿Reinicia obligación? |
|----------------|------------------------|
| Alta/baja/reorden de pasos | Sí |
| Cambio de `element` o `title` | Sí |
| Solo `description` / `side` | No |

### Comportamiento

| Evento | Primera vez (huella nueva o sin done) | Ya completó esta huella |
|--------|---------------------------------------|-------------------------|
| Auto-start en rutas del módulo | Sí | No |
| `?tour=1` | Sí | Sí |
| Botón `#mci-tour-launcher` | Sí | Sí |
| Cerrar / overlay / Escape | Bloqueado | Permitido |
| `markDone` | Solo al **Listo** final | Solo si vuelve a terminar |

Implementación: `onNextClick` marca done en el último índice; `onDestroyStarted` no destruye si aún no hay done; clase `mci-driver-popover--required` oculta la X.

## Patrón de step

```javascript
{
  element: '[data-tour="slug"]',
  popover: {
    title: 'N. Título',
    description: 'Acción. Contexto. Resultado.',
    side: 'bottom',
    popoverClass: 'mci-driver-popover'
  }
}
```

Textos UI del driver (mantener español):

- `progressText: '{{current}} de {{total}}'`
- `nextBtnText: 'Siguiente'`
- `prevBtnText: 'Anterior'`
- `doneBtnText: 'Listo'`
- Primera vez: ocultar/deshabilitar close; no mostrar «Saltar».
- Después: se puede mostrar cerrar; opcional botón «Saltar» solo si `completed`.

## Auto-start (ejemplo Cap. Destino)

Rutas actuales de autoarranque (ajustar al ampliar módulos):

- `url=home/material/capacitacion-destino`
- `url=programas/evaluaciones`
- `url=programas/tareas`

Al añadir un módulo nuevo, restringir auto-start a las URLs de ese módulo/función para no interrumpir otros flujos.

## Roles

| Valor `data-product-tour-role` | Builder | Notas |
|--------------------------------|---------|--------|
| `maestro` | `buildMaestroSteps()` | Menú Cap. Destino profesor |
| `discipulo` | `buildDiscipuloSteps()` | Vista simplificada discípulo |

Nuevos roles: añadir detección en header/footer + builder + storageKey propio.

## Checklist de verificación

- [ ] Primera sesión: no hay forma de saltar/cerrar el tour.
- [ ] Abandonar a mitad (reload) → sigue sin marcar done → vuelve a auto-iniciar.
- [ ] Completar hasta Listo → `localStorage` = `1`.
- [ ] Segunda visita: no auto-start; launcher visible; se puede cerrar.
- [ ] `?tour=1` relanza el tour.
- [ ] Steps omitidos si el selector no está no rompen el drive.
- [ ] Copy coincide con botones/labels reales de la vista.
- [ ] En móvil el popover no tapa el control resaltado de forma inutilizable.

## Ampliación a otros módulos

Orden sugerido al documentar/implementar la plataforma completa:

1. Cap. Destino (ya base)
2. Personas / Ganar
3. Células / Asistencias
4. Talleres / formularios públicos
5. Escuelas de formación / UV
6. Reportes
7. Permisos / roles (solo admin)

Por cada uno: guía escrita (opcional en `docs/`) + tour interactivo si el rol lo usa a diario.

## Qué no subir a producción por FTP

Igual que el resto del proyecto: no subir `.env`, uploads, logs. Sí subir solo archivos tocados del tour (`product-tour.js`, CSS, vistas con `data-tour`, layout si aplica).
