<!-- Resumen de Implementación: Cambio de Segmento en Escuelas de Formación -->

✅ IMPLEMENTACIÓN COMPLETADA: "Cambio de Segmento"

OBJETIVO:
Permitir cambiar el grupo/segmento de una persona inscrita, aunque su edad no corresponda 
automáticamente a ese segmento. Ejemplo: Raúl (28 años, Joven) puede cambiar a "Hombres Adultos".

═══════════════════════════════════════════════════════════════════════════════════

📋 CAMBIOS REALIZADOS:

1. BASE DE DATOS (app/Models/EscuelaFormacionInscripcion.php)
   ✓ Agregada columna: Segmento_Preferido (VARCHAR 60, NULL)
   ✓ La columna se crea automáticamente si no existe

2. MODELO (app/Models/EscuelaFormacionInscripcion.php)
   ✓ Nuevo método: actualizarSegmentoPreferido($idInscripcion, $segmento)
   - Valida que sea un segmento válido
   - Segmentos válidos: 'jovenes', 'teens', 'hombres_adultos', 'mujeres_adultas', ''
   - Actualiza la BD con el nuevo segmento

3. VISTA (views/home/_modulo_formacion.php)
   ✓ Agregado botón "Cambio Seg." en todas las tablas de segmentos:
     - Tabla Jóvenes (14-28 años)
     - Tabla Teens (9-13 años)
     - Tabla Hombres Adultos (30+)
     - Tabla Mujeres Adultas (30+)
   
   ✓ Agregado MODAL popup para seleccionar nuevo segmento
   ✓ Agregado JavaScript para:
     - Capturar clics en botones "Cambio Seg."
     - Mostrar modal con opciones de segmentos
     - Enviar cambio a servidor
     - Recargar página al completar
   
   ✓ Actualizada LÓGICA DE CLASIFICACIÓN:
     - Si existe Segmento_Preferido: usa ese valor
     - Si no existe: clasifica por edad + género (comportamiento anterior)

4. CONTROLADOR (app/Controllers/HomeController.php)
   ✓ Nuevo método: cambiarSegmentoInscripcion()
   - Validación de permisos
   - Validación de inscripción
   - Validación de acceso a la persona
   - Llama a actualizarSegmentoPreferido()
   - Retorna JSON con resultado

═══════════════════════════════════════════════════════════════════════════════════

🎯 CÓMO FUNCIONA:

1. Usuario ve tabla de inscritos (ej: "Jóvenes 14-28 años")
2. Hace clic en botón "Cambio Seg." junto a una persona
3. Se abre MODAL mostrando:
   - Nombre de la persona
   - Selector de segmentos: Jóvenes, Teens, Hombres Adultos, Mujeres Adultas
   - Opción "Sin cambio (por edad/género)" para limpiar preferencia
4. Usuario selecciona nuevo segmento y hace clic en "Guardar"
5. AJAX envía datos a: /home/cambiar-segmento-inscripcion
6. Servidor valida permisos y actualiza BD
7. Página se recarga automáticamente
8. La persona ahora aparece en el segmento elegido (aunque su edad no lo justifique)

═══════════════════════════════════════════════════════════════════════════════════

📊 EJEMPLO: RAÚL (28 años, joven)

ANTES:
- Aparecía en: "Tabla Jóvenes (14-28 años)"
- No podía asistir con "Hombres Adultos"

DESPUÉS:
- Usuario hace clic "Cambio Seg."
- Selecciona "Hombres Adultos (30+)"
- Raúl ahora aparece en "Tabla Hombres Adultos"
- Puede marcar asistencia en clases para adultos

═══════════════════════════════════════════════════════════════════════════════════

🔧 PERMISOS REQUERIDOS:

- Usuario debe tener permiso: "personas" -> "editar" (puedeEditarPersonaFormacion)
- Y acceso a la persona específica (puedeGestionarPersonaFormacion)

═══════════════════════════════════════════════════════════════════════════════════

✨ CARACTERÍSTICAS ADICIONALES:

✓ Botón flotante en línea con "Editar"
✓ Modal elegante con validación
✓ Posibilidad de limpiar preferencia (volver a clasificación automática)
✓ Segmentación respeta género cuando es posible
✓ Refresco automático para confirmar cambio
✓ Validación de seguridad en servidor
✓ Muy flexible: permite cualquier combinación de edad/género/segmento

═══════════════════════════════════════════════════════════════════════════════════
