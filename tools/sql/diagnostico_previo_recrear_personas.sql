-- =============================================================================
-- Diagnóstico PREVIO a recrear personas desde escuela_formacion_inscripcion
-- Personas: LAURA VALENTINA POSADA PARDO (CC 1015994401)
--           Laura Valentina Aponte Contreras (CC 1073156731)
-- Fecha: 2026-04-27
-- =============================================================================

-- 1. VER INSCRIPCIONES EN ESCUELA_FORMACION_INSCRIPCION
SELECT 
    '=== INSCRIPCIONES ACTUALES ===' AS paso,
    Id_Inscripcion,
    Id_Persona,
    Cedula,
    Nombre,
    Apellido,
    Edad,
    Genero,
    Telefono,
    Programa,
    Fuente,
    Fecha_Registro
FROM escuela_formacion_inscripcion
WHERE Cedula IN ('1015994401', '1073156731')
ORDER BY Cedula;


-- 2. VER TODOS LOS MINISTERIOS (para obtener Id_Ministerio de Fabia y Elizabeth)
SELECT 
    '=== MINISTERIOS DISPONIBLES ===' AS paso,
    Id_Ministerio,
    Nombre_Ministerio
FROM ministerio
ORDER BY Nombre_Ministerio;


-- 3. VERIFICAR SI HAY Id_Persona NULL O ASIGNADO EN ESAS INSCRIPCIONES
SELECT 
    '=== VERIFICAR VINCULACION PERSONA ===' AS paso,
    Id_Inscripcion,
    Cedula,
    Nombre,
    Id_Persona,
    CASE 
        WHEN Id_Persona IS NULL THEN 'SIN VINCULACION'
        WHEN Id_Persona = 0 THEN 'VINCULACION NULA'
        ELSE CONCAT('VINCULADA A: ', Id_Persona)
    END AS Estado_Vinculacion
FROM escuela_formacion_inscripcion
WHERE Cedula IN ('1015994401', '1073156731')
ORDER BY Cedula;


-- 4. VERIFICAR SI DOCUMENTOS YA EXISTEN EN TABLA PERSONA
SELECT 
    '=== VERIFICAR EXISTENCIA EN PERSONA ===' AS paso,
    p.Id_Persona,
    p.Numero_Documento,
    CONCAT(p.Nombre, ' ', p.Apellido) AS Nombre_Completo,
    p.Proceso,
    m.Nombre_Ministerio
FROM persona p
LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
WHERE p.Numero_Documento IN ('1015994401', '1073156731');


-- 5. CONTAR CUÁNTAS INSCRIPCIONES TIENE CADA CÉDULA
SELECT 
    '=== INSCRIPCIONES POR CÉDULA ===' AS paso,
    Cedula,
    COUNT(*) AS Total_Inscripciones,
    GROUP_CONCAT(DISTINCT Programa) AS Programas
FROM escuela_formacion_inscripcion
WHERE Cedula IN ('1015994401', '1073156731')
GROUP BY Cedula;
