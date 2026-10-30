# Registro público — Escuelas de Formación

Formulario público de inscripción: Universidad de la Vida (y bautismo) y Capacitación Destino.

## URLs

- Universidad de la Vida: `?url=escuelas_formacion/registro-publico/universidad-vida`
- Capacitación Destino: `?url=escuelas_formacion/registro-publico/capacitacion-destino`
- Buscar persona (AJAX): `escuelas_formacion/registro-publico/buscar-persona`
- Guardar: `escuelas_formacion/registro-publico/guardar`

## Archivos

- Controlador: `app/Controllers/EscuelaFormacionRegistroController.php`
- Vista: `views/escuelas_formacion_publico/formulario.php`
- Modelos: `Persona`, `EscuelaFormacionInscripcion`, `Ministerio`

## Cambio del 20 de octubre de 2026 — Ficha nueva en el mismo formulario

Antes, si la cédula no estaba en la base, el flujo abría el formulario privado `personas/crear` (atajos y login). Eso ya no aplica a este registro público.

### Comportamiento actual

1. **Una sola sección «Datos personales»**  
   Documento, cédula, teléfono, nombre, apellidos, género, fecha de nacimiento, edad, dirección y tratamiento de datos (si la columna existe).

2. **Si la persona ya está en la base**  
   Tras buscar por cédula se cargan y muestran sus datos (resumen «Persona encontrada»). No se crea otra ficha.

3. **Si no está en la base**  
   Aviso visible: *«Esta persona no se encuentra en nuestra base de datos. Por favor complete los campos…»*  
   Recordatorio: los campos con `*` son obligatorios.  
   Al guardar se crea la ficha en `persona` con las **mismas reglas de identidad** que `personas/crear` y se continúa la inscripción.

### Campos obligatorios al crear ficha (persona nueva)

| Campo | Notas |
|--------|--------|
| Nombre | Solo nombres; letras; sin X, N/A, relleno |
| Apellidos | Al menos un apellido real; no iguales al nombre |
| Tipo y número de documento | Obligatorios |
| Teléfono | Obligatorio; no duplicar salvo TI / Registro Civil |
| Fecha de nacimiento | Obligatoria |
| Género | Hombre / Mujer |
| Tratamiento de datos | Obligatorio si existe la columna `Tratamiento_Datos` |

Validación compartida: `BaseController::validarNombreYApellidoPersona` / `primerErrorNombreYApellidoPersona`.  
Duplicados: `Persona::findDuplicateByCedulaOrTelefono`.

### Qué no hace este formulario

No redirige a `personas/crear`.  
No pide login para crear la ficha desde el registro público.  
Líder y ministerio siguen siendo parte de la inscripción (sección ministerial), no atajos del formulario privado de personas.

### Despliegue

Subido a producción el 20 de octubre de 2026.  
Archivos: controlador, vista pública y `public/assets/.build` (`20261020-uv-datos-personales-unificado` y siguientes).  
No requiere SQL nuevo (usa `Tratamiento_Datos` si ya existe; el controlador puede asegurar la columna).

### Botón «Limpiar formulario»

Vacía cédula, datos personales, líder, ministerio, programa/segmento, ids ocultos y documentos pendientes; desbloquea campos readonly/disabled de una persona cargada; cancela búsquedas AJAX en curso. Corregido el 20 de octubre de 2026 (fallaba por una variable JS inexistente).
