-- =============================================================================
-- Ver estructura real de tabla escuela_formacion_inscripcion
-- Fecha: 2026-04-27
-- =============================================================================

-- 1. VER ESTRUCTURA DE LA TABLA
DESCRIBE escuela_formacion_inscripcion;

-- 2. VER COLUMNAS CON INFORMACIÓN
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_KEY,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'escuela_formacion_inscripcion'
ORDER BY ORDINAL_POSITION;


-- 3. VER 5 REGISTROS MUESTRA
SELECT * FROM escuela_formacion_inscripcion LIMIT 5;


-- 4. BÚSQUEDA CORRECTA POR CÉDULA (sin asumir estructura)
SELECT * FROM escuela_formacion_inscripcion
WHERE Cedula = '1073156731'
LIMIT 10;
