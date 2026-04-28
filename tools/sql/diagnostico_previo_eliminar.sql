-- =============================================================================
-- DIAGNÓSTICO COMPLETO: Dónde aparecen las 2 personas antes de eliminar
-- LAURA VALENTINA APONTE CONTRERAS (CC 1073156731)
-- LAURA VALENTINA POSADA PARDO (CC 1015994401)
-- Fecha: 2026-04-27
-- =============================================================================

-- 1. EN TABLA PERSONA
SELECT '=== TABLA PERSONA ===' AS Tabla, COUNT(*) AS Registros_Encontrados
FROM persona
WHERE Numero_Documento IN ('1073156731', '1015994401');

SELECT * FROM persona
WHERE Numero_Documento IN ('1073156731', '1015994401');


-- 2. EN TABLA ESCUELA_FORMACION_INSCRIPCION
SELECT '=== TABLA ESCUELA_FORMACION_INSCRIPCION ===' AS Tabla, COUNT(*) AS Registros_Encontrados
FROM escuela_formacion_inscripcion
WHERE Cedula IN ('1073156731', '1015994401');

SELECT * FROM escuela_formacion_inscripcion
WHERE Cedula IN ('1073156731', '1015994401');


-- 3. EN TABLA ESCUELA_FORMACION_ESTADO (si existe)
SELECT '=== TABLA ESCUELA_FORMACION_ESTADO ===' AS Tabla, COUNT(*) AS Registros_Encontrados
FROM escuela_formacion_estado
WHERE Id_Persona IN (
    SELECT Id_Persona FROM persona 
    WHERE Numero_Documento IN ('1073156731', '1015994401')
);

SELECT * FROM escuela_formacion_estado
WHERE Id_Persona IN (
    SELECT Id_Persona FROM persona 
    WHERE Numero_Documento IN ('1073156731', '1015994401')
);


-- 4. EN TABLA ESCUELA_FORMACION_ASISTENCIA_CLASE (si existe)
SELECT '=== TABLA ESCUELA_FORMACION_ASISTENCIA_CLASE ===' AS Tabla, COUNT(*) AS Registros_Encontrados
FROM escuela_formacion_asistencia_clase
WHERE Id_Persona IN (
    SELECT Id_Persona FROM persona 
    WHERE Numero_Documento IN ('1073156731', '1015994401')
);

SELECT * FROM escuela_formacion_asistencia_clase
WHERE Id_Persona IN (
    SELECT Id_Persona FROM persona 
    WHERE Numero_Documento IN ('1073156731', '1015994401')
);


-- 5. RESUMEN DE DÓNDE APARECEN
SELECT 
    '=== RESUMEN FINAL ===' AS diagnostico,
    'Personas a eliminar: 2' AS personas,
    'CC 1073156731 - LAURA VALENTINA APONTE CONTRERAS' AS persona_1,
    'CC 1015994401 - LAURA VALENTINA POSADA PARDO' AS persona_2;
