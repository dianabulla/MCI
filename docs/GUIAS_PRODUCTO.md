# Guías paso a paso (product tour)

Recorrido interactivo para que los **líderes** conozcan cada módulo de la plataforma: entrar al menú, ver acciones reales e interactuar (filtros, botones, enlaces).

## Estado actual

**Desactivado en producción** (`PRODUCT_TOUR_ENABLED = false`).

El código y las anclas `data-tour` están listos. Para reactivar:

1. En `app/Config/config.php`, poner `PRODUCT_TOUR_ENABLED` en `true`, **o**
2. Definir variable de entorno `PRODUCT_TOUR_ENABLED=1` en el hosting.
3. Subir por FTP al menos `app/Config/config.php` (y `public/assets/.build` si conviene invalidar caché).

## Audiencia

Solo si el flag está activo **y** `AuthController::debeVerGuiaProducto()`:

- Líder de célula, líder de 12, líder de 144
- Otros roles cuyo nombre normalizado contiene `lider`
- **No** administradores

## Cómo funciona

| Pieza | Ubicación |
|-------|-----------|
| Interruptor | `PRODUCT_TOUR_ENABLED` en `app/Config/config.php` |
| Quién la ve | `AuthController::debeVerGuiaProducto()` |
| Carga assets | `views/layout/header.php` (`data-product-tour`) + `footer.php` (Driver.js + JS/CSS) |
| Lógica | `public/assets/js/product-tour.js` |
| Estilos | `public/assets/css/product-tour.css` (botón Guías a la **izquierda**; el Asistente queda a la derecha) |
| Anclas menú | `views/layout/_sidebar_nav_dynamic.php` + enlaces del sidebar |
| Skill Cursor | `.cursor/skills/guias-tour-producto/` |

### Reglas de uso

1. La primera vez el líder **no puede saltar**: debe completar el currículo.
2. El sistema arma la cola con los módulos **visibles en su menú**.
3. Obliga a **entrar** a cada módulo (clic en el sidebar) y a **interactuar** con acciones (`requireClick`).
4. Si cambia el flujo de pasos (huella), se vuelve a exigir completar.
5. Botón **Guías** (abajo izquierda) para reiniciar o repetir un módulo cuando ya completó.

### Módulos cubiertos (si aparecen en el menú del líder)

Inicio, Escalera del éxito, Células, Programas, Discipular, Teens, Nehemias, Talleres, Dashboard Ganar, Cap. Destino / Material, Servicio Social. Administración solo si el rol líder la tuviera en menú (normalmente no).

## Reactivar checklist

- [ ] `PRODUCT_TOUR_ENABLED = true` (o env `1`)
- [ ] FTP: `config.php` (+ `.build` si hace falta)
- [ ] Probar con cuenta **líder de célula** (Ctrl+F5)
- [ ] Confirmar que **admin** no ve el botón Guías
