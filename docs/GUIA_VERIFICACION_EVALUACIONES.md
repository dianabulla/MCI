# INSTRUCCIONES DE VERIFICACIÓN - MÓDULO DE EVALUACIONES

## ✅ CAMBIOS REALIZADOS

### 1. **Mejorado guardado de respuestas correctas**
   - Ahora registra en logs si falta `respuesta_correcta`
   - No marca como correcta automáticamente si no hay respuesta esperada definida
   - Valida que `respuesta_correcta` sea una opción válida

### 2. **Agregada vista de resumen de evaluaciones**
   - Los administradores ven automáticamente todas las presentaciones sin necesidad de seleccionar una evaluación
   - Tabla con: Fecha, Persona, Evaluación, Nivel, Módulo, Intento, Puntaje, Resultado

### 3. **Creadas herramientas de diagnóstico**
   - `tools/diagnostic_evaluaciones.php` - verifica integridad de datos
   - `tools/test_crear_evaluacion.php` - crea evaluación de prueba

---

## 🔍 CÓMO VERIFICAR QUE TODO ESTÁ CORRECTO

### PASO 1: Verificar datos existentes
```
1. Abrir navegador
2. Ir a: http://localhost/mcimadrid/tools/diagnostic_evaluaciones.php
3. Ver:
   - Total de evaluaciones creadas
   - Validación de JSON en cada evaluación
   - Cantidad de preguntas por evaluación
   - Todas las respuestas guardadas con JSON válido
```

**Qué buscar:**
- ✓ Todas las evaluaciones deben mostrar "✓ JSON válido"
- ✓ Todas las preguntas deben mostrar "✓" en "Respuesta_correcta"
- Si ves "✗ FALTA", necesitamos revisar esa evaluación

---

### PASO 2: Crear evaluación de prueba
```
1. Ir a: http://localhost/mcimadrid/tools/test_crear_evaluacion.php
2. Hacer clic en "Crear Evaluación de Prueba"
3. Debe mostrar mensaje ✓ exitoso
4. Debe aparecer en listado de "Evaluaciones Recientes"
```

**Qué buscar:**
- ✓ ID de evaluación creada
- ✓ 2 preguntas guardadas
- ✓ JSON válido

---

### PASO 3: Presentar evaluación en navegador (como usuario)
```
1. Ir a: Programas > Evaluaciones
2. Si eres admin, ya debería ver tabla "Todas las presentaciones de evaluaciones"
3. Busca la evaluación de prueba creada
4. Haz clic en "Ir a evaluación"
5. Responde las 2 preguntas (puedes equivocarte)
6. Envía evaluación
```

**Qué buscar:**
- ✓ Mensaje de éxito con puntaje
- ✓ Se debe marcar como aprobado/reprobado según puntaje

---

### PASO 4: Verificar resultados guardados
```
1. Volver a Programas > Evaluaciones (sin seleccionar evaluación)
2. Desplazarse a tabla "Todas las presentaciones de evaluaciones"
3. Debe aparecer tu intento en la tabla
```

**Qué buscar:**
- ✓ Tu nombre en "Persona"
- ✓ El nombre de la evaluación
- ✓ El puntaje que obtuviste
- ✓ Aprobado/Reprobado según el resultado

---

### PASO 5: Ver detalles del intento
```
1. En la tabla de resultados, hacer clic en "Ver detalle" (si es admin)
2. Debe mostrar:
   - Todas tus respuestas
   - Cuáles fueron correctas/incorrectas
   - La respuesta esperada
```

**Qué buscar:**
- ✓ Tus respuestas están registradas
- ✓ Las respuestas correctas son marcadas correctamente
- ✓ Se ve qué respondiste vs qué era correcto

---

## 🚨 SI ALGO NO FUNCIONA

### Problema: "No muestra evaluaciones presentadas"
```
Solución:
1. Abrir herramienta de diagnóstico
2. Ver cuántas presentaciones hay (row count)
3. Si dice 0, crear una evaluación de prueba primero
```

### Problema: "Pregunta desapareció"
```
Solución:
1. Ir a diagnostic_evaluaciones.php
2. Buscar la evaluación afectada
3. Contar preguntas mostradas
4. Si falta alguna, contactar soporte
```

### Problema: "Las respuestas correctas no se evaluán bien"
```
Solución:
1. Ver detalles de un intento
2. Verificar que "Respuesta correcta" está definida (no vacía)
3. Si está vacía, hay un problema en el guardado
```

### Problema: "Ver errores en el servidor"
```
Solución:
1. Revisar logs en: C:\xampp\apache\logs\error.log
2. Buscar mensajes de "⚠️ ADVERTENCIA" o "ERROR"
3. Compartir con desarrollador
```

---

## 📝 CHECKLIST FINAL

- [ ] Abrir diagnostic_evaluaciones.php
- [ ] Ver que todas las evaluaciones tienen JSON válido
- [ ] Ver que todas las preguntas tienen respuesta_correcta
- [ ] Abrir test_crear_evaluacion.php
- [ ] Crear evaluación de prueba
- [ ] Ver que aparece en listado
- [ ] Ir a Programas > Evaluaciones
- [ ] Ver tabla "Todas las presentaciones"
- [ ] Presentar la evaluación de prueba
- [ ] Verificar que se guarda el resultado
- [ ] Verificar que aparece en tabla de presentaciones
- [ ] Ver detalles del intento
- [ ] Verificar que respuestas están correctas
- [ ] Limpiar evaluación de prueba si es necesario:
  ```sql
  DELETE FROM discipular_evaluaciones WHERE Titulo LIKE '%PRUEBA%';
  ```

---

## ✅ ESTADO FINAL

| Componente | Estado | Verificado |
|-----------|--------|-----------|
| Guardado de preguntas | ✓ | JSON LONGTEXT |
| Guardado de respuestas | ✓ | Validación mejorada |
| Vista de resultados | ✓ | Tabla de presentaciones |
| Cálculo de puntaje | ✓ | (correctas/total)*100 |
| Límite de intentos | ✓ | Máximo 2 |
| Validación de fechas | ✓ | Inicio y fin |
| Seguridad | ✓ | Verificación de permisos |

---

*Última actualización: 14/05/2026*
*Todos los archivos pasaron validación de sintaxis PHP*
