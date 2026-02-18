# NUEVOS CAMPOS AGREGADOS A PERSONA

## Campos Agregados (Diciembre 2025)

Se han agregado los siguientes campos a la tabla PERSONA para capturar más información de los miembros:

### 1. Información de Identificación
- **Tipo_Documento**: Tipo de documento de identidad
  - Registro Civil
  - Cédula de Ciudadanía (por defecto)
  - Cédula Extranjera
  
- **Numero_Documento**: Número del documento de identidad

### 2. Información Demográfica
- **Edad**: Edad en años (campo numérico)
- **Genero**: Género/Categoría
  - Hombre
  - Mujer
  - Joven Hombre
  - Joven Mujer

### 3. Información de Contacto Adicional
- **Hora_Llamada**: Mejor horario para contacto telefónico
  - Mañana
  - Medio Día
  - Tarde
  - Noche
  - Cualquier Hora (por defecto)
  
- **Barrio**: Barrio de residencia

### 4. Información Ministerial
- **Peticion**: Petición de oración personal (campo de texto largo)
- **Invitado_Por**: ID de la persona que invitó (relación con tabla PERSONA)
- **Tipo_Reunion**: Tipo de reunión donde asistió por primera vez
  - Domingo
  - Célula
  - Reunión Jóvenes
  - Reunión Hombre
  - Reunión Mujeres
  - Grupo Go
  - Seminario
  - Pesca
  - Semana Santa
  - Otro

## Archivos Modificados

1. **mci.sql** - Estructura completa actualizada
2. **actualizar_persona.sql** - Script para actualizar base de datos existente
3. **app/Controllers/PersonaController.php** - Manejo de nuevos campos
4. **app/Models/Persona.php** - Consultas con relaciones actualizadas
5. **views/personas/formulario.php** - Formulario completo con todos los campos
6. **views/personas/detalle.php** - Vista de detalle completa
7. **views/personas/lista.php** - Lista de personas
8. **public/assets/css/styles.css** - Estilos para formularios y detalles

## Cómo Actualizar

Si ya tienes datos en la base de datos, ejecuta:

```sql
mysql -u root -P 3310 --protocol=TCP < actualizar_persona.sql
```

O desde PHP MyAdmin, importa el archivo `actualizar_persona.sql`

## Notas Técnicas

- La relación "Invitado_Por" es auto-referencial (PERSONA → PERSONA)
- Todos los campos nuevos permiten valores NULL excepto los que tienen valores por defecto
- Se agregaron índices para mejorar el rendimiento en búsquedas por documento
- El formulario ahora usa diseño en grid responsive (form-row)
- La vista de detalle muestra toda la información organizada por secciones

## Validaciones Recomendadas (Futuras)

- Validar formato de número de documento según tipo
- Calcular edad automáticamente desde fecha de nacimiento
- Evitar que una persona se invite a sí misma
- Requerir ciertos campos según el rol de la persona
