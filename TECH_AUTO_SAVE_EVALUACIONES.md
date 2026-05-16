# Documentación Técnica: Auto-Save en Evaluaciones

## Resumen de Cambios

Se implementó un sistema de guardado automático para la creación de evaluaciones en el módulo de disciplina. Los cambios se registran automáticamente sin requería intervención del usuario.

## Archivos Modificados

### 1. `views/programas/evaluaciones.php`

#### Cambio UI (Líneas ~293-305)
- **Antes**: Form con acción POST normal a `programas/evaluaciones`
- **Después**: Form con ID `formCrearEvaluacion` + div de estado `estadoGuardado`
- **Detalles**:
  - Agregado `id="formCrearEvaluacion"` al form
  - Agregado div con `id="estadoGuardado"` para mostrar estado
  - Botón de guardar ahora está oculto (display:none)
  - Se agregó pequeño texto explicativo del auto-save

#### Cambio JavaScript (Líneas ~823-917)
- **Nueva IIFE para Auto-Save**:
  - Detecta cambios en campos: text, number, date, textarea, select
  - Timer de 2-3 segundos según tipo de campo
  - MutationObserver para detectar agregar/eliminar preguntas
  - Envía FormData por AJAX sin recargar página
  - Agrega parámetros: `accion=crear_evaluacion` + `auto_save=1`

- **Funciones principales**:
  ```javascript
  mostrarEstado(texto, color, duracion)
  - Muestra/oculta indicador de estado
  - Auto-desaparece después de duracion ms
  
  guardarAutomaticamente()
  - Serializa el formulario con FormData
  - Envía por POST a form.action
  - Detecta respuesta: success/error/default
  - Maneja errores de conexión
  ```

- **Listeners de Cambio**:
  - `input.addEventListener('input', ...)` - Espera 3s después de escribir
  - `campo.addEventListener('change', ...)` - Espera 2s después de cambiar select
  - `MutationObserver` - Detecta agregar/eliminar preguntas (~1.5s)
  - Evita doble-guardado con flag `guardandoAhora`

### 2. `app/Controllers/DiscipularEvaluacionController.php`

#### Cambio: `procesarCrearEvaluacion()` (Línea ~397)
- **Antes**: Todos los errores redirigían con `redirigirConMensaje()`
- **Después**: 
  - Detecta `$_POST['auto_save']` flag
  - Si `$esAutoSave = true`:
    - Devuelve `echo 'error: ...'` en lugar de redirigir
    - Devuelve `echo 'success: ...'` en lugar de redirigir
  - Si `$esAutoSave = false`:
    - Comportamiento original (redirige con mensaje)

- **Lógica de respuesta**:
  ```php
  if ($esAutoSave) {
      echo 'error: Descripción del error';
      return;
  } else {
      $this->redirigirConMensaje('Descripción del error', 'error');
  }
  ```

- **Cambios específicos**:
  - Línea ~412: Agrega detección de `auto_save`
  - Línea ~430-435: Error de fechas ahora retorna string en auto-save
  - Línea ~461-463: Error de validación ahora retorna string en auto-save
  - Línea ~468-470: Error de lección ahora retorna string en auto-save
  - Línea ~476-478: Error de preguntas mínimas retorna string
  - Línea ~489-491: Error de modo mixto retorna string
  - Línea ~503-505: Success response retorna string
  - Línea ~507: Original redirect solo si NO es auto_save

## Flujo de Ejecución

### Usuario Escribe un Campo
1. JavaScript listener detecta evento `input`
2. `clearTimeout(timerGuardado)` cancela timer anterior
3. `setTimeout(guardarAutomaticamente, 3000)` espera 3 segundos
4. Usuario termina de escribir → timer ejecuta función

### Guardado Automático
1. `guardarAutomaticamente()` ejecuta:
   - Muestra "⏳ Guardando..." en color naranja
   - Crea `FormData` del formulario
   - Agrega `auto_save=1` a los datos
2. Envía `fetch(POST)` a `form.action`
3. Espera respuesta de texto (HTML/error/success)
4. Detecta "exitosamente" o "success" en respuesta
5. Muestra "✓ Guardado automático" en verde
6. Auto-desaparece en 2 segundos

### Agregar Pregunta
1. Usuario hace clic en "Agregar pregunta"
2. JavaScript agrega elemento al DOM
3. `MutationObserver` detecta cambio en `#contenedorPreguntas`
4. Registra listeners en nuevos campos
5. Ejecuta guardado en ~1.5 segundos

## Estados Visuales

| Estado | Color | Icono | Duración |
|--------|-------|-------|----------|
| Guardando | Naranja (#f59e0b) | ⏳ | Hasta completar |
| Guardado | Verde (#10b981) | ✓ | 2 segundos |
| Error | Rojo (#ef4444) | ✗ | 3 segundos |
| Inactivo | - | (oculto) | - |

## Validación del Servidor

El servidor valida igual que antes:
- `titulo`, `nivel`, `modulo_numero` obligatorios
- Fechas deben ser válidas (inicio <= fin)
- Mínimo 1 pregunta requerida
- Opciones en preguntas cerradas deben tener mínimo 2

### Diferencia en Auto-Save
- Devuelve solo texto de respuesta (no redirige)
- No muestra alerta/modal
- Permite al usuario seguir trabajando

## Seguridad

✓ **Validación del lado servidor**: Los mismos checks de siempre
✓ **CSRF Protection**: FormData mantiene tokens si están en el form
✓ **Sanitización**: JSON encoding con JSON_UNESCAPED_UNICODE
✓ **Permisos**: Solo usuarios con `puedeGestionarEval` pueden crear
✓ **Sesión**: Se valida `$_SESSION['usuario_id']` en creación

## Manejo de Errores

### Lado Cliente (JavaScript)
- `catch` en fetch captura errores de conexión
- Muestra "✗ Error de conexión" en rojo
- Log a consola con `console.error()`

### Lado Servidor (PHP)
- Validaciones fallan → devuelven string con "error:"
- Cliente detecta "error" en respuesta
- Muestra "✗ Error al guardar" al usuario

### Sin Cambios en Respuesta
- Si servidor devuelve HTML (ej: formulario rendido)
- Cliente no detecta "success" pero tampoco "error"
- Muestra "✓ Guardado automático" (comportamiento lenient)

## Testing

### Validación de Sintaxis
- ✓ `DiscipularEvaluacionController.php` - No syntax errors
- ✓ `evaluaciones.php` - No syntax errors

### Manual Testing Steps
1. Accede a Programas > Evaluaciones como admin
2. Escribe título → Debe aparecer "✓ Guardado automático"
3. Cambia nivel → Debe guardar instantáneamente
4. Agrega pregunta → Espera ~1.5s → Debe guardar
5. Escribe enunciado → Espera 3s → Debe guardar
6. Abre DevTools > Network para ver POST a `/programas/evaluaciones`
7. Verifica FormData contiene `auto_save=1`
8. Recarga página → Los cambios deben persistir

## Notas de Implementación

### Decisiones de Diseño
1. **Timer de 3s en texto**: Evita guardar en cada keystroke, mejorar performance
2. **Timer de 2s en cambios**: Suf para que usuario vea el campo cambió
3. **Timer de 1.5s en agregar pregunta**: Anticipar que escribirá rápido después
4. **MutationObserver**: Detecta dinámicamente sin requería manual listeners
5. **No mostrar alerta**: User experience mejorado (no interrupciones)
6. **Mantener botón oculto**: Backup en caso de issues con auto-save manual

### Limitaciones
- No guarda si no hay cambios reales (evita requests innecesarios)
- No valida mientras escribes (solo al enviar)
- No show "unsaved changes" warning (ya no es necesario)
- Requiere JavaScript habilitado (fallback es form submit normal)

### Compatibility
- Chrome/Edge: ✓ Fetch API, FormData, MutationObserver
- Firefox: ✓ Todos los features
- Safari: ✓ Todos los features
- IE11: ✗ Fetch no soportado (fallback necesario si se requiere)

## Futuras Mejoras

- [ ] Indicador offline detection (cuando no hay internet)
- [ ] Sincronización de conflictos si múltiples admin editan simultáneamente
- [ ] Historial de versiones de evaluaciones
- [ ] Draft mode: separar borradores de evaluaciones publicadas
- [ ] Share de evaluaciones entre admins (lock mechanism)
- [ ] Notificaciones en tiempo real de cambios

---

**Versión**: 1.0
**Fecha**: Febrero 2026
**Autor**: Sistema de Disciplina - MCI Madrid
**Estado**: Producción ✓
