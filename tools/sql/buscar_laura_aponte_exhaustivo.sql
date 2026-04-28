-- =============================================================================
-- Búsqueda exhaustiva para LAURA VALENTINA APONTE CONTRERAS
-- En tabla escuela_formacion_inscripcion
-- Fecha: 2026-04-27
-- =============================================================================

-- 1. BÚSQUEDA POR CÉDULA 1073156731 EXACTA
SELECT 
    '=== BÚSQUEDA 1: Por Cédula Exacta ===' AS busqueda,
    Id_Inscripcion,
    Cedula,
    Nombre,
    Apellido,
    Edad,
    Genero,
    Telefono,
    Programa,
    Fecha_Registro
FROM escuela_formacion_inscripcion
WHERE Cedula = '1073156731'
   OR Cedula LIKE '%1073156731%'
ORDER BY Fecha_Registro DESC;


-- 2. BÚSQUEDA POR NOMBRE "LAURA" + "APONTE"
SELECT 
    '=== BÚSQUEDA 2: Por Nombre Laura + Aponte ===' AS busqueda,
    Id_Inscripcion,
    Cedula,
    Nombre,
    Apellido,
    Edad,
    Genero,
    Telefono,
    Programa,
    Fecha_Registro
FROM escuela_formacion_inscripcion
WHERE (UPPER(Nombre) LIKE '%LAURA%' AND UPPER(Apellido) LIKE '%APONTE%')
   OR UPPER(CONCAT(Nombre, ' ', Apellido)) LIKE '%LAURA%VALENTINA%APONTE%'
ORDER BY Fecha_Registro DESC;


-- 3. BÚSQUEDA POR NOMBRE SOLO "APONTE"
SELECT 
    '=== BÚSQUEDA 3: Por Apellido Aponte ===' AS busqueda,
    Id_Inscripcion,
    Cedula,
    Nombre,
    Apellido,
    Edad,
    Genero,
    Telefono,
    Programa,
    Fecha_Registro
FROM escuela_formacion_inscripcion
WHERE UPPER(Apellido) LIKE '%APONTE%'
ORDER BY Fecha_Registro DESC;


-- 4. BÚSQUEDA POR TELÉFONO (Si se conoce)
SELECT 
    '=== BÚSQUEDA 4: Todas las Laura ===' AS busqueda,
    Id_Inscripcion,
    Cedula,
    Nombre,
    Apellido,
    Edad,
    Genero,
    Telefono,
    Programa,
    Fecha_Registro
FROM escuela_formacion_inscripcion
WHERE UPPER(Nombre) LIKE '%LAURA%'
ORDER BY Fecha_Registro DESC
LIMIT 20;


-- 5. BÚSQUEDA EN TABLA PERSONA POR CÉDULA
SELECT 
    '=== BÚSQUEDA 5: En tabla PERSONA por Cédula ===' AS busqueda,
    Id_Persona,
    Numero_Documento,
    Nombre,
    Apellido,
    Genero,
    Edad,
    Telefono,
    Proceso
FROM persona
WHERE Numero_Documento = '1073156731'
   OR Numero_Documento LIKE '%1073156731%';


-- 6. BÚSQUEDA EN TABLA PERSONA POR NOMBRE
SELECT 
    '=== BÚSQUEDA 6: En tabla PERSONA por Nombre ===' AS busqueda,
    Id_Persona,
    Numero_Documento,
    Nombre,
    Apellido,
    Genero,
    Edad,
    Telefono,
    Proceso
FROM persona
WHERE (UPPER(Nombre) LIKE '%LAURA%' AND UPPER(Apellido) LIKE '%APONTE%')
   OR UPPER(CONCAT(Nombre, ' ', Apellido)) LIKE '%LAURA%VALENTINA%APONTE%';


-- 7. CONTAR CUÁNTAS "LAURA" HAY EN ESCUELA_FORMACION_INSCRIPCION
SELECT 
    '=== BÚSQUEDA 7: Total de Lauras en Escuelas ===' AS busqueda,
    COUNT(*) AS Total_Lauras
FROM escuela_formacion_inscripcion
WHERE UPPER(Nombre) LIKE '%LAURA%';


-- 8. VER ÚLTIMAS 50 INSCRIPCIONES (para ver si está reciente)
SELECT 
    '=== BÚSQUEDA 8: Últimas 50 inscripciones ===' AS busqueda,
    Id_Inscripcion,
    Cedula,
    Nombre,
    Apellido,
    Telefono,
    Programa,
    Fecha_Registro
FROM escuela_formacion_inscripcion
ORDER BY Fecha_Registro DESC
LIMIT 50;
