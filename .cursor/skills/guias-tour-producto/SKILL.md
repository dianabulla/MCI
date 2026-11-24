---
name: guias-tour-producto
description: >-
  Experto en guías paso a paso y tours del producto MCI Madrid. Diseña, documenta e
  implementa recorridos interactivos por cada función de cada módulo (Driver.js,
  data-tour, product-tour.js). Aplica la regla obligatoria: la primera vez el usuario
  debe ver la guía completa y completar el paso a paso; solo después puede saltar.
  Usar cuando el usuario pida guías, tours, onboarding, walkthrough, ayuda contextual,
  product tour, o documentar/implementar el recorrido de un módulo o función.
disable-model-invocation: false
---

# Guías paso a paso y tour del producto

## Rol

Actúa como experto en **guías paso a paso** y **product tours** de la plataforma MCI Madrid. Cada módulo y cada función relevante deben tener un recorrido claro, accionable y coherente con la UI real.

## Regla de oro (obligatoria)

> **Al menos una vez** el usuario debe haber **visto la guía completa** y haber **realizado el paso a paso siguiendo la guía**. Solo **después** se le puede ofrecer **Saltar / Cerrar**.

Además el recorrido es **interactivo por currículo**:

1. Descubre los módulos **visibles en el menú del usuario** (admin, líder, etc.).
2. **Obliga a entrar** a cada módulo (clic real en el sidebar).
3. Dentro, muestra acciones/funciones reales y exige **interactuar** (`requireClick`) con filtros, botones o enlaces.
4. Solo al terminar todos los módulos de su menú se marca el currículo como hecho.
5. Si cambia el flujo (huella), se vuelve a exigir.

Botón **Guías** abajo a la **izquierda** (el Asistente queda a la derecha).

Detalle técnico: [reference.md](reference.md).

## Cuándo aplicar esta skill

- Crear o ampliar tours por rol/módulo/función.
- Escribir textos de guía (títulos, descripciones, orden de pasos).
- Añadir `data-tour` en vistas y steps en `public/assets/js/product-tour.js`.
- Revisar que un tour cumpla la regla de primera completación obligatoria.
- Documentar el flujo humano de un módulo (guía escrita + tour interactivo).

## Inventario actual (punto de partida)

Infraestructura multi-módulo **solo para líderes** (no admins):

- **Interruptor:** `PRODUCT_TOUR_ENABLED` en `app/Config/config.php` (por defecto **false** / desactivado; documentado en `docs/GUIAS_PRODUCTO.md`).
- Audiencia (si el flag está on): `AuthController::debeVerGuiaProducto()` → líder de célula, 12, 144 u otros con «lider». **Excluye administradores.**
- `public/assets/js/product-tour.js` — currículo interactivo por módulos visibles del menú.
- `public/assets/css/product-tour.css` — launcher «Guías» (izquierda), menú y popover.
- `views/layout/header.php` / `footer.php` — `data-product-tour="1"` solo si `debeVerGuiaProducto()`.
- Anclas `data-tour` en sidebar y cabeceras de módulos.

Al ampliar: **añadir defs en `TOURS`** y anclas `data-tour`; no inventar otra librería.

## Workflow al crear/ampliar una guía

Copia y marca progreso:

```
Guía / tour:
- [ ] 1. Identificar módulo, función y roles afectados
- [ ] 2. Mapear pantallas y selectores reales (data-tour)
- [ ] 3. Redactar pasos (una acción clara por paso)
- [ ] 4. Implementar anclas + steps en product-tour.js
- [ ] 5. Aplicar regla primera completación (sin saltar hasta Listo)
- [ ] 6. Versionar storageKey si el recorrido cambió de forma material
- [ ] 7. Probar primera visita, abandono, completado y repetición
```

### 1. Identificar alcance

Definir:

- **Módulo** (ej. Cap. Destino, Células, Personas, Talleres, Reportes).
- **Función** (ej. crear evaluación, registrar asistencia, ganar alma).
- **Roles** (maestro, discípulo, líder, admin, etc.).
- **Rutas** donde el tour puede arrancar o continuar.

Una guía = un recorrido coherente por función (o por rol dentro del módulo). No mezclar flujos ajenos en el mismo tour.

### 2. Mapear la UI

Para cada paso:

1. Localizar el elemento en la vista PHP.
2. Asegurar `data-tour="slug-estable"` (preferir anclas estables; evitar solo clases volátiles).
3. Confirmar que el elemento existe en el estado de pantalla donde el paso aplica (el builder ya omite selectores ausentes).

### 3. Redactar pasos

Cada paso debe tener:

- **Título** corto (qué es / qué número de acción).
- **Descripción** de 1–3 frases: qué hacer, por qué, resultado esperado.
- **Side** coherente (`top` / `bottom` / `left` / `right`) para no tapar el control.

Estilo de voz:

- Tú / imperativo claro («Pulsa…», «Elige…», «Completa…»).
- Español de la plataforma; mismos nombres de menús y botones que en la UI.
- Sin jerga técnica interna (no mencionar `data-tour`, Driver, localStorage al usuario final).

Plantilla de paso:

```text
Título: N. Nombre de la acción
Descripción: [Acción concreta]. [Detalle útil]. [Resultado o siguiente movimiento].
```

### 4. Implementar

1. Añadir `data-tour` en las vistas tocadas.
2. Extender el builder de steps del rol correspondiente en `product-tour.js` (o añadir rol nuevo si aplica).
3. Incluir el rol en `data-product-tour-role` del layout cuando corresponda.
4. Respetar CSS existente del launcher/popover.

### 5. Regla de completado (código)

Ya implementado en `public/assets/js/product-tour.js`:

- Persistencia: `mci_product_tour_{role}_{huellaFlujo}_done`.
- **Huella automática** del flujo (orden + `element` + `title`). Si cambias el recorrido, la clave cambia sola y se exige completar otra vez. Ajustes solo de `description` **no** reinician.
- Primera vez: `allowClose: false`; cierre bloqueado en `onDestroyStarted`.
- `markDone` **solo** al pulsar **Listo** en el último paso (`onNextClick`).
- Ya completada (misma huella): se puede saltar; auto-start off; launcher disponible.
- **No** hardcodear `TOUR_VERSION` manual: la huella sustituye el versionado a mano.

### 6. Guía escrita (cuando pidan documentación)

Además del tour interactivo, se puede dejar una guía textual por módulo/función:

```markdown
# Guía: [Módulo] — [Función]
Rol: [quién]
Precondiciones: [permisos, datos previos]

## Objetivo
[Una frase]

## Paso a paso
1. ...
2. ...

## Errores frecuentes
- ...

## Tour asociado
- Rol tour: ...
- Version: ...
- Anclas data-tour: ...
```

## Criterios de calidad

- Cobertura: cada función crítica del módulo tiene pasos o queda explícitamente fuera de alcance.
- Orden = flujo real de trabajo (entrada → acción → resultado).
- Textos alineados con etiquetas visibles de la UI.
- Primera visita no se puede saltar; segunda sí.
- Completar de verdad el recorrido (llegar a Listo), no solo abrir y cerrar.
- Mobile: lados de popover y launcher no bloquean CTAs principales.

## Anti-patrones

- Marcar «done» al cerrar con la X la primera vez.
- Tours que solo «enseñan» sin indicar la acción a realizar.
- Pasos sobre elementos que no existen en esa vista/estado.
- Un solo tour gigante que mezcla módulos distintos.
- Cambiar textos/pasos sin subir versión cuando la guía es sustancialmente nueva.

## Recursos

- Detalle de persistencia, Driver.js y checklist de PR: [reference.md](reference.md)
- Código base: `public/assets/js/product-tour.js`, `public/assets/css/product-tour.css`
